<?php
/**
 * Short description for file.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different urls to chosen controllers and their actions (functions).
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
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * connect api controller's urls.
 */
  // Users
  Router::connect(
    '/api/institutions/:institution/users',
    array('controller' => 'api_users', 'action' => 'index', '[method]' => 'GET'),
    array('institution' => '[0-9]+')
  );
  Router::connect(
    '/api/institutions/:institution/users/login',
    array('controller' => 'api_users', 'action' => 'login', '[method]' => 'GET'),
    array('institution' => '[0-9]+')
  );
  Router::connect(
    '/api/institutions/:institution/users/me',
    array('controller' => 'api_users', 'action' => 'me', '[method]' => 'GET'),
    array('institution' => '[0-9]+')
  );
  // Events
  Router::connect(
    '/api/institutions/:institution/events',
    array('controller' => 'api_events', 'action' => 'index', '[method]' => 'GET'),
    array('institution' => '[0-9]+')
  );
  Router::connect(
    '/api/institutions/:institution/events/:id',
    array('controller' => 'api_events', 'action' => 'view', '[method]' => 'GET'),
    array('institution' => '[0-9]+', 'id' => '[0-9]+', 'pass' => array('id'))
  );
  // Attendance registers
  Router::connect(
    '/api/institutions/:institution/attendance_registers',
    array('controller' => 'api_attendance_registers', 'action' => 'add', '[method]' => 'POST'),
    array('institution' => '[0-9]+')
  );
  Router::connect(
    '/api/institutions/:institution/attendance_registers/:id',
    array('controller' => 'api_attendance_registers', 'action' => 'edit', '[method]' => 'POST'),
    array('institution' => '[0-9]+', 'id' => '[0-9]+', 'pass' => array('id'))
  );
  Router::connect(
    '/api/institutions/:institution/attendance_registers/:id',
    array('controller' => 'api_attendance_registers', 'action' => 'view', '[method]' => 'GET'),
    array('institution' => '[0-9]+', 'id' => '[0-9]+', 'pass' => array('id'))
  );
  Router::connect(
    '/api/users_attendance_register',
    array('controller' => 'api_users_attendance_register', 'action' => 'add', '[method]' => 'POST')
  );
  Router::connect(
    '/api/institutions/:institution/users_attendance_register',
    array('controller' => 'api_users_attendance_register', 'action' => 'add', '[method]' => 'POST'),
    array('institution' => '[0-9]+')
  );
  Router::connect(
    '/api/institutions/:institution/users/:user_id/attendance_registers/:attendance_id',
    array('controller' => 'api_users_attendance_register', 'action' => 'delete', '[method]' => 'DELETE'),
    array('institution' => '[0-9]+', 'user_id' => '[0-9]+', 'attendance_id' => '[0-9]+', 'pass' => array('user_id', 'attendance_id'))
  );
  // Competence goals
  Router::connect(
    '/api/institutions/:institution/competence_goals/by_teacher/:teacher_id',
    array('controller' => 'api_competence_goals', 'action' => 'by_teacher', '[method]' => 'GET'),
    array('institution' => '[0-9]+', 'teacher_id' => 'me|[0-9]+', 'pass' => array('teacher_id'))
  );
  Router::connect(
    '/api/institutions/:institution/competence_goals/by_student/:student_id/:id',
    array('controller' => 'api_competence_goals', 'action' => 'by_student', '[method]' => 'GET'),
    array('institution' => '[0-9]+', 'student_id' => '[0-9]+', 'id' => '[0-9]+', 'pass' => array('student_id', 'id'))
  );
  // Grade competence goals
  Router::connect(
    '/api/institutions/:institution/competence_goals/grade_by_student/:student_id/:id',
    array('controller' => 'api_competence_goals', 'action' => 'grade_by_student', '[method]' => 'POST'),
    array('institution' => '[0-9]+', 'student_id' => '[0-9]+', 'id' => '[0-9]+', 'pass' => array('student_id', 'id'))
  );
  // Competence goal requests
  Router::connect(
    '/api/institutions/:institution/competence_goal_requests',
    array('controller' => 'api_competence_goal_requests', 'action' => 'index', '[method]' => 'GET'),
    array('institution' => '[0-9]+')
  );
  Router::connect(
    '/api/institutions/:institution/competence_goal_requests/by_goal/:goal_id',
    array('controller' => 'api_competence_goal_requests', 'action' => 'by_goal', '[method]' => 'GET'),
    array('institution' => '[0-9]+', 'goal_id' => '[0-9]+', 'pass' => array('goal_id'))
  );
  Router::connect(
    '/api/institutions/:institution/competence_goal_requests/by_course/:course_id',
    array('controller' => 'api_competence_goal_requests', 'action' => 'by_course', '[method]' => 'GET'),
    array('institution' => '[0-9]+', 'course_id' => '[0-9]+', 'pass' => array('course_id'))
  );
  Router::connect(
    '/api/institutions/:institution/competence_goal_requests',
    array('controller' => 'api_competence_goal_requests', 'action' => 'add', '[method]' => 'POST'),
    array('institution' => '[0-9]+')
  );
  Router::connect(
    '/api/institutions/:institution/competence_goal_requests/:competence_goal_request_id',
    array('controller' => 'api_competence_goal_requests', 'action' => 'delete', '[method]' => 'DELETE'),
    array('institution' => '[0-9]+', 'competence_goal_request_id' => '[0-9]+', 'pass' => array('competence_goal_request_id'))
  );
  // Log
  Router::connect(
    '/api/log',
    array('controller' => 'api_log', 'action' => 'add', '[method]' => 'POST'),
    array('institution' => '[0-9]+')
  );
  Router::connect(
    '/institutions/:institution/api/log',
    array('controller' => 'api_log', 'action' => 'add', '[method]' => 'POST'),
    array('institution' => '[0-9]+')
  );
  // Fake api requests
  Router::connect(
    '/api/fake_data/:fake_controller/*',
    array('controller' => 'api_fake_data', 'action' => 'fake_request'),
    array('institution' => '[0-9]+')
  );

/**
 * connect app controller's urls.
 */
  Router::mapResources('users');
  Router::parseExtensions();
  
  Router::connect('/', array('controller' => 'users', 'action' => 'home', 'home'));
	Router::connect('/login', array('controller' => 'users', 'action' => 'login'));
	Router::connect('/editProfile', array('controller' => 'users', 'action' => 'editProfile'));
	Router::connect('/mySubjects', array('controller' => 'users', 'action' => 'mySubjects'));
	
  Router::connect('/institutions/:institution/calendar_by_classroom', array('controller' => 'events', 'action' => 'calendar_by_classroom'), array('institution' => '[0-9]+'));
  Router::connect('/institutions/:institution/calendar_by_subject', array('controller' => 'events', 'action' => 'calendar_by_subject'), array('institution' => '[0-9]+'));
  Router::connect('/institutions/:institution/calendar_by_level', array('controller' => 'events', 'action' => 'calendar_by_level'), array('institution' => '[0-9]+'));
  Router::connect('/institutions/:institution/calendar_by_teacher', array('controller' => 'events', 'action' => 'calendar_by_teacher'), array('institution' => '[0-9]+'));
  
  Router::connect('/institutions/:institution/:controller/:action/*', array('action' => 'index'), array('institution' => '[0-9]+', 'controller' => '[\w_]+', 'action' => '[\w_]+'));
  Router::connect('/institutions/:institution/*', array('controller' => 'users', 'action' => 'home'), array('institution' => '[0-9]+'));

  Router::connect('/:controller/:action/*', array('action' => 'index'), array('controller' => '[\w_]+', 'action' => '[\w_]+'));
