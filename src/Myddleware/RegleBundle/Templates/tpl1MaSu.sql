INSERT INTO Template VALUES ('53f8d318879e5','7','1');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','53f8d318879e5','tpl1MaSu','Bug et notes Mantis envoyés dans les tickets et notes dans SugarCRM. ATTENTION : Une fois générées, éditez les règles afin de renseigner le projet et le filtre correspondant à votre solution Mantis.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','53f8d318879e5','tpl1MaSu','Sending Mantis Bug and notes to tickets and notes in SugarCRM. CAUTION: once you have generated the rules, please edit them to inform the project and the filter which correspond to your Mantis solution.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('53f8d318879e5', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','issue','Cases','0','0','prefixRuleName_masg_bug','prefixRuleName_masg_bug','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('53f8d318879e5', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','name','summary',''),
('idRule','description','description',''),
('idRule','priority','priority','({priority} == \"10\" ? \"P3\" : ({priority} == \"20\" ? \"P3\" : ({priority} == \"30\" ? \"P2\" : \"P1\")))'),
('idRule','resolution','resolution','({resolution} == \"10\" ? \"Open\" : ({resolution} == \"20\" ? \"Fixed\" : ({resolution} == \"30\" ? \"Reopened\" : ({resolution} == \"40\" ? \"Unable to reproduce\" : ({resolution} == \"50\" ? \"Not fixable\" : ({resolution} == \"60\" ? \"Duplicate\" : ({resolution} == \"70\" ? \"No change required\" : ({resolution} == \"80\" ? \"Suspended\" : \"Will not fix\"))))))))'),
('idRule','status','status','({status} == \"10\" ? \"New\" : ({status} == \"20\" ? \"Assigned\" : ({status} == \"30\" ?  \"Assigned\" : ({status} == \"40\" ? \"Assigned\" : ({status} == \"50\" ? \"Assigned\" : \"Closed\")))))') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('53f8d318879e5', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','projectId',''),
('idRule','filterId','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('53f8d318879e5', 'CREATE TABLE `z_prefixRuleName_masg_bug_001_source` (
  `id_prefixRuleName_masg_bug_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `summary` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `priority` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resolution` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_masg_bug_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('53f8d318879e5', 'CREATE TABLE `z_prefixRuleName_masg_bug_001_target` (
  `id_prefixRuleName_masg_bug_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `priority` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resolution` text COLLATE utf8_unicode_ci,
  `status` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_masg_bug_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('53f8d318879e5', 'CREATE TABLE `z_prefixRuleName_masg_bug_001_history` (
  `id_prefixRuleName_masg_bug_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `priority` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `resolution` text COLLATE utf8_unicode_ci,
  `status` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_masg_bug_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('53f8d318879e5', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','note','Notes','0','0','prefixRuleName_masg_note','prefixRuleName_masg_note','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('53f8d318879e5', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','name','text',''),
('idRule','description','text',''),
('idRule','portal_flag','my_value','\"0\"'),
('idRule','parent_type','my_value','\"Cases\"') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('53f8d318879e5', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','projectId',''),
('idRule','filterId','') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('53f8d318879e5', "INSERT INTO `RuleRelationShips` (`rule_id`,`rrs_field_name_source`,`rrs_field_name_target`,`rrs_field_id`) VALUES ('idRule','issue','parent_id','#BEG#masg_bug#END#') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('53f8d318879e5', 'CREATE TABLE `z_prefixRuleName_masg_note_001_source` (
  `id_prefixRuleName_masg_note_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `text` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `issue` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_masg_note_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('53f8d318879e5', 'CREATE TABLE `z_prefixRuleName_masg_note_001_target` (
  `id_prefixRuleName_masg_note_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `portal_flag` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `parent_type` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `parent_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_masg_note_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('53f8d318879e5', 'CREATE TABLE `z_prefixRuleName_masg_note_001_history` (
  `id_prefixRuleName_masg_note_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `portal_flag` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `parent_type` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `parent_id` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_masg_note_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

