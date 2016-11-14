INSERT INTO Template VALUES ('54abcf78c19d2','14','1');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','54abcf78c19d2','tpl1EtSg','Envoi des contacts Exacttarget vers SugarCRM.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','54abcf78c19d2','tpl1EtSg','Sending Exacttarget contacts to SugarCRM.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54abcf78c19d2', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Subscriber','Contacts','0','0','prefixRuleName_etsg_contact','prefixRuleName_etsg_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54abcf78c19d2', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','email1') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54abcf78c19d2', "INSERT INTO `RuleFilters` (`rule_id`,`rfi_target`,`rfi_type`,`rfi_value`) VALUES ('idRule','EmailAddress','content','@') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54abcf78c19d2', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','last_name','Full_mydblank_Name',''),
('idRule','description','Status','\"Status: \".{Status}'),
('idRule','email1','EmailAddress',''),
('idRule','first_name','User_mydblank_Defined','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54abcf78c19d2', 'CREATE TABLE `z_prefixRuleName_etsg_contact_001_source` (
  `id_prefixRuleName_etsg_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Full_mydblank_Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Status` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EmailAddress` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `User_mydblank_Defined` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_etsg_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54abcf78c19d2', 'CREATE TABLE `z_prefixRuleName_etsg_contact_001_target` (
  `id_prefixRuleName_etsg_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_etsg_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54abcf78c19d2', 'CREATE TABLE `z_prefixRuleName_etsg_contact_001_history` (
  `id_prefixRuleName_etsg_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_etsg_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

