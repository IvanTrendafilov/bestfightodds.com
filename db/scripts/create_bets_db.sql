CREATE DATABASE `bets` /*!40100 DEFAULT CHARACTER SET latin1 */;

CREATE TABLE  `bets`.`alerts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(45) NOT NULL DEFAULT '',
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`events` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `date` timestamp NULL default CURRENT_TIMESTAMP,
  `name` varchar(200) NOT NULL default '',
  `display` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`fighters` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(85) NOT NULL default '',
  `url` text NOT NULL,
  PRIMARY KEY  USING BTREE (`id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`fighters_altnames` (
  `fighter_id` int(11) DEFAULT NULL,
  `altname` varchar(100) DEFAULT NULL,
  KEY `INDEX` (`fighter_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

CREATE TABLE  `bets`.`fightodds` (
  `fight_id` int(10) unsigned NOT NULL default '0',
  `fighter1_odds` int(11) NOT NULL default '0',
  `fighter2_odds` int(11) NOT NULL default '0',
  `bookie_id` int(10) unsigned NOT NULL default '0',
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  USING BTREE (`fight_id`,`bookie_id`,`date`,`fighter1_odds`,`fighter2_odds`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`fights` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `fighter1_id` int(10) unsigned NOT NULL default '0',
  `fighter2_id` int(10) unsigned NOT NULL default '0',
  `event_id` int(10) unsigned NOT NULL default '0',
  `is_mainevent` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`linkouts_ext` (
  `bookie_id` int(3) unsigned NOT NULL default '0',
  `click_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `event_id` int(8) unsigned NOT NULL default '0',
  `visitor_ip` varchar(15) NOT NULL default ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`fight_twits` (
  `fight_id` int(10) unsigned NOT NULL,
  `twitdate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`fight_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`sports` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(30) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`lines_spread_set` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `matchup_id` int(10) unsigned NOT NULL,
  `bookie_id` smallint(5) unsigned NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`lines_spread` (
  `team1_line` smallint(6) NOT NULL,
  `team2_line` smallint(6) NOT NULL,
  `team1_spread` decimal(4,1) NOT NULL,
  `team2_spread` decimal(4,1) NOT NULL,
  `set_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`team1_spread`,`team2_spread`,`set_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`lines_totals_set` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `matchup_id` int(10) unsigned NOT NULL,
  `bookie_id` smallint(5) unsigned NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`lines_totals` (
  `totalpoints` decimal(4,1) NOT NULL,
  `over_line` smallint(6) NOT NULL,
  `under_line` smallint(6) NOT NULL,
  `set_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`set_id`,`totalpoints`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `bets`.`bookies_changenums` (
  `bookie_id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `changenum` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`bookie_id`)
) ENGINE = InnoDB;

CREATE TABLE  `bets`.`bookies_proptemplates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bookie_id` int(10) unsigned NOT NULL,
  `template` varchar(300) NOT NULL,
  `prop_type` int(10) unsigned NOT NULL,
  `template_neg` varchar(300) NOT NULL,
  `fields_type` int(3) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`bookies_parsers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bookie_id` int(10) unsigned NOT NULL,
  `parse_url` varchar(500) NOT NULL,
  `name` varchar(45) NOT NULL,
  `cn_inuse` tinyint(1) NOT NULL,
  `mockfile` varchar(45) NOT NULL,
  `cn_urlsuffix` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`prop_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `prop_desc` varchar(300) NOT NULL,
  `negprop_desc` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`lines_props` (
  `matchup_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bookie_id` int(10) unsigned NOT NULL DEFAULT '0',
  `prop_odds` int(11) NOT NULL DEFAULT '0',
  `negprop_odds` int(11) NOT NULL DEFAULT '0',
  `proptype_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `team_num` int(2) unsigned NOT NULL,
  PRIMARY KEY (`matchup_id`,`bookie_id`,`date`,`proptype_id`,`team_num`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `bets`.`alerts_exemptions` (
  `email` varchar(45) NOT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `bets`.`matchups_metadata` (
  `matchup_id` INT NOT NULL,
  `mattribute` VARCHAR(45) NOT NULL,
  `mvalue` VARCHAR(45) NOT NULL,
  `source_bookie_id` INT NOT NULL,
  PRIMARY KEY (`matchup_id`, `attribute`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `bets`.`events_redditposts` (
  `event_id` INT NOT NULL,
  `reddit_id` VARCHAR(45) NOT NULL,
  `last_change` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `schedule_manualactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(2000) DEFAULT NULL,
  `type` int(2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=latin1;

