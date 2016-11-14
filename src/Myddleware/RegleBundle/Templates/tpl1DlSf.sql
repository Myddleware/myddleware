INSERT INTO Template VALUES ('5409ccb2c8546','6','2');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','5409ccb2c8546','tpl1DlSf','Envoi des contacts Dolist dans les contacts Salesforce.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','5409ccb2c8546','tpl1DlSf','Sending Dolist contacts in Salesforce contacts.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ccb2c8546', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','contact','Contact','0','0','prefixRuleName_dlsf_contacts','prefixRuleName_dlsf_contacts','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ccb2c8546', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ccb2c8546', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','LastName','lastname',''),
('idRule','Email','Email',''),
('idRule','FirstName','firstname',''),
('idRule','Phone','phone','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ccb2c8546', 'CREATE TABLE `z_prefixRuleName_dlsf_contacts_001_source` (
  `id_prefixRuleName_dlsf_contacts_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `lastname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_dlsf_contacts_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ccb2c8546', 'CREATE TABLE `z_prefixRuleName_dlsf_contacts_001_target` (
  `id_prefixRuleName_dlsf_contacts_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FirstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Phone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_dlsf_contacts_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ccb2c8546', 'CREATE TABLE `z_prefixRuleName_dlsf_contacts_001_history` (
  `id_prefixRuleName_dlsf_contacts_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FirstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Phone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_dlsf_contacts_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

