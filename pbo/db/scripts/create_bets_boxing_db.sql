CREATE DATABASE `bets_boxing` /*!40100 DEFAULT CHARACTER SET latin1 */;

CREATE TABLE  `bets_boxing`.`alerts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(45) NOT NULL DEFAULT '',
  `fight_id` int(10) unsigned NOT NULL DEFAULT '0',
  `fighter` int(10) unsigned NOT NULL DEFAULT '0',
  `bookie_id` int(11) NOT NULL DEFAULT '0',
  `odds` int(11) NOT NULL DEFAULT '0',
  `odds_type` int(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12920 DEFAULT CHARSET=latin1;

CREATE TABLE  `bets_boxing`.`bookies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` text,
  `refurl` text,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `position` int(10) unsigned NOT NULL DEFAULT '2',
  `date_added` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets_boxing`.`events` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `date` timestamp NULL default CURRENT_TIMESTAMP,
  `name` varchar(200) NOT NULL default '',
  `display` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets_boxing`.`fighters` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(85) NOT NULL default '',
  `url` text NOT NULL,
  PRIMARY KEY  USING BTREE (`id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets_boxing`.`fighters_altnames` (
  `fighter_id` int(11) DEFAULT NULL,
  `altname` varchar(100) DEFAULT NULL,
  KEY `INDEX` (`fighter_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

CREATE TABLE  `bets_boxing`.`fightodds` (
  `fight_id` int(10) unsigned NOT NULL default '0',
  `fighter1_odds` int(11) NOT NULL default '0',
  `fighter2_odds` int(11) NOT NULL default '0',
  `bookie_id` int(10) unsigned NOT NULL default '0',
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  USING BTREE (`fight_id`,`bookie_id`,`date`,`fighter1_odds`,`fighter2_odds`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `bets_boxing`.`fights` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fighter1_id` int(10) unsigned NOT NULL DEFAULT '0',
  `fighter2_id` int(10) unsigned NOT NULL DEFAULT '0',
  `event_id` int(10) unsigned NOT NULL DEFAULT '0',
  `is_mainevent` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `event_index` (`event_id`) USING BTREE,
  KEY `fighter_id_index` (`fighter1_id`),
  KEY `fighter_id2_index` (`fighter2_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets_boxing`.`fight_twits` (
  `fight_id` int(10) unsigned NOT NULL,
  `twitdate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`fight_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets_boxing`.`sports` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(30) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `bets_boxing`.`bookies_changenums` (
  `bookie_id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `changenum` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`bookie_id`)
) ENGINE = MyISAM;

CREATE TABLE  `bets_boxing`.`bookies_proptemplates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bookie_id` int(10) unsigned NOT NULL,
  `template` varchar(300) NOT NULL,
  `prop_type` int(10) unsigned NOT NULL,
  `template_neg` varchar(300) NOT NULL,
  `fields_type` int(3) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

CREATE TABLE  `bets_boxing`.`bookies_parsers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bookie_id` int(10) unsigned NOT NULL,
  `parse_url` varchar(500) NOT NULL,
  `name` varchar(45) NOT NULL,
  `cn_inuse` tinyint(1) NOT NULL,
  `mockfile` varchar(45) NOT NULL,
  `cn_urlsuffix` varchar(45) NOT NULL,
  `cn_initial` VARCHAR(45) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets_boxing`.`prop_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `prop_desc` varchar(300) NOT NULL,
  `negprop_desc` varchar(300) NOT NULL,
  `is_eventprop` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

CREATE TABLE  `bets_boxing`.`lines_props` (
  `matchup_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bookie_id` int(10) unsigned NOT NULL DEFAULT '0',
  `prop_odds` int(11) NOT NULL DEFAULT '0',
  `negprop_odds` int(11) NOT NULL DEFAULT '0',
  `proptype_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `team_num` int(2) unsigned NOT NULL,
  PRIMARY KEY (`matchup_id`,`bookie_id`,`date`,`proptype_id`,`team_num`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets_boxing`.`alerts_exemptions` (
  `email` varchar(45) NOT NULL,
  PRIMARY KEY (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets_boxing`.`lines_correlations` (
  `correlation` varchar(200) NOT NULL,
  `bookie_id` int(10) unsigned NOT NULL,
  `matchup_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`correlation`,`bookie_id`,`matchup_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets_boxing`.`matchups_unmatched` (
  `matchup` varchar(350) NOT NULL,
  `bookie_id` int(10) unsigned NOT NULL DEFAULT '0',
  `log_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` int(2) unsigned NOT NULL DEFAULT '0',
  `metadata` VARCHAR(1000) NULL DEFAULT NULL,
  PRIMARY KEY (`matchup`,`bookie_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `bets_boxing`.`matchups_metadata` (
  `matchup_id` INT NOT NULL,
  `mattribute` VARCHAR(45) NOT NULL,
  `mvalue` VARCHAR(500) NOT NULL,
  `source_bookie_id` INT NOT NULL,
  PRIMARY KEY (`matchup_id`, `mattribute`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `bets_boxing`.`schedule_manualactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(2000) DEFAULT NULL,
  `type` int(2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=53 DEFAULT CHARSET=latin1;

CREATE TABLE `bets_boxing`.`lines_eventprops` (
  `event_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bookie_id` int(10) unsigned NOT NULL DEFAULT '0',
  `prop_odds` int(11) NOT NULL DEFAULT '0',
  `negprop_odds` int(11) NOT NULL DEFAULT '0',
  `proptype_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`,`bookie_id`,`date`,`proptype_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `bets_boxing`.`alerts_entries` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `oddstype` INT(1) NOT NULL,
  `criterias` VARCHAR(500) NOT NULL,
  PRIMARY KEY (`id`, `email`, `criterias`),
  UNIQUE INDEX `ix_entry` (`email`, `criterias`)
)
ENGINE=MyISAM DEFAULT 
CHARSET=latin1 
AUTO_INCREMENT=301
;

CREATE TABLE `bets_boxing`.`prop_types_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
)
ENGINE=MyISAM DEFAULT 
CHARSET=latin1;

CREATE TABLE `bets_boxing`.`logs_parseruns` (
  `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `parser_id` INT(3) NOT NULL,
  `bookie_id` INT(3) NOT NULL,
  `parsed_matchups` INT(4) NULL DEFAULT NULL,
  `matched_matchups` INT(4) NULL DEFAULT NULL,
  `parsed_props` INT(4) NULL DEFAULT NULL,
  `matched_props` INT(4) NULL DEFAULT NULL,
  `status` INT(2) NOT NULL,
  `url` VARCHAR(500) NULL DEFAULT NULL,
  `authoritative_run` TINYINT(1) NULL DEFAULT NULL,
  `mockfeed_used` TINYINT(1) NULL DEFAULT NULL,
  `mockfeed_file` VARCHAR(100) NULL DEFAULT NULL,
  PRIMARY KEY (`date`, `parser_id`, `bookie_id`)
)
ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `bets_boxing`.`matchups_fbposts` (
  `matchup_id` INT(7) NOT NULL DEFAULT '-1',
  `event_id` INT(7) NOT NULL DEFAULT '-1',
  `post_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `skipped` TINYINT(1) NOT NULL,
  PRIMARY KEY (`matchup_id`, `event_id`, `post_date`)
)
ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `bets_boxing`.`teams_twitterhandles` (
  `team_id` int(10) unsigned NOT NULL,
  `name` varchar(85) NOT NULL,
  PRIMARY KEY (`team_id`)
)
ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `bets_boxing`.`prop_categories` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`category_name` VARCHAR(50) NOT NULL COMMENT 'Technical name',
	`category_description` VARCHAR(200) NOT NULL COMMENT 'Describes the category in text',
	PRIMARY KEY (`id`)
)
ENGINE=MyISAM DEFAULT CHARSET=latin1;
;

CREATE TABLE `bets_boxing`.`prop_type_category` (
	`proptype_id` INT(11) NOT NULL,
	`category_id` INT(11) NOT NULL,
	PRIMARY KEY (`proptype_id`, `category_id`)
)
ENGINE=MyISAM DEFAULT CHARSET=latin1;
;


CREATE TABLE `bets_boxing`.`lines_flagged` (
  `bookie_id` int NOT NULL,
  `matchup_id` int NOT NULL,
  `event_id` int NOT NULL,
  `proptype_id` int NOT NULL,
  `team_num` int NOT NULL,
  `initial_flagdate` timestamp NULL DEFAULT NULL,
  `last_flagdate` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`bookie_id`,`matchup_id`,`event_id`,`proptype_id`,`team_num`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;





ALTER TABLE bets_boxing.events ADD FULLTEXT(name);
ALTER TABLE bets_boxing.fighters ADD FULLTEXT(name);


INSERT INTO bets_boxing.sports(id, name) VALUES
(1, 'Boxing');


USE `bets_boxing`;
DROP function IF EXISTS `MoneylineToDecimal`;

DELIMITER $$
USE `bets_boxing`$$
CREATE FUNCTION `MoneylineToDecimal`(moneyline INT) RETURNS float
BEGIN
  DECLARE decimalval FLOAT;
  IF moneyline = 100 THEN
    SET decimalval = 2.0;
  ELSEIF moneyline > 0 THEN
    SET decimalval = ROUND((moneyline / 100) + 1, 5) ;
  ELSEIF moneyline < 0 THEN
    SET decimalval = ROUND((100 / ABS(moneyline)) + 1, 5);
  END IF;
RETURN decimalval;
END$$

DELIMITER ;


INSERT INTO bets_boxing.events VALUES (197, '2030-12-31 00:00:00', 'Future Events',1);


ALTER TABLE lines_props ADD KEY ix1(matchup_id, proptype_id, bookie_id, team_num, date);