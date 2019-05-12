CREATE TABLE `monitors` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `show_events` TINYINT(1) NOT NULL DEFAULT 0,
  `show_media` TINYINT(1) NOT NULL DEFAULT 0,
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE `monitors_media` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `monitor_id` INT(11) NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `src` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(255) NULL,
  `video_id` VARCHAR(255) NULL,
  `visible` TINYINT(1) NOT NULL DEFAULT 0,
  `order` INT(11) NOT NULL,
  `duration` INT(11) NULL,
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE `monitors_classrooms` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `monitor_id` INT(11) NOT NULL,
  `classroom_id` INT(11) NOT NULL,
  `created` DATETIME NULL,
  `modified` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
