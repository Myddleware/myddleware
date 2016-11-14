INSERT INTO Template VALUES ('5404a71d17d8c','2','1');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','5404a71d17d8c','tpl1SfSg','Synchronisation des comptes, contacts et opportunités de Salesforce vers SugarCRM.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','5404a71d17d8c','tpl1SfSg','Synchronising accounts, contacts and opportunities from Salesforce to SugarCRM.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Account','Accounts','0','0','prefixRuleName_SfSg_compte','prefixRuleName_SfSg_compte','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','name') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','name','Name',''),
('idRule','annual_revenue','AnnualRevenue',''),
('idRule','billing_address_city','BillingCity',''),
('idRule','billing_address_country','BillingCountry',''),
('idRule','billing_address_postalcode','BillingPostalCode',''),
('idRule','billing_address_state','BillingState',''),
('idRule','billing_address_street','BillingStreet',''),
('idRule','description','Description',''),
('idRule','industry','Industry',''),
('idRule','phone_fax','Fax',''),
('idRule','phone_office','Phone',''),
('idRule','shipping_address_city','ShippingCity',''),
('idRule','shipping_address_country','ShippingCountry',''),
('idRule','shipping_address_postalcode','ShippingPostalCode',''),
('idRule','shipping_address_state','ShippingState',''),
('idRule','shipping_address_street','ShippingStreet',''),
('idRule','sic_code','Sic',''),
('idRule','website','Website','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', 'CREATE TABLE `z_prefixRuleName_SfSg_compte_001_source` (
  `id_prefixRuleName_SfSg_compte_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `AnnualRevenue` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingCity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingCountry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingPostalCode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingState` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingStreet` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Industry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Fax` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Phone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingCity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingCountry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingPostalCode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingState` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingStreet` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Sic` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Website` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfSg_compte_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', 'CREATE TABLE `z_prefixRuleName_SfSg_compte_001_target` (
  `id_prefixRuleName_SfSg_compte_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `annual_revenue` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_postalcode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_street` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `industry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_fax` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_office` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_postalcode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_street` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sic_code` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `website` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfSg_compte_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', 'CREATE TABLE `z_prefixRuleName_SfSg_compte_001_history` (
  `id_prefixRuleName_SfSg_compte_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `annual_revenue` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_postalcode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_street` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `industry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_fax` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_office` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_postalcode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_street` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sic_code` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `website` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfSg_compte_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Contact','Contacts','0','0','prefixRuleName_SfSg_contact','prefixRuleName_SfSg_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','email1') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','AccountId','account_id','#BEG#SfSg_compte#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','last_name','LastName',''),
('idRule','alt_address_city','OtherCity',''),
('idRule','alt_address_country','OtherCountry',''),
('idRule','alt_address_postalcode','OtherPostalCode',''),
('idRule','alt_address_state','OtherState',''),
('idRule','alt_address_street','OtherStreet',''),
('idRule','birthdate','Birthdate',''),
('idRule','description','Description',''),
('idRule','email1','Email',''),
('idRule','first_name','FirstName',''),
('idRule','phone_fax','Fax',''),
('idRule','phone_home','HomePhone',''),
('idRule','phone_mobile','MobilePhone',''),
('idRule','phone_other','OtherPhone',''),
('idRule','phone_work','Phone',''),
('idRule','primary_address_city','MailingCity',''),
('idRule','primary_address_country','MailingCountry',''),
('idRule','primary_address_postalcode','MailingPostalCode',''),
('idRule','primary_address_state','MailingState',''),
('idRule','primary_address_street','MailingStreet',''),
('idRule','salutation','Salutation',''),
('idRule','title','Title','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', 'CREATE TABLE `z_prefixRuleName_SfSg_contact_001_source` (
  `id_prefixRuleName_SfSg_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherCity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherCountry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherPostalCode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherState` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherStreet` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FirstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Fax` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `HomePhone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MobilePhone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherPhone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Phone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingCity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingCountry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingPostalCode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingState` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingStreet` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Salutation` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Title` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `AccountId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfSg_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', 'CREATE TABLE `z_prefixRuleName_SfSg_contact_001_target` (
  `id_prefixRuleName_SfSg_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_postalcode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_street` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_fax` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_home` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_mobile` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_other` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_work` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_postalcode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_street` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `salutation` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `account_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfSg_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', 'CREATE TABLE `z_prefixRuleName_SfSg_contact_001_history` (
  `id_prefixRuleName_SfSg_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_postalcode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_street` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_fax` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_home` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_mobile` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_other` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_work` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_postalcode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_street` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `salutation` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `account_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfSg_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Opportunity','Opportunities','0','0','prefixRuleName_SfSg_opportunite','prefixRuleName_SfSg_opportunite','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','AccountId','account_id','#BEG#SfSg_compte#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','amount','Amount',''),
('idRule','date_closed','CloseDate',''),
('idRule','name','Name',''),
('idRule','sales_stage','StageName',''),
('idRule','description','Description','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', 'CREATE TABLE `z_prefixRuleName_SfSg_opportunite_001_source` (
  `id_prefixRuleName_SfSg_opportunite_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Amount` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CloseDate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StageName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `AccountId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfSg_opportunite_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', 'CREATE TABLE `z_prefixRuleName_SfSg_opportunite_001_target` (
  `id_prefixRuleName_SfSg_opportunite_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `amount` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_closed` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sales_stage` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `account_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfSg_opportunite_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', 'CREATE TABLE `z_prefixRuleName_SfSg_opportunite_001_history` (
  `id_prefixRuleName_SfSg_opportunite_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `amount` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_closed` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sales_stage` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `account_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfSg_opportunite_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','OpportunityContactRole','opportunities_contacts','0','0','prefixRuleName_SfSg_relContOppt','prefixRuleName_SfSg_relContOppt','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','ContactId','contact_id','#BEG#SfSg_contact#END#') ");
INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','OpportunityId','opportunity_id','#BEG#SfSg_opportunite#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', 'CREATE TABLE `z_prefixRuleName_SfSg_relContOppt_001_source` (
  `id_prefixRuleName_SfSg_relContOppt_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ContactId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OpportunityId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfSg_relContOppt_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', 'CREATE TABLE `z_prefixRuleName_SfSg_relContOppt_001_target` (
  `id_prefixRuleName_SfSg_relContOppt_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contact_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opportunity_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfSg_relContOppt_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404a71d17d8c', 'CREATE TABLE `z_prefixRuleName_SfSg_relContOppt_001_history` (
  `id_prefixRuleName_SfSg_relContOppt_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contact_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opportunity_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_SfSg_relContOppt_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

