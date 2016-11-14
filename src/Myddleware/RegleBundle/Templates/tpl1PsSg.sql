INSERT INTO Template VALUES ('5409a7582d353','5','1');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','5409a7582d353','tpl1PsSg','Compatible Prestashop 1.5 et Prestashop 1.6 : Envoi des clients, commandes et messages Prestashop vers les comptes, contacts, opportunités et ticket dans SugarCRM.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','5409a7582d353','tpl1PsSg','Compatible Prestashop 1.5 et Prestashop 1.6: Sending Prestashop clients, orders and messages to accounts, contacts, opportunities and ticket in SugarCRM.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','customers','Accounts','0','0','prefixRuleName_pssg_account','prefixRuleName_pssg_account','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','name') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','name','company;firstname;lastname','upper( (({company} == \"\") ?{firstname}.\" \".{lastname} : {company} ))') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_account_001_source` (
  `id_prefixRuleName_pssg_account_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `company` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_account_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_account_001_target` (
  `id_prefixRuleName_pssg_account_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_account_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_account_001_history` (
  `id_prefixRuleName_pssg_account_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_account_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','customers','Contacts','0','0','prefixRuleName_pssg_contact','prefixRuleName_pssg_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','last_name') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','Myddleware_element_id','account_id','#BEG#pssg_account#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','last_name','lastname',''),
('idRule','birthdate','birthday',''),
('idRule','email1','email',''),
('idRule','first_name','firstname','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_contact_001_source` (
  `id_prefixRuleName_pssg_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `lastname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthday` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Myddleware_element_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_contact_001_target` (
  `id_prefixRuleName_pssg_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `account_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_contact_001_history` (
  `id_prefixRuleName_pssg_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `account_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','orders','Opportunities','0','0','prefixRuleName_pssg_order','prefixRuleName_pssg_order','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','id_customer','account_id','#BEG#pssg_account#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','amount','total_paid',''),
('idRule','date_closed','date_add',''),
('idRule','name','reference',''),
('idRule','sales_stage','current_state','({current_state} == \"2\" ? \"Closed Won\" : ({current_state} == \"5\" ? \"Closed Won\" : ({current_state} == \"6\" ? \"Closed Lost\" : ({current_state} == \"7\" ? \"Closed Lost\" : ({current_state} == \"8\" ? \"Needs Analysis\" : ({current_state} == \"12\" ? \"Closed Won\" : \"Proposal/Price Quote\" ))))))') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_order_001_source` (
  `id_prefixRuleName_pssg_order_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `total_paid` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_add` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reference` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `current_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id_customer` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_order_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_order_001_target` (
  `id_prefixRuleName_pssg_order_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `amount` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_closed` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sales_stage` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `account_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_order_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_order_001_history` (
  `id_prefixRuleName_pssg_order_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `amount` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_closed` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sales_stage` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `account_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_order_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','orders','opportunities_contacts','0','0','prefixRuleName_pssg_rel_contact_order','prefixRuleName_pssg_rel_contact_order','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','mode','C'),
('idRule','delete','60'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','id_customer','contact_id','#BEG#pssg_contact#END#') ");
INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','Myddleware_element_id','opportunity_id','#BEG#pssg_order#END#') ");


INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_rel_contact_order_001_source` (
  `id_prefixRuleName_pssg_rel_contact_order_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `id_customer` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Myddleware_element_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_rel_contact_order_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_rel_contact_order_001_target` (
  `id_prefixRuleName_pssg_rel_contact_order_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contact_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opportunity_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_rel_contact_order_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_rel_contact_order_001_history` (
  `id_prefixRuleName_pssg_rel_contact_order_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contact_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opportunity_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_rel_contact_order_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','customer_threads','Cases','0','0','prefixRuleName_pssg_message','prefixRuleName_pssg_message','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','id_customer','account_id','#BEG#pssg_account#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','name','email','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_message_001_source` (
  `id_prefixRuleName_pssg_message_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id_customer` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_message_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_message_001_target` (
  `id_prefixRuleName_pssg_message_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `account_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_message_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_message_001_history` (
  `id_prefixRuleName_pssg_message_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `account_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_message_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','customer_messages','Notes','0','0','prefixRuleName_pssg_note','prefixRuleName_pssg_note','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','id_customer_thread','parent_id','#BEG#pssg_message#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','name','message',''),
('idRule','description','message',''),
('idRule','portal_flag','my_value','\"1\"'),
('idRule','parent_type','my_value','\"Cases\"') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_note_001_source` (
  `id_prefixRuleName_pssg_note_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `message` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id_customer_thread` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_note_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_note_001_target` (
  `id_prefixRuleName_pssg_note_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `portal_flag` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `parent_type` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `parent_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_note_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409a7582d353', 'CREATE TABLE `z_prefixRuleName_pssg_note_001_history` (
  `id_prefixRuleName_pssg_note_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `portal_flag` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `parent_type` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `parent_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_pssg_note_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

