INSERT INTO Template VALUES ('546cbf2703cab','4','13');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','546cbf2703cab','tpl1GwMc','Envoi des webinars avec leurs particpants dans les listes et contacts Mailchimp.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','546cbf2703cab','tpl1GwMc','Sending webinars with their participants to Mailchimp lists and contacts.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf2703cab', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','organizerSessions','lists','0','0','prefixRuleName_gwmc_list','prefixRuleName_gwmc_list','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf2703cab', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','mode','S'),
('idRule','delete','60'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf2703cab', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','name','sessionKey','\"RÃ©union Webinar: \".{sessionKey}') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf2703cab', 'CREATE TABLE `z_prefixRuleName_gwmc_list_001_source` (
  `id_prefixRuleName_gwmc_list_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `sessionKey` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmc_list_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf2703cab', 'CREATE TABLE `z_prefixRuleName_gwmc_list_001_target` (
  `id_prefixRuleName_gwmc_list_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmc_list_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf2703cab', 'CREATE TABLE `z_prefixRuleName_gwmc_list_001_history` (
  `id_prefixRuleName_gwmc_list_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmc_list_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf2703cab', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','attendees','subscribe','0','0','prefixRuleName_gwmc_subsciber','prefixRuleName_gwmc_subsciber','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf2703cab', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf2703cab', "INSERT INTO `RuleFilters` (`rule_id`,`rfi_target`,`rfi_type`,`rfi_value`) VALUES ('idRule','email','content','@') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf2703cab', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','sessionKey','id','#BEG#gwmc_list#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf2703cab', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','email__email','email',''),
('idRule','merge_vars__FNAME','firstName',''),
('idRule','merge_vars__LNAME','lastName',''),
('idRule','double_optin','my_value','\"0\"') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf2703cab', 'CREATE TABLE `z_prefixRuleName_gwmc_subsciber_001_source` (
  `id_prefixRuleName_gwmc_subsciber_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sessionKey` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmc_subsciber_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf2703cab', 'CREATE TABLE `z_prefixRuleName_gwmc_subsciber_001_target` (
  `id_prefixRuleName_gwmc_subsciber_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email__email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `merge_vars__FNAME` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `merge_vars__LNAME` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `double_optin` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmc_subsciber_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf2703cab', 'CREATE TABLE `z_prefixRuleName_gwmc_subsciber_001_history` (
  `id_prefixRuleName_gwmc_subsciber_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email__email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `merge_vars__FNAME` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `merge_vars__LNAME` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `double_optin` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_gwmc_subsciber_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

