INSERT INTO Template VALUES ('5404aac4879c7','11','2');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','5404aac4879c7','tpl1StSf','Synchronisation des comptes, contacts et opportunités de SuiteCRM vers Salesforce.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','5404aac4879c7','tpl1StSf','Synchronising accounts, contacts and opportunities from SuiteCRM to Salesforce.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Accounts','Account','0','0','prefixRuleName_StSf_compte','prefixRuleName_StSf_compte','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','Name') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','Name','name',''),
('idRule','AnnualRevenue','annual_revenue',''),
('idRule','BillingCity','billing_address_city',''),
('idRule','BillingCountry','billing_address_country',''),
('idRule','BillingPostalCode','billing_address_postalcode',''),
('idRule','BillingState','billing_address_state',''),
('idRule','BillingStreet','billing_address_street',''),
('idRule','Description','description',''),
('idRule','Fax','phone_fax',''),
('idRule','Industry','industry',''),
('idRule','Phone','phone_office',''),
('idRule','ShippingCity','shipping_address_city',''),
('idRule','ShippingCountry','shipping_address_country',''),
('idRule','ShippingPostalCode','shipping_address_postalcode',''),
('idRule','ShippingState','shipping_address_state',''),
('idRule','ShippingStreet','shipping_address_street',''),
('idRule','Sic','sic_code',''),
('idRule','Website','website','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', 'CREATE TABLE `z_prefixRuleName_StSf_compte_001_source` (
  `id_prefixRuleName_StSf_compte_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `annual_revenue` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_postalcode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `billing_address_street` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `phone_fax` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `industry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_office` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_postalcode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `shipping_address_street` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sic_code` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `website` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_StSf_compte_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', 'CREATE TABLE `z_prefixRuleName_StSf_compte_001_target` (
  `id_prefixRuleName_StSf_compte_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `AnnualRevenue` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingCity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingCountry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingPostalCode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingState` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingStreet` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Fax` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Industry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Phone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingCity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingCountry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingPostalCode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingState` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingStreet` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Sic` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Website` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_StSf_compte_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', 'CREATE TABLE `z_prefixRuleName_StSf_compte_001_history` (
  `id_prefixRuleName_StSf_compte_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `AnnualRevenue` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingCity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingCountry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingPostalCode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingState` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BillingStreet` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Fax` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Industry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Phone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingCity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingCountry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingPostalCode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingState` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ShippingStreet` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Sic` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Website` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_StSf_compte_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Contacts','Contact','0','0','prefixRuleName_StSf_contact','prefixRuleName_StSf_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','Email') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','account_id','AccountId','#BEG#StSf_compte#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','LastName','last_name',''),
('idRule','Birthdate','birthdate',''),
('idRule','Description','description',''),
('idRule','Email','email1',''),
('idRule','Fax','phone_fax',''),
('idRule','FirstName','first_name',''),
('idRule','HomePhone','phone_home',''),
('idRule','MailingCity','primary_address_city',''),
('idRule','MailingCountry','primary_address_country',''),
('idRule','MailingPostalCode','primary_address_postalcode',''),
('idRule','MailingState','primary_address_state',''),
('idRule','MailingStreet','primary_address_street',''),
('idRule','MobilePhone','phone_mobile',''),
('idRule','OtherCity','alt_address_city',''),
('idRule','OtherCountry','alt_address_country',''),
('idRule','OtherPhone','phone_other',''),
('idRule','OtherPostalCode','alt_address_postalcode',''),
('idRule','OtherState','alt_address_state',''),
('idRule','OtherStreet','alt_address_street',''),
('idRule','Phone','phone_work',''),
('idRule','Salutation','salutation',''),
('idRule','Title','title','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', 'CREATE TABLE `z_prefixRuleName_StSf_contact_001_source` (
  `id_prefixRuleName_StSf_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_fax` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_home` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_postalcode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_street` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_mobile` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_other` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_postalcode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_state` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `alt_address_street` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone_work` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `salutation` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `account_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_StSf_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', 'CREATE TABLE `z_prefixRuleName_StSf_contact_001_target` (
  `id_prefixRuleName_StSf_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Fax` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FirstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `HomePhone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingCity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingCountry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingPostalCode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingState` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingStreet` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MobilePhone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherCity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherCountry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherPhone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherPostalCode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherState` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherStreet` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Phone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Salutation` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Title` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `AccountId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_StSf_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', 'CREATE TABLE `z_prefixRuleName_StSf_contact_001_history` (
  `id_prefixRuleName_StSf_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Fax` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FirstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `HomePhone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingCity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingCountry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingPostalCode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingState` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingStreet` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MobilePhone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherCity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherCountry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherPhone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherPostalCode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherState` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OtherStreet` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Phone` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Salutation` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Title` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `AccountId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_StSf_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Opportunities','Opportunity','0','0','prefixRuleName_StSf_opportunite','prefixRuleName_StSf_opportunite','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','account_id','AccountId','#BEG#StSf_compte#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','CloseDate','date_closed',''),
('idRule','Name','name',''),
('idRule','StageName','sales_stage',''),
('idRule','Description','description','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', 'CREATE TABLE `z_prefixRuleName_StSf_opportunite_001_source` (
  `id_prefixRuleName_StSf_opportunite_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `date_closed` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sales_stage` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `account_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_StSf_opportunite_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', 'CREATE TABLE `z_prefixRuleName_StSf_opportunite_001_target` (
  `id_prefixRuleName_StSf_opportunite_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `CloseDate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StageName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `AccountId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_StSf_opportunite_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', 'CREATE TABLE `z_prefixRuleName_StSf_opportunite_001_history` (
  `id_prefixRuleName_StSf_opportunite_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `CloseDate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StageName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `AccountId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_StSf_opportunite_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','opportunities_contacts','OpportunityContactRole','0','0','prefixRuleName_StSf_relContOppt','prefixRuleName_StSf_relContOppt','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','contact_id','ContactId','#BEG#StSf_contact#END#') ");
INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','opportunity_id','OpportunityId','#BEG#StSf_opportunite#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', 'CREATE TABLE `z_prefixRuleName_StSf_relContOppt_001_source` (
  `id_prefixRuleName_StSf_relContOppt_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contact_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opportunity_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_StSf_relContOppt_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', 'CREATE TABLE `z_prefixRuleName_StSf_relContOppt_001_target` (
  `id_prefixRuleName_StSf_relContOppt_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ContactId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OpportunityId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_StSf_relContOppt_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404aac4879c7', 'CREATE TABLE `z_prefixRuleName_StSf_relContOppt_001_history` (
  `id_prefixRuleName_StSf_relContOppt_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ContactId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OpportunityId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_StSf_relContOppt_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

