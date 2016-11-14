INSERT INTO Template VALUES ('5408e8747aeb8','3','2');

INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('FR','5408e8747aeb8','tpl1TwSf','Envoi des followers de Twitter en contact dans Salesforce.');
INSERT INTO TemplateLang (`tpll_lang`, `tplt_id`, `tpll_name`, `tpll_description`) VALUES ('EN','5408e8747aeb8','tpl1TwSf','Sending of followers from Twitter to contact in Salesforce.');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5408e8747aeb8', "INSERT INTO `Rule` (`rule_id`,`conn_id_source`,`conn_id_target`,`rule_date_created`,`rule_date_modified`,`rule_created_by`,`rule_modified_by`,`rule_module_source`,`rule_module_target`,`rule_active`,`rule_deleted`,`rule_name`,`rule_name_slug`,`rule_version`) VALUES ('idRule','idConnectorSource','idConnectorTarget',NOW(),NOW(),'idUser','idUser','Followers','Contact','0','0','prefixRuleName_twsf_followers','prefixRuleName_twsf_followers','001') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5408e8747aeb8', "INSERT INTO `RuleParams` (`rule_id`,`rulep_name`,`rulep_value`) VALUES ('idRule','rate','5'),
('idRule','delete','60'),
('idRule','mode','0'),
('idRule','datereference',CONCAT( CURDATE( ),' 00:00:00' )),
('idRule','duplicate_fields','LastName') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5408e8747aeb8', "INSERT INTO `RuleFields` (`rule_id`,`rulef_target_field_name`,`rulef_source_field_name`,`rulef_formula`) VALUES ('idRule','LastName','screen_name',''),
('idRule','Description','followers_count;friends_count;statuses_count','\"Friends: \".{friends_count}. \" Followers: \".{followers_count}. \" Tweet: \".{statuses_count}') ");

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5408e8747aeb8', 'CREATE TABLE `z_prefixRuleName_twsf_followers_001_source` (
  `id_prefixRuleName_twsf_followers_001_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `screen_name` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `followers_count` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `friends_count` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `statuses_count` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_twsf_followers_001_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5408e8747aeb8', 'CREATE TABLE `z_prefixRuleName_twsf_followers_001_target` (
  `id_prefixRuleName_twsf_followers_001_target` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_twsf_followers_001_target`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

INSERT INTO `TemplateQuery` (`tplt_id`, `tplq_query`) VALUES ('5408e8747aeb8', 'CREATE TABLE `z_prefixRuleName_twsf_followers_001_history` (
  `id_prefixRuleName_twsf_followers_001_history` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` varchar(684) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id_prefixRuleName_twsf_followers_001_history`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8;');

