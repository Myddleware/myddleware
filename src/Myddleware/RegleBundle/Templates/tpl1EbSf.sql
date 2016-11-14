INSERT INTO Template VALUES ('54085f5f631d5','8','2');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','54085f5f631d5','tpl1EbSf','Envoi des événements et participants EventBrite dans les événements et contacts de Salesforce.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','54085f5f631d5','tpl1EbSf','Sending EventBrite events and participants to events and contacts in Salesforce.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Events','Event','0','0','prefixRuleName_ebsf_events','prefixRuleName_ebsf_events','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','Description','description','striptags({description})'),
('idRule','DurationInMinutes','capacity',''),
('idRule','EndDateTime','end_date','changeFormatDate( changeTimeZone( {end_date},\"Europe/Paris\",\"UTC\"),\"Y-m-d\\\\TH:i:s\")'),
('idRule','StartDateTime','start_date','changeFormatDate( changeTimeZone( {start_date},\"Europe/Paris\",\"UTC\"),\"Y-m-d\\\\TH:i:s\")'),
('idRule','Subject','title','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', 'CREATE TABLE `z_prefixRuleName_ebsf_events_001_source` (
  `id_prefixRuleName_ebsf_events_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `capacity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `end_date` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `start_date` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsf_events_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', 'CREATE TABLE `z_prefixRuleName_ebsf_events_001_target` (
  `id_prefixRuleName_ebsf_events_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DurationInMinutes` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EndDateTime` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StartDateTime` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Subject` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsf_events_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', 'CREATE TABLE `z_prefixRuleName_ebsf_events_001_history` (
  `id_prefixRuleName_ebsf_events_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `DurationInMinutes` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `EndDateTime` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `StartDateTime` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Subject` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsf_events_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Attendees','Contact','0','0','prefixRuleName_ebsf_contact','prefixRuleName_ebsf_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','Email') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','LastName','last_name',''),
('idRule','Birthdate','birth_date',''),
('idRule','Description','notes',''),
('idRule','Email','email',''),
('idRule','FirstName','first_name',''),
('idRule','MailingCity','ship_city',''),
('idRule','MailingCountry','ship_country',''),
('idRule','MailingPostalCode','ship_postal_code',''),
('idRule','MailingStreet','ship_address;ship_address_2','{ship_address}.{ship_address_2}'),
('idRule','Title','job_title','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', 'CREATE TABLE `z_prefixRuleName_ebsf_contact_001_source` (
  `id_prefixRuleName_ebsf_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birth_date` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `notes` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ship_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ship_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ship_postal_code` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ship_address` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ship_address_2` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `job_title` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsf_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', 'CREATE TABLE `z_prefixRuleName_ebsf_contact_001_target` (
  `id_prefixRuleName_ebsf_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FirstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingCity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingCountry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingPostalCode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingStreet` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Title` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsf_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', 'CREATE TABLE `z_prefixRuleName_ebsf_contact_001_history` (
  `id_prefixRuleName_ebsf_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Email` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FirstName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingCity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingCountry` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingPostalCode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MailingStreet` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Title` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsf_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Attendees','EventRelation','0','0','prefixRuleName_ebsf_rel_events','prefixRuleName_ebsf_rel_events','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','event_id','EventId','#BEG#ebsf_events#END#') ");
INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','Myddleware_element_id','RelationId','#BEG#ebsf_contact#END#') ");


INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', 'CREATE TABLE `z_prefixRuleName_ebsf_rel_events_001_source` (
  `id_prefixRuleName_ebsf_rel_events_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `event_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Myddleware_element_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsf_rel_events_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', 'CREATE TABLE `z_prefixRuleName_ebsf_rel_events_001_target` (
  `id_prefixRuleName_ebsf_rel_events_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `EventId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `RelationId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsf_rel_events_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('54085f5f631d5', 'CREATE TABLE `z_prefixRuleName_ebsf_rel_events_001_history` (
  `id_prefixRuleName_ebsf_rel_events_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `EventId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `RelationId` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsf_rel_events_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

