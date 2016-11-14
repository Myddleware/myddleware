INSERT INTO Template VALUES ('5409ddfc15e8a','8','1');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','5409ddfc15e8a','tpl1EbSg','Envoi des évènements et participant de Evenbrite vers les réunions et contacts de SugarCRM.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','5409ddfc15e8a','tpl1EbSg','Sending of events and participant from Evenbrite to meetings and contacts of SugarCRM.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Events','Meetings','0','0','prefixRuleName_ebsg_event','prefixRuleName_ebsg_event','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','date_start','start_date','changeTimeZone( {start_date},\"Europe/Paris\",\"UTC\")'),
('idRule','duration_hours','capacity',''),
('idRule','name','title',''),
('idRule','date_end','end_date','changeTimeZone({end_date},\"Europe/Paris\",\"UTC\")'),
('idRule','description','description','striptags({description})') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', 'CREATE TABLE `z_prefixRuleName_ebsg_event_001_source` (
  `id_prefixRuleName_ebsg_event_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `start_date` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `capacity` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `end_date` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsg_event_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', 'CREATE TABLE `z_prefixRuleName_ebsg_event_001_target` (
  `id_prefixRuleName_ebsg_event_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `date_start` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration_hours` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_end` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id_prefixRuleName_ebsg_event_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', 'CREATE TABLE `z_prefixRuleName_ebsg_event_001_history` (
  `id_prefixRuleName_ebsg_event_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `date_start` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration_hours` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_end` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id_prefixRuleName_ebsg_event_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Attendees','Contacts','0','0','prefixRuleName_ebsg_contacts','prefixRuleName_ebsg_contacts','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','email1') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','last_name','last_name',''),
('idRule','birthdate','birth_date',''),
('idRule','description','notes',''),
('idRule','email1','email',''),
('idRule','first_name','first_name',''),
('idRule','primary_address_city','ship_city',''),
('idRule','primary_address_country','ship_country',''),
('idRule','primary_address_postalcode','ship_postal_code',''),
('idRule','primary_address_street','ship_address',''),
('idRule','primary_address_street_2','ship_address_2',''),
('idRule','title','job_title','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', 'CREATE TABLE `z_prefixRuleName_ebsg_contacts_001_source` (
  `id_prefixRuleName_ebsg_contacts_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
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
  PRIMARY KEY (`id_prefixRuleName_ebsg_contacts_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', 'CREATE TABLE `z_prefixRuleName_ebsg_contacts_001_target` (
  `id_prefixRuleName_ebsg_contacts_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_postalcode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_street` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_street_2` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsg_contacts_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', 'CREATE TABLE `z_prefixRuleName_ebsg_contacts_001_history` (
  `id_prefixRuleName_ebsg_contacts_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birthdate` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `email1` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_city` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_country` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_postalcode` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_street` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `primary_address_street_2` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsg_contacts_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Attendees','meetings_contacts','0','0','prefixRuleName_ebsg_rel_event_contact','prefixRuleName_ebsg_rel_event_contact','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','mode','C'),
('idRule','delete','60'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )) ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','Myddleware_element_id','contact_id','#BEG#ebsg_contacts#END#') ");
INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','event_id','meeting_id','#BEG#ebsg_event#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', 'CREATE TABLE `z_prefixRuleName_ebsg_rel_event_contact_001_source` (
  `id_prefixRuleName_ebsg_rel_event_contact_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `Myddleware_element_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `event_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsg_rel_event_contact_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', 'CREATE TABLE `z_prefixRuleName_ebsg_rel_event_contact_001_target` (
  `id_prefixRuleName_ebsg_rel_event_contact_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contact_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `meeting_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsg_rel_event_contact_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5409ddfc15e8a', 'CREATE TABLE `z_prefixRuleName_ebsg_rel_event_contact_001_history` (
  `id_prefixRuleName_ebsg_rel_event_contact_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `contact_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `meeting_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_ebsg_rel_event_contact_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

