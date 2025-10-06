<?php

/**
 * AfterCoreUpdateNotification System Plugin
 *
 * @copyright  Copyright (C) 2024 Tobias Zulauf All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or later
 */

namespace Joomla\Plugin\System\AfterCoreUpdateNotification\Extension;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Event\Extension\AfterJoomlaUpdateEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\Exception\MailDisabledException;
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\Asset;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use PHPMailer\PHPMailer\Exception as phpMailerException;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * A task plugin. Checks for extension Updates and sends an eMail once one has been found
 *
 * @since 1.0.0
 */
final class AfterCoreUpdateNotification extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * @var boolean
	 *
     * @since 1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * @inheritDoc
     *
     * @return string[]
     *
     * @since 1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onJoomlaAfterUpdate' => 'onJoomlaAfterUpdate',
        ];
    }

   /**
     * On after CMS Update
     *
     * Method is called after user update the CMS.
     *
     * @param   AfterJoomlaUpdateEvent $event  The event instance.
     *
     * @return  void
     *
     * @since   1.0.0
     *
     */
    public function onJoomlaAfterUpdate(AfterJoomlaUpdateEvent $event): void
    {
        $arguments  = array_values($event->getArguments());
        $oldVersion = $arguments[0] ?? '';

        if (empty($oldVersion)) {
            $oldVersion = $this->getApplication()->getLanguage()->_('JLIB_UNKNOWN');
        }

		// Load the parameters.
        $specificEmail  = $this->params->get('email') ?? '';
        $forcedLanguage = $this->params->get('language_override') ?? '';

        // Let's find out the email addresses to notify
        $recipients = [];

        if (!empty($specificEmail))
        {
			$recipients = explode(',', $specificEmail);
        }

        if (empty($recipients))
        {
            $superUsers = $this->getSuperUsers();

			foreach ($superUsers as $superUser){
				$recipients[] = $superUser->email;
			}

        }

        if (empty($recipients))
        {
            return;
        }

        /*
         * Load the appropriate language. We try to load English (UK), the current user's language and the forced
         * language preference, in this order. This ensures that we'll never end up with untranslated strings in the
         * update email which would make Joomla! seem bad. So, please, if you don't fully understand what the
         * following code does DO NOT TOUCH IT. It makes the difference between a hobbyist CMS and a professional
         * solution!
         */
        $jLanguage = $this->getApplication()->getLanguage();
        $jLanguage->load('plg_system_aftercoreupdatenotification', JPATH_ADMINISTRATOR, 'en-GB', true, true);
        $jLanguage->load('plg_system_aftercoreupdatenotification', JPATH_ADMINISTRATOR, null, true, false);

        // Then try loading the preferred (forced) language
        if (!empty($forcedLanguage))
		{
            $jLanguage->load('plg_system_aftercoreupdatenotification', JPATH_ADMINISTRATOR, $forcedLanguage, true, false);
        }

		// Replace merge codes with their values
		$substitutions = [
			'newversion' => JVERSION,
			'oldversion' => $oldversion,
			'sitename'   => $this->getApplication()->get('sitename'),
			'url'        => Uri::base(),
			'datetime'   => Factory::getDate()->format(Text::_('DATE_FORMAT_FILTER_DATETIME')),
		];

		// Send the emails to the Super Users
		foreach ($recipients as $recipient)
		{
			try
			{
				$mailer = new MailTemplate('plg_system_aftercoreupdatenotification.core_update', $jLanguage->getTag());
				$mailer->addRecipient(trim($recipient));
				$mailer->addTemplateData($substitutions);
				$mailer->send();
			}
			catch (MailDisabledException | phpMailerException $exception)
			{
				return;
			}
        }

        return;
    }

    /**
     * Returns the Super Users email information. If you provide a comma separated $email list
     * we will check that these emails do belong to Super Users and that they have not blocked
     * system emails.
     *
     * @param   null|string  $email  A list of Super Users to email
     *
     * @return  array  The list of Super User emails
     *
     * @since   3.5
     */
    private function getSuperUsers($email = null)
    {
        $db     = $this->getDatabase();
        $emails = [];

        // Convert the email list to an array
        if (!empty($email)) {
            $temp   = explode(',', $email);

            foreach ($temp as $entry) {
                $emails[] = trim($entry);
            }

            $emails = array_unique($emails);
        }

        // Get a list of groups which have Super User privileges
        $ret = [];

        try {
            $table     = new Asset($db);
            $rootId    = $table->getRootId();
            $rules     = Access::getAssetRules($rootId)->getData();
            $rawGroups = $rules['core.admin']->getData();
            $groups    = [];

            if (empty($rawGroups)) {
                return $ret;
            }

            foreach ($rawGroups as $g => $enabled) {
                if ($enabled) {
                    $groups[] = $g;
                }
            }

            if (empty($groups)) {
                return $ret;
            }
        } catch (\Exception $exc) {
            return $ret;
        }

        // Get the user IDs of users belonging to the SA groups
        try {
            $query = $db->getQuery(true)
                ->select($db->quoteName('user_id'))
                ->from($db->quoteName('#__user_usergroup_map'))
                ->whereIn($db->quoteName('group_id'), $groups);

            $db->setQuery($query);
            $userIDs = $db->loadColumn(0);

            if (empty($userIDs)) {
                return $ret;
            }
        } catch (\Exception $exc) {
            return $ret;
        }

        // Get the user information for the Super Administrator users
        try {
            $query = $db->getQuery(true)
                ->select($db->quoteName(['id', 'username', 'email']))
                ->from($db->quoteName('#__users'))
                ->whereIn($db->quoteName('id'), $userIDs)
                ->where($db->quoteName('block') . ' = 0')
                ->where($db->quoteName('sendEmail') . ' = 1');

            if (!empty($emails)) {
                $lowerCaseEmails = array_map('strtolower', $emails);
                $query->whereIn('LOWER(' . $db->quoteName('email') . ')', $lowerCaseEmails, ParameterType::STRING);
            }

            $db->setQuery($query);
            $ret = $db->loadObjectList();
        } catch (\Exception $exc) {
            return $ret;
        }

        return $ret;
    }
}
