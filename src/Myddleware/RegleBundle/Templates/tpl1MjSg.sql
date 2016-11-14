INSERT INTO Template VALUES ('5456540f9bd9b','12','1');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','5456540f9bd9b','tpl1MjSg','Création ou mise à jour de contact SugarCRM pour chaque contact Mailjet.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','5456540f9bd9b','tpl1MjSg','Creating or updating a SugarCRM contact for each Mailjet contact.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5456540f9bd9b', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','contactstatistics','Contacts','0','0','prefixRuleName_mjsg_contact','prefixRuleName_mjsg_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5456540f9bd9b', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','email1') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5456540f9bd9b', "INSERT INTO `RuleFilters` (`rule_id`,`rfi_target`,`rfi_type`,`rfi_value`) VALUES ('idRule','Email','content','@') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5456540f9bd9b', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES 
('idRule','email1','Email',''),
('idRule','email_opt_out','BouncedCount;SpamComplaintCount;UnsubscribedCount','( ({BouncedCount} != 0 || {UnsubscribedCount} != 0 || {SpamComplaintCount} != 0 ) ? 1 : 0 )') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5456540f9bd9b', 'CREATE TABLE `z_prefixRuleName_mjsg_contact_001_source` (
  `id_prefixRuleName_mjsg_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BouncedCount` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `SpamComplaintCount` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `UnsubscribedCount` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_mjsg_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5456540f9bd9b', 'CREATE TABLE `z_prefixRuleName_mjsg_contact_001_target` (
  `id_prefixRuleName_mjsg_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_opt_out` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_mjsg_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5456540f9bd9b', 'CREATE TABLE `z_prefixRuleName_mjsg_contact_001_history` (
  `id_prefixRuleName_mjsg_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_opt_out` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_mjsg_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

