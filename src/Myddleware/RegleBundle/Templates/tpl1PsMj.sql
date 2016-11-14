INSERT INTO Template VALUES ('54565521728d0','5','12');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','54565521728d0','tpl1PsMj','Envoi des groupes de clients et des clients Prestashop vers des listes de contacts et des contacts de Mailjet.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','54565521728d0','tpl1PsMj','Sending Prestashop customers groups and customers to lists of contacts and contacts of Mailjet.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','groups','contactslist','0','0','prefixRuleName_psmj_groups','prefixRuleName_psmj_groups','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','Address','name',''),
('idRule','Name','name','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', 'CREATE TABLE `z_prefixRuleName_psmj_groups_001_source` (
  `id_prefixRuleName_psmj_groups_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_psmj_groups_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', 'CREATE TABLE `z_prefixRuleName_psmj_groups_001_target` (
  `id_prefixRuleName_psmj_groups_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Address` text COLLATE utf8_unicode_ci,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_psmj_groups_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', 'CREATE TABLE `z_prefixRuleName_psmj_groups_001_history` (
  `id_prefixRuleName_psmj_groups_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Address` text COLLATE utf8_unicode_ci,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_psmj_groups_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','customers','contact','0','0','prefixRuleName_psmj_customer','prefixRuleName_psmj_customer','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','Email') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', "INSERT INTO `RuleFilters` (`rule_id`,`rfi_target`,`rfi_type`,`rfi_value`) VALUES ('idRule','email','content','@') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','Email','email',''),
('idRule','Name','lastname',''),
('idRule','nom','lastname',''),
('idRule','prÃ©nom','firstname','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', 'CREATE TABLE `z_prefixRuleName_psmj_customer_001_source` (
  `id_prefixRuleName_psmj_customer_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_psmj_customer_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', 'CREATE TABLE `z_prefixRuleName_psmj_customer_001_target` (
  `id_prefixRuleName_psmj_customer_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nom` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `prÃ©nom` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_psmj_customer_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', 'CREATE TABLE `z_prefixRuleName_psmj_customer_001_history` (
  `id_prefixRuleName_psmj_customer_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nom` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `prÃ©nom` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_psmj_customer_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','groups_customers','listrecipient','0','0','prefixRuleName_psmj_rel_group_customer','prefixRuleName_psmj_rel_group_customer','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','customer_id','ContactID','#BEG#psmj_customer#END#') ");
INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','group_id','ListID','#BEG#psmj_groups#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', 'CREATE TABLE `z_prefixRuleName_psmj_rel_group_customer_001_source` (
  `id_prefixRuleName_psmj_rel_group_customer_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `customer_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `group_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_psmj_rel_group_customer_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', 'CREATE TABLE `z_prefixRuleName_psmj_rel_group_customer_001_target` (
  `id_prefixRuleName_psmj_rel_group_customer_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ContactID` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ListID` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_psmj_rel_group_customer_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54565521728d0', 'CREATE TABLE `z_prefixRuleName_psmj_rel_group_customer_001_history` (
  `id_prefixRuleName_psmj_rel_group_customer_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ContactID` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ListID` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_psmj_rel_group_customer_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

