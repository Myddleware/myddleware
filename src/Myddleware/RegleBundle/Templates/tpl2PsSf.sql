INSERT INTO Template VALUES ('542d716e625fa','5','2');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','542d716e625fa','tpl2PsSf','Compatible Prestashop 1.4 : Envoi des clients et commandes Prestashop vers les contacts et opportunités dans Salesforce.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','542d716e625fa','tpl2PsSf','Compatible Prestashop 1.4 : Sending Prestashop customers and orders to contacts and opportunities in Salesforce');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','customers','Contact','0','0','prefixRuleName_pssf_contact','prefixRuleName_pssf_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','LastName') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','LastName','lastname',''),
('idRule','Birthdate','birthday',''),
('idRule','Email','email',''),
('idRule','FirstName','firstname','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', 'CREATE TABLE `z_prefixRuleName_pssf_contact_001_source` (
  `id_prefixRuleName_pssf_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `lastname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthday` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssf_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', 'CREATE TABLE `z_prefixRuleName_pssf_contact_001_target` (
  `id_prefixRuleName_pssf_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FirstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssf_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', 'CREATE TABLE `z_prefixRuleName_pssf_contact_001_history` (
  `id_prefixRuleName_pssf_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FirstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssf_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','orders','Opportunity','0','0','prefixRuleName_pssf_order','prefixRuleName_pssf_order','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','CloseDate','delivery_date','(({delivery_date} == \"0000-00-00 00:00:00\") ? changeFormatDate( changeTimeZone( date( \"Y-m-d\\\\TH:i:s\"),\"Europe/Paris\",\"UTC\"),\"Y-m-d\\\\TH:i:s\") : changeFormatDate( changeTimeZone( {delivery_date},\"Europe/Paris\",\"UTC\"),\"Y-m-d\\\\TH:i:s\") )'),
('idRule','StageName','current_state','({current_state} == \"2\" ? \"Closed Won\" : ({current_state} == \"5\" ? \"Closed Won\" : ({current_state} == \"6\" ? \"Closed Lost\" : ({current_state} == \"7\" ? \"Closed Lost\" : ({current_state} == \"8\" ? \"Needs Analysis\" : ({current_state} == \"12\" ? \"Closed Won\" : \"Proposal/Price Quote\" ))))))'),
('idRule','Amount','total_paid',''),
('idRule','Name','my_value','\"Commande Presashop\"') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', 'CREATE TABLE `z_prefixRuleName_pssf_order_001_source` (
  `id_prefixRuleName_pssf_order_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `delivery_date` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `current_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `total_paid` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssf_order_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', 'CREATE TABLE `z_prefixRuleName_pssf_order_001_target` (
  `id_prefixRuleName_pssf_order_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `CloseDate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StageName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Amount` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssf_order_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', 'CREATE TABLE `z_prefixRuleName_pssf_order_001_history` (
  `id_prefixRuleName_pssf_order_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `CloseDate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StageName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Amount` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssf_order_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','orders','OpportunityContactRole','0','0','prefixRuleName_pssf_1_4_rel_contact_order','prefixRuleName_pssf_1_4_rel_contact_order','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','id_customer','ContactId','#BEG#pssf_contact#END#') ");
INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','Myddleware_element_id','OpportunityId','#BEG#pssf_order#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', 'CREATE TABLE `z_prefixRuleName_pssf_1_4_rel_contact_order_001_source` (
  `id_prefixRuleName_pssf_1_4_rel_contact_order_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `id_customer` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Myddleware_element_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssf_1_4_rel_contact_order_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', 'CREATE TABLE `z_prefixRuleName_pssf_1_4_rel_contact_order_001_target` (
  `id_prefixRuleName_pssf_1_4_rel_contact_order_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ContactId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OpportunityId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssf_1_4_rel_contact_order_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('542d716e625fa', 'CREATE TABLE `z_prefixRuleName_pssf_1_4_rel_contact_order_001_history` (
  `id_prefixRuleName_pssf_1_4_rel_contact_order_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ContactId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OpportunityId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssf_1_4_rel_contact_order_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

