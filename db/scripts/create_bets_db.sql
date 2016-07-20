CREATE DATABASE `bets` /*!40100 DEFAULT CHARACTER SET latin1 */;

CREATE TABLE  `bets`.`alerts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL DEFAULT '',
  `fight_id` int(10) unsigned NOT NULL DEFAULT '0',
  `fighter` int(10) unsigned NOT NULL DEFAULT '0',
  `bookie_id` int(11) NOT NULL DEFAULT '0',
  `odds` int(11) NOT NULL DEFAULT '0',
  `odds_type` int(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12920 DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`bookies` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `url` text,
  `refurl` text,
  `active` tinyint(1) NOT NULL default '1',
  `position` int(10) unsigned NOT NULL default '2',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`events` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `date` timestamp NULL default CURRENT_TIMESTAMP,
  `name` varchar(200) NOT NULL default '',
  `display` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`fighters` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(85) NOT NULL default '',
  `url` text NOT NULL,
  PRIMARY KEY  USING BTREE (`id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`fighters_altnames` (
  `fighter_id` int(11) DEFAULT NULL,
  `altname` varchar(100) DEFAULT NULL,
  KEY `INDEX` (`fighter_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

CREATE TABLE  `bets`.`fightodds` (
  `fight_id` int(10) unsigned NOT NULL default '0',
  `fighter1_odds` int(11) NOT NULL default '0',
  `fighter2_odds` int(11) NOT NULL default '0',
  `bookie_id` int(10) unsigned NOT NULL default '0',
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  USING BTREE (`fight_id`,`bookie_id`,`date`,`fighter1_odds`,`fighter2_odds`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`fights` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `fighter1_id` int(10) unsigned NOT NULL default '0',
  `fighter2_id` int(10) unsigned NOT NULL default '0',
  `event_id` int(10) unsigned NOT NULL default '0',
  `is_mainevent` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`linkouts_ext` (
  `bookie_id` int(3) unsigned NOT NULL default '0',
  `click_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `event_id` int(8) unsigned NOT NULL default '0',
  `visitor_ip` varchar(15) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`fight_twits` (
  `fight_id` int(10) unsigned NOT NULL,
  `twitdate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`fight_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`sports` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(30) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `bets`.`bookies_changenums` (
  `bookie_id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `changenum` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`bookie_id`)
) ENGINE = MyISAM;

CREATE TABLE  `bets`.`bookies_proptemplates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bookie_id` int(10) unsigned NOT NULL,
  `template` varchar(300) NOT NULL,
  `prop_type` int(10) unsigned NOT NULL,
  `template_neg` varchar(300) NOT NULL,
  `fields_type` int(3) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`bookies_parsers` (
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

CREATE TABLE  `bets`.`prop_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `prop_desc` varchar(300) NOT NULL,
  `negprop_desc` varchar(300) NOT NULL,
  `is_eventprop` TINYINT(1) DEFAULT 0 NOT NULL, 
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`lines_props` (
  `matchup_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bookie_id` int(10) unsigned NOT NULL DEFAULT '0',
  `prop_odds` int(11) NOT NULL DEFAULT '0',
  `negprop_odds` int(11) NOT NULL DEFAULT '0',
  `proptype_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `team_num` int(2) unsigned NOT NULL,
  PRIMARY KEY (`matchup_id`,`bookie_id`,`date`,`proptype_id`,`team_num`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`alerts_exemptions` (
  `email` varchar(45) NOT NULL,
  PRIMARY KEY (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`lines_correlations` (
  `correlation` varchar(200) NOT NULL,
  `bookie_id` int(10) unsigned NOT NULL,
  `matchup_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`correlation`,`bookie_id`,`matchup_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`matchups_unmatched` (
  `matchup` varchar(350) NOT NULL,
  `bookie_id` int(10) unsigned NOT NULL DEFAULT '0',
  `log_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` int(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`matchup`,`bookie_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `bets`.`matchups_metadata` (
  `matchup_id` INT NOT NULL,
  `mattribute` VARCHAR(45) NOT NULL,
  `mvalue` VARCHAR(45) NOT NULL,
  `source_bookie_id` INT NOT NULL,
  PRIMARY KEY (`matchup_id`, `mattribute`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `bets`.`events_redditposts` (
  `event_id` INT NOT NULL,
  `reddit_id` VARCHAR(45) NOT NULL,
  `last_change` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `schedule_manualactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(2000) DEFAULT NULL,
  `type` int(2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=53 DEFAULT CHARSET=latin1;

CREATE TABLE `lines_eventprops` (
  `event_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bookie_id` int(10) unsigned NOT NULL DEFAULT '0',
  `prop_odds` int(11) NOT NULL DEFAULT '0',
  `negprop_odds` int(11) NOT NULL DEFAULT '0',
  `proptype_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`,`bookie_id`,`date`,`proptype_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `matchups_results` (
  `matchup_id` INT(11) NOT NULL,
  `winner` INT(11) NULL DEFAULT '-1',
  `method` VARCHAR(200) NULL DEFAULT NULL,
  `endround` TINYINT(2) NULL DEFAULT NULL,
  `endtime` VARCHAR(20) NULL DEFAULT NULL,
  PRIMARY KEY (`matchup_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


CREATE TABLE `alerts_entries` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `oddstype` INT(1) NOT NULL,
  `criterias` VARCHAR(500) NOT NULL,
  PRIMARY KEY (`id`, `email`, `criterias`),
  UNIQUE INDEX `ix_entry` (`email`, `criterias`)
)
ENGINE=MyISAM DEFAULT 
CHARSET=latin1;
AUTO_INCREMENT=301
;

CREATE TABLE `prop_types_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
)
ENGINE=MyISAM DEFAULT 
CHARSET=latin1;
;

CREATE TABLE `logs_parseruns` (
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


CREATE TABLE `matchups_fbposts` (
  `matchup_id` INT(7) NOT NULL DEFAULT '-1',
  `event_id` INT(7) NOT NULL DEFAULT '-1',
  `post_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `skipped` TINYINT(1) NOT NULL,
  PRIMARY KEY (`matchup_id`, `event_id`, `post_date`)
)
ENGINE=MyISAM DEFAULT CHARSET=latin1;



USE `bets`;
DROP function IF EXISTS `MoneylineToDecimal`;

DELIMITER $$
USE `bets`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `MoneylineToDecimal`(moneyline INT) RETURNS float
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

