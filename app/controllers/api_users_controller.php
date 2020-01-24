<?php
class ApiUsersController extends AppController {
    var $name = 'Users';
    var $isApi = true;

    function _authorize(){
        if (!parent::_authorize()) {
            return false;
        }

        $no_institution_actions = array('me', 'login');
        $administrator_actions = array('delete');
        $administrative_actions = array('edit', 'add');
        $neither_student_nor_concierge = array('index', 'view');
        $student_actions = array();

        if (array_search($this->params['action'], $no_institution_actions) === false && ! Environment::institution('id')) {
            $this->Api->setError('No se ha especificado la institución en la url de la petición.', 400);
            $this->Api->respond($this);
            return;
        }

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
        $this->Api->setData($this->_getCurrentUserInfo());
        $this->Api->respond($this);
    }

    function login()
    {
        $responseData = $this->_getCurrentUserInfo();
        
        $issuer    = Configure::read('app.issuer');
        $issuedAt  = time();
        $tokenId   = base64_encode($issuer.$issuedAt.mcrypt_create_iv(16));
        $secretKey = base64_decode(Configure::read('Security.secret'));

        /*
        * Create the token as an array
        */
        $jwtData = array(
            'iat'  => $issuedAt, // Issued at: time when the token was generated
            'jti'  => $tokenId,  // Json Token Id: an unique identifier for the token
            'iss'  => $issuer,   // Issuer
            'data' => array(     // Data related to the signed user
                'id'       => $this->Auth->user('id'),       // id from the auth user
                'username' => $this->Auth->user('username'), // username from the auth user
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

    function _getCurrentUserInfo()
    {
        $responseData = $this->Auth->user();

        $model = $this->Auth->getModel();

        $userTypes = $model->find('list', array(
            'fields' => array("{$model->alias}.type"),
            'conditions' => array(
                "{$model->alias}.dni" => $responseData[$model->alias]['dni']
            ),
            'order' => "FIELD({$model->alias}.type, 'Profesor', 'Administrador', 'Administrativo', 'Conserje', 'Becario', 'Estudiante')",
        ));

        unset($responseData[$model->alias]['id']);
        unset($responseData[$model->alias]['type']);
        $responseData[$model->alias]['types'] = $userTypes;
        
        $currentInstitution = Environment::institution();

        if ($currentInstitution) {
            $responseData['Institution'] = $this->_addInstitutionConfig($currentInstitution['Institution'], $responseData);
        } else {
            $responseData['Institutions'] = Set::extract(Environment::userInstitutions(), '{n}.Institution');
            foreach ($responseData['Institutions'] as $i => $institution) {
                $responseData['Institutions'][$i] = $this->_addInstitutionConfig($responseData['Institutions'][$i], $responseData);
            }
        }

        return $responseData;
    }

    function _addInstitutionConfig($institution, $user)
    {
        $configLoader = function ($institution) {
            $path = CONFIGS . "institutions/{$institution['id']}/app.php";
            if (is_readable($path)) {
                include $path;
            }
            return isset($config)? $config : array();
        };
        $optionsLoader = function ($institution) {
            $path = CONFIGS . "institutions/{$institution['id']}/app.options.php";
            return is_readable($path) ? (array) include $path : array();
        };
        $config = $configLoader($institution);
        $options = $optionsLoader($institution);
        $institution['competences'] = !empty($config['app']['competence']['enable']);
        $username = $user['User']['username'];
        $beta_testers = (array) (isset($options['beta']['testers']) ? $options['beta']['testers'] : array());
        if (!empty($beta_testers[$username])) {
            if (!empty($config['app']['beta']['config_writes']['app.competence.enable'])) {
                $institution['competences'] = true;
            }
        }
        return $institution;
    }
    
    function index()
    {
        App::import('Core', 'Sanitize');
        $db = $this->User->getDataSource();
        
        $joins_for_where = '';
        $where = array();

        $joins_for_where .= " INNER JOIN users_institutions UserInstitution ON UserInstitution.user_id = User.id AND UserInstitution.institution_id = {$db->value(Environment::institution('id'))} AND UserInstitution.active";

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
                "SELECT User.* FROM users User $joins_for_where $where ORDER BY User.last_name ASC, User.first_name ASC LIMIT $limit OFFSET $offset"
            );
            $this->Api->setData($users);
        }

        $this->Api->respond($this);
    }
}
