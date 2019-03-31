<?php
class ApiFakeDataController extends AppController {
    var $name = 'Events';
    var $isApi = true;
    
    function _authorize()
    {
        $this->Auth->allow($this->params['action']);
        return true;
    }

    function _api_authenticate()
    {
        $username = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
        if (empty($_SERVER['PHP_AUTH_PWD'])) {
            try {
                $secretKey = base64_decode(Configure::read('Security.secret'));
                $username = \Firebase\JWT\JWT::decode($username, $secretKey, array('HS512'))->data->username;
            } catch (Exception $e) {
            }
        }
        $isStudent = preg_match('#(?:estudiante|student)#i', $username);
        $this->Auth->sessionKey = 'Api.Auth.User';
        $user = array(
            'id' => '1',
            'type' => $isStudent ? 'Estudiante' : 'Profesor',
            'dni' => '12345678Z',
            'first_name' => $isStudent ? 'Estudiante' : 'Profesor',
            'last_name' => '',
            'username' => $username ?: 'profesor@millolab.com',
            'phone' => '',
            'notify_all' => '1',
            'created' => '2000-01-01 00:00:00',
            'modified' => '2000-01-01 00:00:00',
        );
        $this->Auth->_loggedIn = true;
        $this->Auth->Session->write($this->Auth->sessionKey, $user);
    }

    function fake_request()
    {
        call_user_func_array(
            array($this, $this->params['fake_controller']),
            $this->params['pass']
        );
    }

    function users($id_or_action = null, $relation = null)
    {
        if ($id_or_action === 'login' || $id_or_action === 'me') {
            $issuer    = Configure::read('app.issuer');
            $issuedAt  = time();
            $tokenId   = base64_encode($issuer.$issuedAt.mcrypt_create_iv(16));
            $secretKey = base64_decode(Configure::read('Security.secret'));

            $responseData = $this->Auth->user();
            
            /*
            * Create the token as an array
            */
            $jwtData = array(
                'iat'  => $issuedAt, // Issued at: time when the token was generated
                'jti'  => $tokenId,  // Json Token Id: an unique identifier for the token
                'iss'  => $issuer,   // Issuer
                'data' => array(     // Data related to the signed user
                    'id'       => $responseData['User']['id'],       // id from the auth user
                    'username' => $responseData['User']['username'], // username from the auth user
                )
            );

            $responseData['Auth']['token'] = \Firebase\JWT\JWT::encode(
                $jwtData,   //Data to be encoded in the JWT
                $secretKey, // The signing key
                'HS512'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
            );
            $this->Api->setData($responseData);
        } elseif ($relation === null) {
            $student = json_decode(
                '{"User":{"id":"2","type":"Estudiante","dni":"12345678","first_name":"Estudiante","last_name":"Prueba","username":"estudiante@millolab.com","phone":"","notify_all":"1","created":null,"modified":"2015-09-09 18:44:36"}}',
                true
            );
            $this->Api->setData(array($student));
        } else {
            $attendance_register = json_decode(
                '{"AttendanceRegister":{"event_id":"69672","id":"85233","initial_hour":"2019-02-18 18:30:00","final_hour":"2019-02-18 20:00:00","duration":"0.00","teacher_id":"1","activity_id":"12163","group_id":"13086","num_students":"0","teacher_2_id":"-1","secret_code":"9731","created":"2019-02-18 16:37:53","modified":"2019-02-18 16:37:53"},"Teacher":{"id":"1","type":"Profesor","dni":"","first_name":"Profesor coordinador","last_name":"","username":"ibanfuentes+profesor@gmail.com","phone":"","notify_all":"1","created":"2018-09-03 11:43:48","modified":"2018-09-03 11:43:48"},"Teacher_2":{"id":null,"type":null,"dni":null,"first_name":null,"last_name":null,"username":null,"phone":null,"notify_all":null,"created":null,"modified":null},"Event":{"id":"69672","parent_id":null,"group_id":"13086","activity_id":"12163","teacher_id":"1","initial_hour":"2019-03-18 18:30:00","final_hour":"2019-03-18 20:00:00","classroom_id":"14","duration":"1.50","owner_id":"1978","teacher_2_id":null,"show_tv":"0","created":"2019-02-18 16:37:30","modified":"2019-02-18 16:37:30"},"Activity":{"id":"12163","subject_id":"1409","type":"Pr\u00e1ctica en aula","name":"practica en aula","notes":"","duration":"50.00","inflexible_groups":"0","created":"2018-09-17 12:11:54","modified":"2018-11-21 16:42:37"},"Group":{"id":"13086","subject_id":"1409","name":"grupo A","type":"Pr\u00e1ctica en aula","capacity":"10","notes":"","created":"2018-09-17 12:12:42","modified":"2018-09-17 12:12:42"},"Classroom":{"id":"14","name":"Aula Inform\u00e1tica A","type":"Aula","capacity":"20","show_tv":"1","teachers_can_booking":"0","created":null,"modified":"2015-08-28 08:41:03"},"Student":{"id":"2","type":"Estudiante","dni":"12345678","first_name":"Estudiante Prueba","last_name":"Estudiante","username":"estudiante@alu.ulpgc.es","phone":"928454305","notify_all":"1","created":null,"modified":"2015-09-09 18:44:36"},"Students":[{"Student":{"id":"1","type":"Estudiante","dni":"12345678Z","first_name":"Iban","last_name":"Estudiante","username":"ibanfuentes+estudiante@gmail.com","phone":"","notify_all":"1","created":"2018-09-17 11:03:08","modified":"2018-12-05 20:21:25"},"UserAttendanceRegister":{"user_id":"1","attendance_register_id":"85233","user_gone":"0","created":"2019-03-18 21:56:28","modified":"2019-03-18 21:56:28"}}]}',
                true
            );
            $initial_hour = date_create($attendance_register['Event']['initial_hour']);
            $initial_hour->setDate(date('Y'), date('n'), date('j'));
            $attendance_register['Event']['initial_hour'] = $initial_hour->format('Y-m-d H:i:s');
            $attendance_register['AttendanceRegister']['code'] = 9731;
            $this->Api->setData($attendance_register);
    
            $this->Api->respond($this);
        }

        $this->Api->respond($this);
    }

    function events($action = null)
    {
        $event = json_decode(
            '{"Event":{"id":"69672","parent_id":null,"group_id":"13086","activity_id":"12163","teacher_id":"1","initial_hour":"2019-03-14 18:30:00","final_hour":"2019-03-18 20:00:00","classroom_id":"14","duration":"1.50","owner_id":"1978","teacher_2_id":null,"show_tv":"0","created":"2019-02-18 16:37:30","modified":"2019-02-18 16:37:30"},"Activity":{"id":"12163","subject_id":"1409","type":"Pr\u00e1ctica en aula","name":"practica en aula","notes":"","duration":"50.00","inflexible_groups":"0","created":"2018-09-17 12:11:54","modified":"2018-11-21 16:42:37"},"Subject":{"id":"1409","course_id":"33","code":"12345","degree":null,"level":"Primero","type":"Troncal","name":"asignatura prueba","acronym":"prueb","semester":"Primero","credits_number":"300.00","coordinator_id":"1","practice_responsible_id":"2343","closed_attendance_groups":"1","created":"2018-09-17 12:11:23","modified":"2019-02-27 13:46:35"},"Group":{"id":"13086","subject_id":"1409","name":"grupo A","type":"Pr\u00e1ctica en aula","capacity":"10","notes":"","created":"2018-09-17 12:12:42","modified":"2018-09-17 12:12:42"},"Classroom":{"id":"14","name":"Aula Inform\u00e1tica A","type":"Aula","capacity":"20","show_tv":"1","teachers_can_booking":"0","created":null,"modified":"2015-08-28 08:41:03"}}',
            true
        );
        $initial_hour = date_create($event['Event']['initial_hour']);
        $initial_hour->setDate(date('Y'), date('n'), date('j'));
        $event['Event']['initial_hour'] = $initial_hour->format('Y-m-d H:i:s');
        $this->Api->setData(array($event));

        $this->Api->respond($this);
    }

    function attendance_registers($action = null)
    {
        $attendance_register = json_decode(
            '{"AttendanceRegister":{"event_id":"69672","id":"85233","initial_hour":"2019-02-18 18:30:00","final_hour":"2019-02-18 20:00:00","duration":"0.00","teacher_id":"1","activity_id":"12163","group_id":"13086","num_students":"0","teacher_2_id":"-1","secret_code":"9731","created":"2019-02-18 16:37:53","modified":"2019-02-18 16:37:53"},"Teacher":{"id":"1","type":"Profesor","dni":"","first_name":"Profesor coordinador","last_name":"","username":"ibanfuentes+profesor@gmail.com","phone":"","notify_all":"1","created":"2018-09-03 11:43:48","modified":"2018-09-03 11:43:48"},"Teacher_2":{"id":null,"type":null,"dni":null,"first_name":null,"last_name":null,"username":null,"phone":null,"notify_all":null,"created":null,"modified":null},"Event":{"id":"69672","parent_id":null,"group_id":"13086","activity_id":"12163","teacher_id":"1","initial_hour":"2019-03-18 18:30:00","final_hour":"2019-03-18 20:00:00","classroom_id":"14","duration":"1.50","owner_id":"1978","teacher_2_id":null,"show_tv":"0","created":"2019-02-18 16:37:30","modified":"2019-02-18 16:37:30"},"Activity":{"id":"12163","subject_id":"1409","type":"Pr\u00e1ctica en aula","name":"practica en aula","notes":"","duration":"50.00","inflexible_groups":"0","created":"2018-09-17 12:11:54","modified":"2018-11-21 16:42:37"},"Group":{"id":"13086","subject_id":"1409","name":"grupo A","type":"Pr\u00e1ctica en aula","capacity":"10","notes":"","created":"2018-09-17 12:12:42","modified":"2018-09-17 12:12:42"},"Classroom":{"id":"14","name":"Aula Inform\u00e1tica A","type":"Aula","capacity":"20","show_tv":"1","teachers_can_booking":"0","created":null,"modified":"2015-08-28 08:41:03"},"Students":[{"Student":{"id":"2","type":"Estudiante","dni":"12345678","first_name":"Estudiante","last_name":"Prueba","username":"estudiante@millolab.com","phone":"","notify_all":"1","created":null,"modified":"2015-09-09 18:44:36"},"UserAttendanceRegister":{"user_id":"2","attendance_register_id":"85233","user_gone":"1","created":"2019-03-18 21:53:02","modified":"2019-03-18 21:53:02"}},{"Student":{"id":"1","type":"Estudiante","dni":"12345678Z","first_name":"Iban","last_name":"Estudiante","username":"ibanfuentes+estudiante@gmail.com","phone":"","notify_all":"1","created":"2018-09-17 11:03:08","modified":"2018-12-05 20:21:25"},"UserAttendanceRegister":{"user_id":"1","attendance_register_id":"85233","user_gone":"0","created":"2019-03-18 21:50:15","modified":"2019-03-18 21:50:15"}}]}',
            true
        );
        $initial_hour = date_create($attendance_register['Event']['initial_hour']);
        $initial_hour->setDate(date('Y'), date('n'), date('j'));
        $attendance_register['Event']['initial_hour'] = $initial_hour->format('Y-m-d H:i:s');
        $attendance_register['AttendanceRegister']['code'] = 9731;
        $this->Api->setData($attendance_register);

        $this->Api->respond($this);
    }

    function users_attendance_register($action = null)
    {
        $attendance_register = json_decode(
            '{"Student":{"id":"1","type":"Estudiante","first_name":"Estudiante","last_name":"Prueba","username":"estudiante@millolab.com","notify_all":"1","created":"2018-09-17 11:03:08","modified":"2018-12-05 20:21:25"},"AttendanceRegister":{"event_id":"69672","id":"85233","initial_hour":"2019-02-18 18:30:00","final_hour":"2019-02-18 20:00:00","duration":"0.00","teacher_id":"1","activity_id":"12163","group_id":"13086","num_students":"0","teacher_2_id":"-1","secret_code":"9731","created":"2019-02-18 16:37:53","modified":"2019-02-18 16:37:53"},"Teacher":{"id":"1","type":"Profesor","dni":"","first_name":"Profesor coordinador","last_name":"","username":"ibanfuentes+profesor@gmail.com","phone":"","notify_all":"1","created":"2018-09-03 11:43:48","modified":"2018-09-03 11:43:48"},"Teacher_2":{"id":null,"type":null,"dni":null,"first_name":null,"last_name":null,"username":null,"phone":null,"notify_all":null,"created":null,"modified":null},"Event":{"id":"69672","parent_id":null,"group_id":"13086","activity_id":"12163","teacher_id":"1","initial_hour":"2019-03-18 18:30:00","final_hour":"2019-03-18 20:00:00","classroom_id":"14","duration":"1.50","owner_id":"1978","teacher_2_id":null,"show_tv":"0","created":"2019-02-18 16:37:30","modified":"2019-02-18 16:37:30"},"Activity":{"id":"12163","subject_id":"1409","type":"Pr\u00e1ctica en aula","name":"practica en aula","notes":"","duration":"50.00","inflexible_groups":"0","created":"2018-09-17 12:11:54","modified":"2018-11-21 16:42:37"},"Group":{"id":"13086","subject_id":"1409","name":"grupo A","type":"Pr\u00e1ctica en aula","capacity":"10","notes":"","created":"2018-09-17 12:12:42","modified":"2018-09-17 12:12:42"},"Classroom":{"id":"14","name":"Aula Inform\u00e1tica A","type":"Aula","capacity":"20","show_tv":"1","teachers_can_booking":"0","created":null,"modified":"2015-08-28 08:41:03"},"Students":[{"Student":{"id":"2","type":"Estudiante","dni":"12345678","first_name":"Estudiante","last_name":"Prueba","username":"estudiante@millolab.com","phone":"","notify_all":"1","created":null,"modified":"2015-09-09 18:44:36"},"UserAttendanceRegister":{"user_id":"2","attendance_register_id":"85233","user_gone":"1","created":"2019-03-18 21:53:02","modified":"2019-03-18 21:53:02"}},{"Student":{"id":"1","type":"Estudiante","dni":"12345678Z","first_name":"Iban","last_name":"Estudiante","username":"ibanfuentes+estudiante@gmail.com","phone":"","notify_all":"1","created":"2018-09-17 11:03:08","modified":"2018-12-05 20:21:25"},"UserAttendanceRegister":{"user_id":"1","attendance_register_id":"85233","user_gone":"0","created":"2019-03-18 21:50:15","modified":"2019-03-18 21:50:15"}}],"UserAttendanceRegister":{"user_id":"1","attendance_register_id":"85233","user_gone":1,"created":"2019-03-18 21:56:28","modified":"2019-03-18 21:56:28"}}',
            true
        );
        $initial_hour = date_create($attendance_register['Event']['initial_hour']);
        $initial_hour->setDate(date('Y'), date('n'), date('j'));
        $attendance_register['Event']['initial_hour'] = $initial_hour->format('Y-m-d H:i:s');
        $attendance_register['AttendanceRegister']['code'] = 9731;
        $this->Api->setData($attendance_register);

        $this->Api->respond($this);
    }
}
