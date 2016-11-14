INSERT INTO Template VALUES ('5416e6e514e7c','7','2');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','5416e6e514e7c','tpl1MaSf','Envoi des bogues et notes Mantis vers les requetes et notes Salesforce.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','5416e6e514e7c','tpl1MaSf','Sending Mantis bugs and notes to Salesforce requests and notes.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5416e6e514e7c', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','issue','Case','0','0','prefixRuleName_masf_message','prefixRuleName_masf_message','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5416e6e514e7c', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','projectId','1'),
('idRule','filterId','25') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5416e6e514e7c', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','Description','description',''),
('idRule','Priority','priority','({priority} == \"10\" ? \"Low\" : ({priority} == \"20\" ? \"Low\" : ({priority} == \"30\" ? \"Medium\" : \"High\")))'),
('idRule','Status','status','({status} == \"10\" ? \"New\" : ({status} == \"20\" ? \"Working\" : ({status} == \"30\" ? \"Working\" : ({status} == \"40\" ? \"Working\" : ({status} == \"50\" ? \"Working\" : \"Closed\")))))'),
('idRule','Subject','summary','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5416e6e514e7c', 'CREATE TABLE `z_prefixRuleName_masf_message_001_source` (
  `id_prefixRuleName_masf_message_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8_unicode_ci,
  `priority` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `summary` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id_prefixRuleName_masf_message_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5416e6e514e7c', 'CREATE TABLE `z_prefixRuleName_masf_message_001_target` (
  `id_prefixRuleName_masf_message_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Priority` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Status` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Subject` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_masf_message_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5416e6e514e7c', 'CREATE TABLE `z_prefixRuleName_masf_message_001_history` (
  `id_prefixRuleName_masf_message_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Priority` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Status` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Subject` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_masf_message_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5416e6e514e7c', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','note','Attachment','0','0','prefixRuleName_masf_note','prefixRuleName_masf_note','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5416e6e514e7c', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','projectId','1'),
('idRule','filterId','25') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5416e6e514e7c', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','issue','ParentId','#BEG#masf_message#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5416e6e514e7c', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','Body','text',''),
('idRule','Name','id','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5416e6e514e7c', 'CREATE TABLE `z_prefixRuleName_masf_note_001_source` (
  `id_prefixRuleName_masf_note_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `text` text COLLATE utf8_unicode_ci,
  `id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `issue` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_masf_note_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5416e6e514e7c', 'CREATE TABLE `z_prefixRuleName_masf_note_001_target` (
  `id_prefixRuleName_masf_note_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Body` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ParentId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_masf_note_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5416e6e514e7c', 'CREATE TABLE `z_prefixRuleName_masf_note_001_history` (
  `id_prefixRuleName_masf_note_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Body` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ParentId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_masf_note_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

