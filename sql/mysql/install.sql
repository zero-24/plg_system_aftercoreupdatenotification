-- Add mail templates
INSERT INTO `#__mail_templates` (`template_id`, `extension`, `language`, `subject`, `body`, `htmlbody`, `attachments`, `params`) VALUES
('plg_system_aftercoreupdatenotification.core_update', 'plg_system_aftercoreupdatenotification', '', 'PLG_SYSTEM_AFTERCOREUPDATENOTIFICATION_UPDATE_MAIL_SUBJECT', 'PLG_SYSTEM_AFTERCOREUPDATENOTIFICATION_UPDATE_MAIL_BODY', '', '', '{"tags": ["newversion", "oldversion", "sitename", "url", "datetime"]}');
