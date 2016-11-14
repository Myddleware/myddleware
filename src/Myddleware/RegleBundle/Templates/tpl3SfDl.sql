INSERT INTO Template VALUES ('5409b7d202091','2','6');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','5409b7d202091','tpl3SfDl','Envoi des campagnes Salesforce avec leurs membres de type piste et contact vers les listes statiques et contacts de Dolist.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','5409b7d202091','tpl3SfDl','Sending of Salesforce campaigns with their members type lead and contact to static lists and contacts of Dolist.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Campaign','StaticSegmentHeader','0','0','prefixRuleName_sfdo_campaign','prefixRuleName_sfdo_campaign','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','ImportName','Name',''),
('idRule','NumberOfTargets','NumberOfContacts;NumberOfLeads','{NumberOfContacts}+{NumberOfLeads}'),
('idRule','ReportAddresse','my_value','\"ReportAddresse@email.com\"') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_campaign_001_source` (
  `id_prefixRuleName_sfdo_campaign_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `NumberOfContacts` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `NumberOfLeads` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_campaign_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_campaign_001_target` (
  `id_prefixRuleName_sfdo_campaign_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ImportName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `NumberOfTargets` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ReportAddresse` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_campaign_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_campaign_001_history` (
  `id_prefixRuleName_sfdo_campaign_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ImportName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `NumberOfTargets` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ReportAddresse` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_campaign_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Contact','contactCible','0','0','prefixRuleName_sfdo_contacts','prefixRuleName_sfdo_contacts','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `RuleFilters` (`rule_id`,`rfi_target`,`rfi_type`,`rfi_value`) VALUES ('idRule','Email','content','@') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','email','Email',''),
('idRule','firstname','FirstName',''),
('idRule','lastname','LastName','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_contacts_001_source` (
  `id_prefixRuleName_sfdo_contacts_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FirstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_contacts_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_contacts_001_target` (
  `id_prefixRuleName_sfdo_contacts_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_contacts_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_contacts_001_history` (
  `id_prefixRuleName_sfdo_contacts_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_contacts_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Lead','contactCible','0','0','prefixRuleName_sfdo_pistes','prefixRuleName_sfdo_pistes','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `RuleFilters` (`rule_id`,`rfi_target`,`rfi_type`,`rfi_value`) VALUES ('idRule','Email','content','@') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','email','Email',''),
('idRule','firstname','FirstName',''),
('idRule','lastname','LastName','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_pistes_001_source` (
  `id_prefixRuleName_sfdo_pistes_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FirstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_pistes_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_pistes_001_target` (
  `id_prefixRuleName_sfdo_pistes_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_pistes_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_pistes_001_history` (
  `id_prefixRuleName_sfdo_pistes_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_pistes_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','CampaignMember','StaticSegmentBody','0','0','prefixRuleName_sfdo_members_contact','prefixRuleName_sfdo_members_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','C'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','ContactId','ContactID','#BEG#sfdo_contacts#END#') ");
INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','CampaignId','FileName','#BEG#sfdo_campaign#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_members_contact_001_source` (
  `id_prefixRuleName_sfdo_members_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ContactId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CampaignId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_members_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_members_contact_001_target` (
  `id_prefixRuleName_sfdo_members_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ContactID` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FileName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_members_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_members_contact_001_history` (
  `id_prefixRuleName_sfdo_members_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ContactID` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FileName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_members_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','CampaignMember','StaticSegmentBody','0','0','prefixRuleName_sfdo_members_piste','prefixRuleName_sfdo_members_piste','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','C'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','LeadId','ContactID','#BEG#sfdo_pistes#END#') ");
INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','CampaignId','FileName','#BEG#sfdo_campaign#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_members_piste_001_source` (
  `id_prefixRuleName_sfdo_members_piste_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LeadId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `CampaignId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_members_piste_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_members_piste_001_target` (
  `id_prefixRuleName_sfdo_members_piste_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ContactID` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FileName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_members_piste_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409b7d202091', 'CREATE TABLE `z_prefixRuleName_sfdo_members_piste_001_history` (
  `id_prefixRuleName_sfdo_members_piste_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ContactID` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FileName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_sfdo_members_piste_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

