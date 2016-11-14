INSERT INTO Template VALUES ('540f93cd4effa','4','1');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','540f93cd4effa','tpl1GwSg','Envoi des événements et participants GotoWebinar dans les réunions et contacts de SugarCRM.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','540f93cd4effa','tpl1GwSg','Sending GotoWebinar events and participants to meetings and contacts of SugarCRM.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','organizerSessions','Meetings','0','0','prefixRuleName_gwsg_event','prefixRuleName_gwsg_event','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','date_start','startTime',''),
('idRule','name','sessionKey','\"Webinar: \".{sessionKey}'),
('idRule','date_end','endTime',''),
('idRule','description','registrantsAttended','\"Participants: \".{registrantsAttended}'),
('idRule','duration_hours','my_value','\" \"') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', 'CREATE TABLE `z_prefixRuleName_gwsg_event_001_source` (
  `id_prefixRuleName_gwsg_event_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `startTime` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sessionKey` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `endTime` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `registrantsAttended` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsg_event_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', 'CREATE TABLE `z_prefixRuleName_gwsg_event_001_target` (
  `id_prefixRuleName_gwsg_event_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `date_start` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_end` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `duration_hours` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsg_event_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', 'CREATE TABLE `z_prefixRuleName_gwsg_event_001_history` (
  `id_prefixRuleName_gwsg_event_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `date_start` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_end` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `duration_hours` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsg_event_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','attendees','Contacts','0','0','prefixRuleName_gwsg_contact','prefixRuleName_gwsg_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','email1') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','last_name','lastName',''),
('idRule','description','attendanceTimeInSeconds','\"DurÃ©e de la prÃ©sence: \".{attendanceTimeInSeconds}.\" s\"'),
('idRule','email1','email',''),
('idRule','first_name','firstName','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', 'CREATE TABLE `z_prefixRuleName_gwsg_contact_001_source` (
  `id_prefixRuleName_gwsg_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `lastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `attendanceTimeInSeconds` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsg_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', 'CREATE TABLE `z_prefixRuleName_gwsg_contact_001_target` (
  `id_prefixRuleName_gwsg_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsg_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', 'CREATE TABLE `z_prefixRuleName_gwsg_contact_001_history` (
  `id_prefixRuleName_gwsg_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsg_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','attendees','meetings_contacts','0','0','prefixRuleName_gwsg_rel_event_contact','prefixRuleName_gwsg_rel_event_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','mode','C'),
('idRule','delete','60'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','Myddleware_element_id','contact_id','#BEG#gwsg_contact#END#') ");
INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','sessionKey','meeting_id','#BEG#gwsg_event#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', 'CREATE TABLE `z_prefixRuleName_gwsg_rel_event_contact_001_source` (
  `id_prefixRuleName_gwsg_rel_event_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Myddleware_element_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sessionKey` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsg_rel_event_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', 'CREATE TABLE `z_prefixRuleName_gwsg_rel_event_contact_001_target` (
  `id_prefixRuleName_gwsg_rel_event_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contact_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `meeting_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsg_rel_event_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f93cd4effa', 'CREATE TABLE `z_prefixRuleName_gwsg_rel_event_contact_001_history` (
  `id_prefixRuleName_gwsg_rel_event_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contact_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `meeting_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsg_rel_event_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

