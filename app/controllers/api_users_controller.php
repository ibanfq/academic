<?php
class ApiUsersController extends AppController {
    var $name = 'Users';
    var $isApi = true;

    function _authorize(){
        if (!parent::_authorize()) {
            return false;
        }

        $administrator_actions = array('delete');
        $administrative_actions = array('edit', 'add');
        $neither_student_nor_concierge = array('index', 'view');
        $student_actions = array();
        
        if ((array_search($this->params['action'], $administrator_actions) !== false) && ($this->Auth->user('type') != "Administrador")) {
            return false;
        }
        
        if ((array_search($this->params['action'], $administrative_actions) !== false) && ($this->Auth->user('type') != "Administrador") && ($this->Auth->user('type') != "Administrativo")) {
            return false;
        }
        
        if ((array_search($this->params['action'], $neither_student_nor_concierge) !== false) && (($this->Auth->user('type') == "Estudiante") || ($this->Auth->user('type') == "Conserje") )) {
            return false;
        }
        
        if ((array_search($this->params['action'], $student_actions) !== false) && ($this->Auth->user('type') != "Estudiante")) {
            return false;
        }

        return true;
    }
    
    function me()
    {
        $responseData = $this->Auth->user();
        $username = $responseData['User']['username'];
        $beta_testers = (array) Configure::read('app.beta.testers');
        if ($username && !empty($beta_testers[$username])) {
            $responseData['User']['beta'] = true;
            $responseData['User']['beta_config'] = (array) Configure::read('app.beta.config_writes');
        }
        $this->Api->setData($responseData);
        $this->Api->respond($this);
    }

    function login()
    {
        $issuer    = Configure::read('app.issuer');
        $issuedAt  = time();
        $tokenId   = base64_encode($issuer.$issuedAt.mcrypt_create_iv(16));
        $secretKey = base64_decode(Configure::read('Security.secret'));

        $responseData = $this->Auth->user();
        $username = $responseData['User']['username'];
        $beta_testers = (array) Configure::read('app.beta.testers');
        if ($username && !empty($beta_testers[$username])) {
            $responseData['User']['beta'] = true;
            $responseData['User']['beta_config'] = (array) Configure::read('app.beta.config_writes');
        }
        
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
        $this->Api->respond($this);
    }
    
    function index()
    {
        App::import('Sanitize');
            
        $where = array();

        $limit = $this->Api->getParameter('limit', array('integer', '>0', '<=100'), 100);
        $offset = $this->Api->getParameter('offset', array('integer', '>=0'), 0);
        $q = $this->Api->getParameter('filter.q');
        $types = $this->Api->getParameter('filter.type');
        
        if (!empty($q)) {
            $q = Sanitize::escape($q);
            $where []= "(CONCAT(User.last_name, ' ', User.first_name) LIKE '%$q%' OR CONCAT(User.first_name, ' ', User.last_name) LIKE '%$q%' OR User.dni LIKE '%$q%')";
        }
        
        if (!empty($types)) {
            $whereTypes = array();
            foreach (explode(',', $types) as $type) {
                $type = trim($type);
                if (!empty($type)) {
                    $type = Sanitize::escape($type);
                    $whereTypes []= "User.type = '$type'";
                }
            }
            if (!empty($whereTypes)) {
                $where []= '(' . implode(' OR ', $whereTypes) . ')';
            }
        }
        
        if ($this->Api->getStatus() === 'success') {
            $where = empty($where)? '' : 'WHERE ' . implode(' AND ', $where);
            $users = $this->User->query(
                "SELECT User.* FROM users User $where ORDER BY User.last_name ASC, User.first_name ASC LIMIT $limit OFFSET $offset"
            );
            $this->Api->setData($users);
        }

        $this->Api->respond($this);
    }
    
}
