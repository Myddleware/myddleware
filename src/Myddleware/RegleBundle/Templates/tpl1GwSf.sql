INSERT INTO Template VALUES ('540f92683d33b','4','2');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','540f92683d33b','tpl1GwSf','Envoi des événements et participants GotoWebinar dans les événements et contacts de Salesforce.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','540f92683d33b','tpl1GwSf','Sending GoToWebinar events and participants to events and contacts of Salesforce.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','organizerSessions','Event','0','0','prefixRuleName_gwsf_event','prefixRuleName_gwsf_event','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','Description','registrantsAttended','\"Participants: \".{registrantsAttended}'),
('idRule','EndDateTime','endTime',''),
('idRule','StartDateTime','startTime',''),
('idRule','Subject','sessionKey','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', 'CREATE TABLE `z_prefixRuleName_gwsf_event_001_source` (
  `id_prefixRuleName_gwsf_event_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `registrantsAttended` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `endTime` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `startTime` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sessionKey` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsf_event_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', 'CREATE TABLE `z_prefixRuleName_gwsf_event_001_target` (
  `id_prefixRuleName_gwsf_event_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EndDateTime` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StartDateTime` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Subject` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsf_event_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', 'CREATE TABLE `z_prefixRuleName_gwsf_event_001_history` (
  `id_prefixRuleName_gwsf_event_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EndDateTime` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StartDateTime` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Subject` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsf_event_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','attendees','Contact','0','0','prefixRuleName_gwsf_contact','prefixRuleName_gwsf_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','Email') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','LastName','lastName',''),
('idRule','Description','attendanceTimeInSeconds','\"DurÃ©e de la prÃ©sence: \".{attendanceTimeInSeconds}'),
('idRule','Email','email',''),
('idRule','FirstName','firstName','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', 'CREATE TABLE `z_prefixRuleName_gwsf_contact_001_source` (
  `id_prefixRuleName_gwsf_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `lastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `attendanceTimeInSeconds` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsf_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', 'CREATE TABLE `z_prefixRuleName_gwsf_contact_001_target` (
  `id_prefixRuleName_gwsf_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FirstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsf_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', 'CREATE TABLE `z_prefixRuleName_gwsf_contact_001_history` (
  `id_prefixRuleName_gwsf_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FirstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsf_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','attendees','EventRelation','0','0','prefixRuleName_gwsf_rel_event_contact','prefixRuleName_gwsf_rel_event_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','sessionKey','EventId','#BEG#gwsf_event#END#') ");
INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','Myddleware_element_id','RelationId','#BEG#gwsf_contact#END#') ");


INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', 'CREATE TABLE `z_prefixRuleName_gwsf_rel_event_contact_001_source` (
  `id_prefixRuleName_gwsf_rel_event_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sessionKey` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Myddleware_element_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsf_rel_event_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', 'CREATE TABLE `z_prefixRuleName_gwsf_rel_event_contact_001_target` (
  `id_prefixRuleName_gwsf_rel_event_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `EventId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `RelationId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsf_rel_event_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('540f92683d33b', 'CREATE TABLE `z_prefixRuleName_gwsf_rel_event_contact_001_history` (
  `id_prefixRuleName_gwsf_rel_event_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `EventId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `RelationId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwsf_rel_event_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

