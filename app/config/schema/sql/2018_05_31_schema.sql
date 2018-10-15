-- phpMyAdmin SQL Dump
-- version 3.3.9.2
-- http://www.phpmyadmin.net
--
-- Host: mysql.ulpgc.es
-- Generation Time: May 31, 2018 at 06:49 PM
-- Server version: 5.0.95
-- PHP Version: 5.2.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `academic`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE IF NOT EXISTS `activities` (
  `id` int(11) NOT NULL auto_increment,
  `subject_id` int(11) NOT NULL default '0',
  `type` varchar(255) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `notes` text,
  `duration` decimal(10,2) NOT NULL,
  `inflexible_groups` tinyint(1) NOT NULL default '0',
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `subject_id_idxfk` (`subject_id`),
  KEY `type_idx` (`type`(6)),
  KEY `name_idx` (`name`(10))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12156 ;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_registers`
--

CREATE TABLE IF NOT EXISTS `attendance_registers` (
  `event_id` int(11) default NULL,
  `id` int(11) NOT NULL auto_increment,
  `initial_hour` datetime NOT NULL,
  `final_hour` datetime NOT NULL,
  `duration` decimal(10,2) NOT NULL,
  `teacher_id` int(11) default '0',
  `activity_id` int(11) default '0',
  `group_id` int(11) default '0',
  `num_students` int(11) default '0',
  `teacher_2_id` int(11) default NULL,
  `secret_code` varchar(6) default NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `event_id_idxfk` (`event_id`),
  KEY `teacher_id_idxfk` USING BTREE (`teacher_id`),
  KEY `activity_id_idxfk` USING BTREE (`activity_id`),
  KEY `group_id` USING BTREE (`group_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 PACK_KEYS=0 AUTO_INCREMENT=84958 ;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int(11) NOT NULL auto_increment,
  `parent_id` int(11) default NULL,
  `user_id` int(11) NOT NULL default '0',
  `user_type` varchar(255) NOT NULL default '',
  `initial_hour` datetime NOT NULL,
  `classroom_id` int(11) NOT NULL default '0',
  `final_hour` datetime NOT NULL,
  `reason` varchar(255) NOT NULL default '',
  `required_equipment` text,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `parent_id_idxfk` (`parent_id`),
  KEY `user_id_idxfk` (`user_id`),
  KEY `classroom_id_idxfk` (`classroom_id`),
  KEY `user_type_idx` (`user_type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4006 ;

-- --------------------------------------------------------

--
-- Table structure for table `classrooms`
--

CREATE TABLE IF NOT EXISTS `classrooms` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `capacity` int(11) NOT NULL,
  `show_tv` tinyint(1) NOT NULL default '0',
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `name_idx` (`name`(7)),
  KEY `type_idx` (`type`(7))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=144 ;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE IF NOT EXISTS `courses` (
  `id` int(11) NOT NULL auto_increment,
  `initial_date` date default NULL,
  `final_date` date default NULL,
  `name` varchar(255) NOT NULL default '',
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `initial_date_idx` (`initial_date`),
  KEY `end_date_idx` (`final_date`),
  KEY `name_idx` (`name`(10))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=34 ;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL auto_increment,
  `parent_id` int(11) default NULL,
  `group_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL default '0',
  `teacher_id` int(11) default NULL,
  `initial_hour` datetime NOT NULL,
  `final_hour` datetime NOT NULL,
  `classroom_id` int(11) NOT NULL default '0',
  `duration` decimal(10,2) NOT NULL,
  `owner_id` int(11) default NULL,
  `teacher_2_id` int(11) default NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `parent_id_idxfk` (`parent_id`),
  KEY `group_id_idxfk` (`group_id`),
  KEY `activity_id_idxfk` (`activity_id`),
  KEY `classroom_id_idxfk` (`classroom_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `owner_id_idxfk` (`owner_id`),
  KEY `initial_hour` (`initial_hour`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=65601 ;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(11) NOT NULL auto_increment,
  `subject_id` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `type` varchar(255) NOT NULL default '',
  `capacity` int(11) NOT NULL default '0',
  `notes` text,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `subject_id_idxfk` (`subject_id`),
  KEY `name_idx` (`name`),
  KEY `type_idx` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13078 ;

-- --------------------------------------------------------

--
-- Table structure for table `group_requests`
--

CREATE TABLE IF NOT EXISTS `group_requests` (
  `id` int(11) NOT NULL auto_increment,
  `activity_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `student_2_id` int(11) NOT NULL,
  `group_2_id` int(11) NOT NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `user_group_index` (`group_id`,`student_id`),
  KEY `user_group_2_index` (`student_2_id`,`group_2_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4313 ;

-- --------------------------------------------------------

--
-- Table structure for table `massive_attendance_registers`
--

CREATE TABLE IF NOT EXISTS `massive_attendance_registers` (
  `id` int(11) NOT NULL auto_increment,
  `subject_id` int(11) NOT NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `fk_subject_id` (`subject_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE IF NOT EXISTS `registrations` (
  `group_id` int(11) default NULL,
  `activity_id` int(11) default NULL,
  `student_id` int(11) default NULL,
  `id` int(11) NOT NULL auto_increment,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `group_id_idxfk` (`group_id`),
  KEY `activity_id_idxfk` (`activity_id`),
  KEY `student_id_idxfk` (`student_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=697959 ;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int(11) NOT NULL auto_increment,
  `course_id` int(11) NOT NULL default '0',
  `code` int(11) NOT NULL default '0',
  `level` varchar(255) NOT NULL default '',
  `type` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL default '',
  `acronym` varchar(255) NOT NULL,
  `semester` varchar(255) NOT NULL,
  `credits_number` decimal(10,2) NOT NULL,
  `coordinator_id` int(11) NOT NULL default '0',
  `practice_responsible_id` int(11) NOT NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `course_id_idxfk` (`course_id`),
  KEY `coordinator_id_idxfk` (`coordinator_id`),
  KEY `practice_responsible_id_idxfk` (`practice_responsible_id`),
  KEY `code_idx` (`code`),
  KEY `name_idx` (`name`),
  KEY `type_idx` (`type`),
  KEY `semester_idx` (`semester`),
  KEY `level_idx` (`level`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1409 ;

-- --------------------------------------------------------

--
-- Table structure for table `subjects_users`
--

CREATE TABLE IF NOT EXISTS `subjects_users` (
  `id` int(11) NOT NULL auto_increment,
  `subject_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `practices_approved` tinyint(1) NOT NULL default '0',
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  USING BTREE (`id`),
  KEY `subjects_index` USING BTREE (`subject_id`),
  KEY `user_id` USING BTREE (`user_id`),
  KEY `course_idx` (`course_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=49700 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL auto_increment,
  `type` varchar(255) NOT NULL,
  `dni` varchar(255) NOT NULL default '',
  `first_name` varchar(255) NOT NULL default '',
  `last_name` varchar(255) NOT NULL default '',
  `username` varchar(255) NOT NULL default '',
  `phone` varchar(255) default '',
  `password` varchar(255) NOT NULL default '',
  `notify_all` tinyint(1) NOT NULL default '1',
  `created` datetime default NULL,
  `modified` datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `first_name_idx` (`first_name`),
  KEY `last_name_idx` (`last_name`),
  KEY `username_idx` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2251 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_attendance_register`
--

CREATE TABLE IF NOT EXISTS `users_attendance_register` (
  `user_id` int(11) NOT NULL,
  `attendance_register_id` int(11) NOT NULL,
  `user_gone` tinyint(1) NOT NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  KEY `user_id_idxfk` (`user_id`),
  KEY `attendance_register_id_idxfk` (`attendance_register_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users_booking`
--

CREATE TABLE IF NOT EXISTS `users_booking` (
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `created` datetime default NULL,
  `modified` datetime default NULL,
  KEY `user_id_idxfk` (`user_id`),
  KEY `booking_id_idxfk` (`booking_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
