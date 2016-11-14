INSERT INTO Template VALUES ('542d703f00f90','5','1');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','542d703f00f90','tpl2PsSg','Compatible Prestashop 1.4 : Envoi des clients et commandes Prestashop vers les contacts et opportunités dans SugarCRM.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','542d703f00f90','tpl2PsSg','Compatible Prestashop 1.4 : Sending Prestashop customers and orders to contacts and opportunities in SugarCRM.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','customers','Contacts','0','0','prefixRuleName_pssg_contact','prefixRuleName_pssg_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','last_name') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','last_name','lastname',''),
('idRule','birthdate','birthday',''),
('idRule','email1','email',''),
('idRule','first_name','firstname','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', 'CREATE TABLE `z_prefixRuleName_pssg_contact_001_source` (
  `id_prefixRuleName_pssg_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `lastname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthday` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', 'CREATE TABLE `z_prefixRuleName_pssg_contact_001_target` (
  `id_prefixRuleName_pssg_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', 'CREATE TABLE `z_prefixRuleName_pssg_contact_001_history` (
  `id_prefixRuleName_pssg_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','orders','Opportunities','0','0','prefixRuleName_pssg_order','prefixRuleName_pssg_order','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','amount','total_paid',''),
('idRule','date_closed','delivery_date','(({delivery_date} == \"0000-00-00 00:00:00\") ? date( \"Y-m-d H:i:s\") : {delivery_date})'),
('idRule','sales_stage','current_state','({current_state} == \"2\" ? \"Closed Won\" : ({current_state} == \"5\" ? \"Closed Won\" : ({current_state} == \"6\" ? \"Closed Lost\" : ({current_state} == \"7\" ? \"Closed Lost\" : ({current_state} == \"8\" ? \"Needs Analysis\" : ({current_state} == \"12\" ? \"Closed Won\" : \"Proposal/Price Quote\" ))))))'),
('idRule','name','my_value','\"Commande Prestashop\"') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', 'CREATE TABLE `z_prefixRuleName_pssg_order_001_source` (
  `id_prefixRuleName_pssg_order_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `total_paid` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `delivery_date` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `current_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_order_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', 'CREATE TABLE `z_prefixRuleName_pssg_order_001_target` (
  `id_prefixRuleName_pssg_order_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `amount` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_closed` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sales_stage` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_order_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', 'CREATE TABLE `z_prefixRuleName_pssg_order_001_history` (
  `id_prefixRuleName_pssg_order_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `amount` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_closed` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sales_stage` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_order_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','orders','opportunities_contacts','0','0','prefixRuleName_pssg_rel_contact_order','prefixRuleName_pssg_rel_contact_order','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','C'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','id_customer','contact_id','#BEG#pssg_contact#END#') ");
INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','Myddleware_element_id','opportunity_id','#BEG#pssg_order#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', 'CREATE TABLE `z_prefixRuleName_pssg_rel_contact_order_001_source` (
  `id_prefixRuleName_pssg_rel_contact_order_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `id_customer` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Myddleware_element_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_rel_contact_order_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', 'CREATE TABLE `z_prefixRuleName_pssg_rel_contact_order_001_target` (
  `id_prefixRuleName_pssg_rel_contact_order_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contact_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opportunity_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_rel_contact_order_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d703f00f90', 'CREATE TABLE `z_prefixRuleName_pssg_rel_contact_order_001_history` (
  `id_prefixRuleName_pssg_rel_contact_order_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contact_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opportunity_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_rel_contact_order_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

