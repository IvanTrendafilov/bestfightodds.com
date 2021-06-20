CREATE DATABASE `bets` /*!40100 DEFAULT CHARACTER SET latin1 */;

CREATE TABLE `alerts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `fight_id` int unsigned NOT NULL DEFAULT '0',
  `fighter` int unsigned NOT NULL DEFAULT '0',
  `bookie_id` int NOT NULL DEFAULT '0',
  `odds` int NOT NULL DEFAULT '0',
  `odds_type` int unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=162299 DEFAULT CHARSET=latin1;


CREATE TABLE `alerts_exemptions` (
  `email` varchar(45) NOT NULL,
  PRIMARY KEY (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `bookies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `refurl` text,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `position` int unsigned NOT NULL DEFAULT '2',
  `date_added` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=latin1;


CREATE TABLE `bookies_changenums` (
  `bookie_id` int unsigned NOT NULL AUTO_INCREMENT,
  `changenum` varchar(45) NOT NULL DEFAULT '',
  `initial` varchar(45) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`bookie_id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=latin1;


CREATE TABLE `bookies_proptemplates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `bookie_id` int unsigned NOT NULL,
  `template` varchar(300) NOT NULL,
  `prop_type` int unsigned NOT NULL,
  `template_neg` varchar(300) NOT NULL,
  `fields_type` int unsigned NOT NULL,
  `last_used` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1346 DEFAULT CHARSET=latin1;


CREATE TABLE `events` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(200) NOT NULL DEFAULT '',
  `display` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=2207 DEFAULT CHARSET=latin1;


CREATE TABLE `fight_twits` (
  `fight_id` int unsigned NOT NULL DEFAULT '0',
  `twitdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`fight_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `fighters` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(85) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`,`name`) USING BTREE,
  FULLTEXT KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=11580 DEFAULT CHARSET=latin1;


CREATE TABLE `fighters_altnames` (
  `fighter_id` int DEFAULT NULL,
  `altname` varchar(100) DEFAULT NULL,
  KEY `INDEX` (`fighter_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `fightodds` (
  `fight_id` int unsigned NOT NULL DEFAULT '0',
  `fighter1_odds` int NOT NULL DEFAULT '0',
  `fighter2_odds` int NOT NULL DEFAULT '0',
  `bookie_id` int unsigned NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`fight_id`,`bookie_id`,`date`,`fighter1_odds`,`fighter2_odds`) USING BTREE,
  KEY `ix1` (`fight_id`,`bookie_id`,`date`,`fighter1_odds`,`fighter2_odds`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `fights` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `fighter1_id` int unsigned NOT NULL DEFAULT '0',
  `fighter2_id` int unsigned NOT NULL DEFAULT '0',
  `event_id` int unsigned NOT NULL DEFAULT '0',
  `is_mainevent` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `event_index` (`event_id`) USING BTREE,
  KEY `fighter_id_index` (`fighter1_id`),
  KEY `fighter_id2_index` (`fighter2_id`)
) ENGINE=MyISAM AUTO_INCREMENT=23292 DEFAULT CHARSET=latin1;


CREATE TABLE `lines_correlations` (
  `correlation` varchar(350) NOT NULL,
  `bookie_id` int unsigned NOT NULL,
  `matchup_id` int unsigned NOT NULL,
  PRIMARY KEY (`correlation`,`bookie_id`,`matchup_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `lines_eventprops` (
  `event_id` int unsigned NOT NULL DEFAULT '0',
  `bookie_id` int unsigned NOT NULL DEFAULT '0',
  `prop_odds` int NOT NULL DEFAULT '0',
  `negprop_odds` int NOT NULL DEFAULT '0',
  `proptype_id` int unsigned NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`,`bookie_id`,`date`,`proptype_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `lines_flagged` (
  `bookie_id` int NOT NULL,
  `matchup_id` int NOT NULL,
  `event_id` int NOT NULL,
  `proptype_id` int NOT NULL,
  `team_num` int NOT NULL,
  `initial_flagdate` timestamp NULL DEFAULT NULL,
  `last_flagdate` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`bookie_id`,`matchup_id`,`event_id`,`proptype_id`,`team_num`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `lines_props` (
  `matchup_id` int unsigned NOT NULL DEFAULT '0',
  `bookie_id` int unsigned NOT NULL DEFAULT '0',
  `prop_odds` int NOT NULL DEFAULT '0',
  `negprop_odds` int NOT NULL DEFAULT '0',
  `proptype_id` int unsigned NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `team_num` int unsigned NOT NULL,
  PRIMARY KEY (`matchup_id`,`bookie_id`,`date`,`proptype_id`,`team_num`),
  KEY `ix1` (`matchup_id`,`proptype_id`,`bookie_id`,`team_num`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `logs_parseruns` (
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `parser_id` int NOT NULL,
  `bookie_id` int NOT NULL,
  `parsed_matchups` int DEFAULT NULL,
  `matched_matchups` int DEFAULT NULL,
  `parsed_props` int DEFAULT NULL,
  `matched_props` int DEFAULT NULL,
  `status` int NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `authoritative_run` tinyint(1) DEFAULT NULL,
  `mockfeed_used` tinyint(1) DEFAULT NULL,
  `mockfeed_file` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`date`,`parser_id`,`bookie_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `matchups_createaudit` (
  `matchup_id` int NOT NULL,
  `source` int NOT NULL,
  PRIMARY KEY (`matchup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `matchups_fbposts` (
  `matchup_id` int NOT NULL DEFAULT '-1',
  `event_id` int NOT NULL DEFAULT '-1',
  `post_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `skipped` tinyint(1) NOT NULL,
  PRIMARY KEY (`matchup_id`,`event_id`,`post_date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `matchups_metadata` (
  `matchup_id` int NOT NULL,
  `mattribute` varchar(45) NOT NULL,
  `mvalue` varchar(500) NOT NULL,
  `source_bookie_id` int NOT NULL,
  PRIMARY KEY (`matchup_id`,`mattribute`,`source_bookie_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


CREATE TABLE `matchups_results` (
  `matchup_id` int NOT NULL,
  `winner` int DEFAULT '-1',
  `method` varchar(200) DEFAULT NULL,
  `endround` tinyint DEFAULT NULL,
  `endtime` varchar(20) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`matchup_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `matchups_unmatched` (
  `matchup` varchar(350) NOT NULL,
  `bookie_id` int unsigned NOT NULL DEFAULT '0',
  `log_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` int unsigned NOT NULL DEFAULT '0',
  `metadata` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`matchup`,`bookie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `prop_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `prop_desc` varchar(300) NOT NULL,
  `negprop_desc` varchar(300) DEFAULT NULL,
  `is_eventprop` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=226 DEFAULT CHARSET=latin1;


CREATE TABLE `schedule_manualactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(2000) DEFAULT NULL,
  `type` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;


CREATE TABLE `teams_twitterhandles` (
  `team_id` int unsigned NOT NULL,
  `handle` varchar(85) NOT NULL,
  PRIMARY KEY (`team_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;




USE `bets`;
DROP function IF EXISTS `MoneylineToDecimal`;

DELIMITER $$
USE `bets`$$
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


ALTER TABLE lines_props ADD KEY ix1(matchup_id, proptype_id, bookie_id, team_num, date);