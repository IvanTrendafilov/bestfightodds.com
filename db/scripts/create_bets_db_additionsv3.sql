CREATE TABLE `alerts_entries` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`email` VARCHAR(255) NOT NULL,
	`oddstype` INT(1) NOT NULL,
	`criterias` VARCHAR(500) NOT NULL,
	PRIMARY KEY (`id`, `email`, `criterias`),
	UNIQUE INDEX `ix_entry` (`email`, `criterias`)
)
COLLATE='latin1_swedish_ci'
ENGINE=InnoDB
AUTO_INCREMENT=117
;


CREATE TABLE `prop_types_categories` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` INT(11) NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
)
ENGINE=InnoDB
;
