INSERT INTO Template VALUES ('545654a4cee6f','4','12');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','545654a4cee6f','tpl1GwMj','Envoi des événements et participants GotoWebinar vers des listes et les contacts de Mailjet.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','545654a4cee6f','tpl1GwMj','Sending GotoWebinar events and participants to lists and contacts of Mailjet.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','organizerSessions','contactslist','0','0','prefixRuleName_gwmj_events','prefixRuleName_gwmj_events','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','Address','sessionKey',''),
('idRule','Name','sessionKey','\"RÃ©union Webinar: \".{sessionKey}') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', 'CREATE TABLE `z_prefixRuleName_gwmj_events_001_source` (
  `id_prefixRuleName_gwmj_events_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sessionKey` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmj_events_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', 'CREATE TABLE `z_prefixRuleName_gwmj_events_001_target` (
  `id_prefixRuleName_gwmj_events_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Address` text COLLATE utf8_unicode_ci,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmj_events_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', 'CREATE TABLE `z_prefixRuleName_gwmj_events_001_history` (
  `id_prefixRuleName_gwmj_events_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Address` text COLLATE utf8_unicode_ci,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmj_events_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','attendees','contact','0','0','prefixRuleName_gwmj_attendees','prefixRuleName_gwmj_attendees','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','Email') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', "INSERT INTO `RuleFilters` (`rule_id`,`rfi_target`,`rfi_type`,`rfi_value`) VALUES ('idRule','email','content','@') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','Email','email',''),
('idRule','Name','lastName',''),
('idRule','nom','lastName',''),
('idRule','prÃ©nom','firstName','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', 'CREATE TABLE `z_prefixRuleName_gwmj_attendees_001_source` (
  `id_prefixRuleName_gwmj_attendees_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmj_attendees_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', 'CREATE TABLE `z_prefixRuleName_gwmj_attendees_001_target` (
  `id_prefixRuleName_gwmj_attendees_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nom` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `prÃ©nom` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmj_attendees_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', 'CREATE TABLE `z_prefixRuleName_gwmj_attendees_001_history` (
  `id_prefixRuleName_gwmj_attendees_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nom` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `prÃ©nom` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmj_attendees_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','attendees','listrecipient','0','0','prefixRuleName_gwmj_rel_events_attendees','prefixRuleName_gwmj_rel_events_attendees','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','Myddleware_element_id','ContactID','#BEG#gwmj_attendees#END#') ");
INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','sessionKey','ListID','#BEG#gwmj_events#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', 'CREATE TABLE `z_prefixRuleName_gwmj_rel_events_attendees_001_source` (
  `id_prefixRuleName_gwmj_rel_events_attendees_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Myddleware_element_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sessionKey` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmj_rel_events_attendees_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', 'CREATE TABLE `z_prefixRuleName_gwmj_rel_events_attendees_001_target` (
  `id_prefixRuleName_gwmj_rel_events_attendees_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ContactID` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ListID` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmj_rel_events_attendees_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('545654a4cee6f', 'CREATE TABLE `z_prefixRuleName_gwmj_rel_events_attendees_001_history` (
  `id_prefixRuleName_gwmj_rel_events_attendees_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ContactID` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ListID` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmj_rel_events_attendees_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

