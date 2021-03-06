<?php
/**
 * This file is loaded automatically by the app/webroot/index.php file after the core bootstrap.php
 *
 * This is an application wide file to load any function that is not used within a class
 * define. You can also use this to include or require any files in your application.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.app.config
 * @since         CakePHP(tm) v 0.10.8.2117
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

require_once ROOT . '/vendors/plugins/autoload.php'; 

/**
 * The settings below can be used to set additional paths to models, views and controllers.
 * This is related to Ticket #470 (https://trac.cakephp.org/ticket/470)
 *
 * App::build(array(
 *     'plugins' => array('/full/path/to/plugins/', '/next/full/path/to/plugins/'),
 *     'models' =>  array('/full/path/to/models/', '/next/full/path/to/models/'),
 *     'views' => array('/full/path/to/views/', '/next/full/path/to/views/'),
 *     'controllers' => array('/full/path/to/controllers/', '/next/full/path/to/controllers/'),
 *     'datasources' => array('/full/path/to/datasources/', '/next/full/path/to/datasources/'),
 *     'behaviors' => array('/full/path/to/behaviors/', '/next/full/path/to/behaviors/'),
 *     'components' => array('/full/path/to/components/', '/next/full/path/to/components/'),
 *     'helpers' => array('/full/path/to/helpers/', '/next/full/path/to/helpers/'),
 *     'vendors' => array('/full/path/to/vendors/', '/next/full/path/to/vendors/'),
 *     'shells' => array('/full/path/to/shells/', '/next/full/path/to/shells/'),
 *     'locales' => array('/full/path/to/locale/', '/next/full/path/to/locale/')
 * ));
 *
 */

/**
 * As of 1.3, additional rules for the inflector are added below
 *
 * Inflector::rules('singular', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 * Inflector::rules('plural', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 *
 */
Inflector::rules('singular', array('irregular' => array('users_attendance_register' => 'user_attendance_register')));
Inflector::rules('singular', array('irregular' => array('usersattendanceregister'   => 'UserAttendanceRegister')));
Inflector::rules('plural',   array('irregular' => array('user_attendance_register'  => 'users_attendance_register')));
Inflector::rules('plural',   array('irregular' => array('userattendanceregister'    => 'users_attendance_register')));

Inflector::rules('singular', array('irregular' => array('users_booking' => 'user_booking')));
Inflector::rules('singular', array('irregular' => array('usersbooking'  => 'UserBooking')));
Inflector::rules('plural',   array('irregular' => array('user_booking'  => 'users_booking')));
Inflector::rules('plural',   array('irregular' => array('userbooking'   => 'users_booking')));

Inflector::rules('singular', array('irregular' => array('log' => 'log')));
Inflector::rules('plural', array('irregular' => array('log' => 'log')));

Inflector::rules('singular', array('irregular' => array('monitors_media' => 'monitor_media')));
Inflector::rules('singular', array('irregular' => array('monitorsmedia'  => 'MonitorMedia')));
Inflector::rules('plural',   array('irregular' => array('monitor_media'  => 'monitors_media')));
Inflector::rules('plural',   array('irregular' => array('monitormedia'   => 'monitors_media')));

Inflector::rules('singular', array('irregular' => array('competence' => 'competence')));
Inflector::rules('plural',   array('irregular' => array('competence' => 'competence')));

Inflector::rules('singular', array('irregular' => array('competence_criteria' => 'competence_criterion')));
Inflector::rules('singular', array('irregular' => array('competencecriteria'  => 'CompetenceCriterion')));
Inflector::rules('plural',   array('irregular' => array('competence_criterion'  => 'competence_criteria')));
Inflector::rules('plural',   array('irregular' => array('competencecriterion'   => 'competence_criteria')));
