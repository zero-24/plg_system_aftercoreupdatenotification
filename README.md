# After Core Update Notification Plugin

This Joomla plugin sends notifiaction to the customer once an Joomla Core Update has been installed

## Configuration

### Initial setup the plugin

- [Download the latest version of the plugin](https://github.com/zero-24/plg_system_aftercoreupdatenotification/releases/latest)
- Install the plugin using `Upload & Install`
- Enable the plugin `System - After Core Update Notification` from the plugin manager
- Optional: Setup the customer eMails the notification should be send to within the plugin settings
- Optional: Adjust the Mail Template accouding to your needs

Now the inital setup is completed.

## Issues / Pull Requests

You have found an Issue, have a question or you would like to suggest changes regarding this extension?
[Open an issue in this repo](https://github.com/zero-24/plg_system_aftercoreupdatenotification/issues/new) or submit a pull request with the proposed changes.

## Translations

You want to translate this extension to your own language? Check out my [Crowdin Page for my Extensions](https://joomla.crowdin.com/zero-24) for more details. Feel free to [open an issue here](https://github.com/zero-24/plg_system_aftercoreupdatenotification/issues/new) on any question that comes up.

## Joomla! Extensions Directory (JED)

This plugin can also been found in the Joomla! Extensions Directory: [AfterCoreUpdateNotification by zero24](https://extensions.joomla.org/extension/aftercoreupdatenotification/)

## Release steps

- `build/build.sh`
- `git commit -am 'prepare release AfterCoreUpdateNotification 1.0.1'`
- `git tag -s '1.0.1' -m 'AfterCoreUpdateNotification 1.0.1'`
- `git push origin --tags`
- create the release on GitHub
- `git push origin master`

## Crowdin

### Upload new strings

`crowdin upload sources`

### Download translations

`crowdin download --skip-untranslated-files --ignore-match`
