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
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/views/pages/home.ctp)...
 */
	Router::mapResources('users');
	Router::parseExtensions();

	Router::connect('/', array('controller' => 'users', 'action' => 'home', 'home'));
	Router::connect('/login', array('controller' => 'users', 'action' => 'login'));
	Router::connect('/editProfile', array('controller' => 'users', 'action' => 'editProfile'));
	Router::connect('/mySubjects', array('controller' => 'users', 'action' => 'mySubjects'));
	Router::connect('/calendar_by_classroom', array('controller' => 'events', 'action' => 'calendar_by_classroom'));
	Router::connect('/calendar_by_subject', array('controller' => 'events', 'action' => 'calendar_by_subject'));
	Router::connect('/calendar_by_level', array('controller' => 'events', 'action' => 'calendar_by_level'));
/**
 * connect api controller's urls.
 */
  // Users
  Router::connect(
    '/api/users',
    array('controller' => 'api_users', 'action' => 'index', '[method]' => 'GET')
  );
  Router::connect(
    '/api/users/login',
    array('controller' => 'api_users', 'action' => 'login', '[method]' => 'GET')
  );
  Router::connect(
    '/api/users/me',
    array('controller' => 'api_users', 'action' => 'me', '[method]' => 'GET')
  );
  // Events
  Router::connect(
    '/api/events',
    array('controller' => 'api_events', 'action' => 'index', '[method]' => 'GET')
  );
  Router::connect(
    '/api/events/:id',
    array('controller' => 'api_events', 'action' => 'view', '[method]' => 'GET'),
    array('id' => '[0-9]+', 'pass' => array('id'))
  );
  // Attendance registers
  Router::connect(
    '/api/attendance_registers',
    array('controller' => 'api_attendance_registers', 'action' => 'add', '[method]' => 'POST')
  );
  Router::connect(
    '/api/attendance_registers/:id',
    array('controller' => 'api_attendance_registers', 'action' => 'edit', '[method]' => 'POST'),
    array('id' => '[0-9]+', 'pass' => array('id'))
  );
  Router::connect(
    '/api/attendance_registers/:id',
    array('controller' => 'api_attendance_registers', 'action' => 'view', '[method]' => 'GET'),
    array('id' => '[0-9]+', 'pass' => array('id'))
  );
  Router::connect(
    '/api/users_attendance_register',
    array('controller' => 'api_users_attendance_register', 'action' => 'add', '[method]' => 'POST')
  );
  Router::connect(
    '/api/users/:user_id/attendance_registers/:attendance_id',
    array('controller' => 'api_users_attendance_register', 'action' => 'delete', '[method]' => 'DELETE'),
    array('user_id' => '[0-9]+', 'attendance_id' => '[0-9]+', 'pass' => array('user_id', 'attendance_id'))
  );
  // Competence goals
  Router::connect(
    '/api/competence_goals/by_teacher/:teacher_id',
    array('controller' => 'api_competence_goals', 'action' => 'by_teacher', '[method]' => 'GET'),
    array('teacher_id' => 'me|[0-9]+', 'pass' => array('teacher_id'))
  );
  Router::connect(
    '/api/competence_goals/by_student/:student_id/:id',
    array('controller' => 'api_competence_goals', 'action' => 'by_student', '[method]' => 'GET'),
    array('student_id' => '[0-9]+', 'id' => '[0-9]+', 'pass' => array('student_id', 'id'))
  );
  // Grade competence goals
  Router::connect(
    '/api/competence_goals/grade_by_student/:student_id/:id',
    array('controller' => 'api_competence_goals', 'action' => 'grade_by_student', '[method]' => 'POST'),
    array('student_id' => '[0-9]+', 'id' => '[0-9]+', 'pass' => array('student_id', 'id'))
  );
  // Competence goal requests
  Router::connect(
    '/api/competence_goal_requests',
    array('controller' => 'api_competence_goal_requests', 'action' => 'index', '[method]' => 'GET')
  );
  Router::connect(
    '/api/competence_goal_requests/by_goal/:goal_id',
    array('controller' => 'api_competence_goal_requests', 'action' => 'by_goal', '[method]' => 'GET'),
    array('goal_id' => '[0-9]+', 'pass' => array('goal_id'))
  );
  Router::connect(
    '/api/competence_goal_requests/by_course/:course_id',
    array('controller' => 'api_competence_goal_requests', 'action' => 'by_course', '[method]' => 'GET'),
    array('course_id' => '[0-9]+', 'pass' => array('course_id'))
  );
  Router::connect(
    '/api/competence_goal_requests',
    array('controller' => 'api_competence_goal_requests', 'action' => 'add', '[method]' => 'POST')
  );
  Router::connect(
    '/api/competence_goal_requests/:competence_goal_request_id',
    array('controller' => 'api_competence_goal_requests', 'action' => 'delete', '[method]' => 'DELETE'),
    array('competence_goal_request_id' => '[0-9]+', 'pass' => array('competence_goal_request_id'))
  );
/**
 * ...and connect the rest of 'Pages' controller's urls.
 */
	Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
