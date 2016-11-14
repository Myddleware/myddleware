INSERT INTO Template VALUES ('5404acba05ee6','3','1');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','5404acba05ee6','tpl1TwSg','Envoi des followers de Twitter en contact dans SugarCRM.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','5404acba05ee6','tpl1TwSg','Sending followers from Twitter to contact in SugarCRM.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404acba05ee6', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Followers','Contacts','0','0','prefixRuleName_twsg_followers','prefixRuleName_twsg_followers','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404acba05ee6', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','last_name') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404acba05ee6', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','last_name','screen_name',''),
('idRule','description','followers_count;friends_count;statuses_count','\"Friends: \".{friends_count}. \" Followers: \".{followers_count}. \" Tweet: \".{statuses_count}') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404acba05ee6', 'CREATE TABLE `z_prefixRuleName_twsg_followers_001_source` (
  `id_prefixRuleName_twsg_followers_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `screen_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `followers_count` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `friends_count` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `statuses_count` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_twsg_followers_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404acba05ee6', 'CREATE TABLE `z_prefixRuleName_twsg_followers_001_target` (
  `id_prefixRuleName_twsg_followers_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id_prefixRuleName_twsg_followers_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5404acba05ee6', 'CREATE TABLE `z_prefixRuleName_twsg_followers_001_history` (
  `id_prefixRuleName_twsg_followers_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id_prefixRuleName_twsg_followers_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

