INSERT INTO Template VALUES ('546cbf58cf517','8','13');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','546cbf58cf517','tpl1EbMc','Envoi des événements EventBrite avec leurs particpants dans les listes et contacts Mailchimp.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','546cbf58cf517','tpl1EbMc','Sending events of EventBrite with their participants to Mailchimp lists and contacts.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf58cf517', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Events','lists','0','0','prefixRuleName_ebmc_event','prefixRuleName_ebmc_event','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf58cf517', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','mode','S'),
('idRule','delete','60'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf58cf517', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','name','title','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf58cf517', 'CREATE TABLE `z_prefixRuleName_ebmc_event_001_source` (
  `id_prefixRuleName_ebmc_event_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebmc_event_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf58cf517', 'CREATE TABLE `z_prefixRuleName_ebmc_event_001_target` (
  `id_prefixRuleName_ebmc_event_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebmc_event_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf58cf517', 'CREATE TABLE `z_prefixRuleName_ebmc_event_001_history` (
  `id_prefixRuleName_ebmc_event_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebmc_event_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf58cf517', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Attendees','subscribe','0','0','prefixRuleName_ebmc_subscribers','prefixRuleName_ebmc_subscribers','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf58cf517', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf58cf517', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','event_id','id','#BEG#ebmc_event#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf58cf517', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','email__email','email',''),
('idRule','merge_vars__FNAME','first_name',''),
('idRule','merge_vars__LNAME','last_name',''),
('idRule','merge_vars__birthday','birth_date','changeFormatDate( {birth_date},\"m/d/Y\")'),
('idRule','merge_vars__phone','cell_phone',''),
('idRule','double_optin','my_value','\"0\"') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf58cf517', 'CREATE TABLE `z_prefixRuleName_ebmc_subscribers_001_source` (
  `id_prefixRuleName_ebmc_subscribers_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birth_date` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cell_phone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `event_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebmc_subscribers_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf58cf517', 'CREATE TABLE `z_prefixRuleName_ebmc_subscribers_001_target` (
  `id_prefixRuleName_ebmc_subscribers_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email__email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `merge_vars__FNAME` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `merge_vars__LNAME` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `merge_vars__birthday` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `merge_vars__phone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `double_optin` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebmc_subscribers_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('546cbf58cf517', 'CREATE TABLE `z_prefixRuleName_ebmc_subscribers_001_history` (
  `id_prefixRuleName_ebmc_subscribers_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email__email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `merge_vars__FNAME` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `merge_vars__LNAME` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `merge_vars__birthday` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `merge_vars__phone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `double_optin` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebmc_subscribers_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

