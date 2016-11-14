INSERT INTO Template VALUES ('54049fdab1b52','2','5');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','54049fdab1b52','tpl1SfPs','Création de client Prestashop pour chaque contact Salesforce.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','54049fdab1b52','tpl1SfPs','Creating a Prestashop customer for each Salesforce contact.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54049fdab1b52', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Contact','customers','0','0','prefixRuleName_SfPs_contact','prefixRuleName_SfPs_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54049fdab1b52', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','email','Email',''),
('idRule','firstname','FirstName',''),
('idRule','lastname','LastName',''),
('idRule','birthday','Birthdate',''),
('idRule','id_gender','Salutation','({Salutation} == \"Mr.\" ? \"1\" : ({Salutation} == \"Mrs.\" ? \"2\" : ({Salutation} == \"Ms.\" ? \"2\" : \"\") ) )'),
('idRule','passwd','my_value','\"password\"') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54049fdab1b52', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54049fdab1b52', 'CREATE TABLE `z_prefixRuleName_SfPs_contact_001_source` (
  `id_prefixRuleName_SfPs_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FirstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Salutation` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfPs_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54049fdab1b52', 'CREATE TABLE `z_prefixRuleName_SfPs_contact_001_target` (
  `id_prefixRuleName_SfPs_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthday` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id_gender` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `passwd` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfPs_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54049fdab1b52', 'CREATE TABLE `z_prefixRuleName_SfPs_contact_001_history` (
  `id_prefixRuleName_SfPs_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthday` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id_gender` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `passwd` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfPs_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

