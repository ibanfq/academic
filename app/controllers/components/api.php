<?php

/**
 * Api component
 */
class ApiComponent extends Object {
  var $_data = null;
  var $_fail_data = array();
  var $_error_message = null;
  var $_error_code = null;
  var $_error_data = null;
  var $_request = null;
  
  function call($verb, $url, $data = null) {
    $old_request_method = $_SERVER['REQUEST_METHOD'];
    $old_get = &$_GET;
    $old_post = &$_POST;
    $old_request = &$_REQUEST;

    $url = parse_url($url);
    $_SERVER['REQUEST_METHOD'] = $verb;
    if (isset($url['query'])) {
      parse_str($url['query'], $_GET);
    } else {
      $_GET = array();
    }
    $_POST = (array) $data;
    $_REQUEST = array_merge($_GET, $_POST);
    
    $dispatcher = new Dispatcher();
    $content = $dispatcher->dispatch($url['path'], array('return' => true));
    
    $_REQUEST = &$old_request;
    $_POST = &$old_post;
    $_GET = &$old_get;
    $_SERVER['REQUEST_METHOD'] = $old_request_method;
    
    return $content;
  }
  
  function getParameter($name, $filters = null, $default = null) {
    $value = $_REQUEST;
    $filters = (array)$filters;

    foreach (explode('.', $name) as $path) {
      if (!isset($value[$path])) {
        if (($default === null || $value === '') && in_array('required', $filters)) {
          $this->addFail($name, 'Required');
          return null;
        }
        return $default;
      }
      $value = $value[$path];
    }
    
    foreach ($filters as $filter) {
      switch ($filter) {
        case 'required':
          if ($value === null || $value === '') {
            $this->addFail($name, 'Required');
            return null;
          }
          break;
          
        case 'boolean':
          if ($value === '0' || $value === 'false') {
            $value = false;
          } elseif ($value === '1' || $value === 'true') {
            $value = true;
          } elseif (empty($value)) {
            $value = null;
          } else {
            $this->addFail($name, 'Invalid');
            return null;
          }
          break;
          
        case 'integer':
          if (is_numeric($value) && intval($value) == $value) {
            $value = intval($value);
          } elseif (empty($value)) {
            $value = null;
          } else {
            $this->addFail($name, 'Invalid');
            return null;
          }
          break;
        
        case 'numeric':
          if (is_numeric($value)) {
            $value = $value + 0;
          } elseif (empty($value)) {
            $value = null;
          } else {
            $this->addFail($name, 'Invalid');
            return null;
          }
          break;
        
        case 'password':
          if ($value !== null) {
            $value = Security::hash($value, null, true);
          }
          break;
          
        default:
          if ($value !== null && ($filter[0] === '<' || $filter[0] === '>')) {
            $or_equal = $filter[1] === '='? '=' : false;
            $number = 1 * substr($filter, $or_equal? 2 : 1);
            if ($filter[0] === '<') {
              if (($or_equal && $value > $number) || (!$or_equal && $value >= $number)) {
                $this->addFail($name, "The number must be <$or_equal $number");
                return null;
              }
            } elseif ($filter[0] === '>') {
              if (($or_equal && $value < $number) || (!$or_equal && $value <= $number)) {
                $this->addFail($name, "The number must be >$or_equal $number");
                return null;
              }
            }
          }
          
      }
    }
    
    return $value;
  }
  
  function setData($data) {
    $this->_data = $data;
  }
  
  function addFail($object, $message) {
    $this->_fail_data[$object] = $message;
  }
  
  function setError($message, $code = null, $data = null) {
    $this->_error_message = $message;
    $this->_error_code = $code;
    $this->_error_data = $data;
  }
  
  function clearFails() {
    $this->_fail_data = array();
  }
  
  function clearError() {
    $this->_error_message = null;
    $this->_error_code = null;
    $this->_error_data = null;
  }
  
  function getStatus() {
    if (!empty($this->_error_message)) {
      return 'error';
    } elseif (!empty($this->_fail_data)) {
      return 'fail';
    } else {
      return 'success';
    }
  }
  
  function getData() {
    switch($this->getStatus()) {
      case 'success';
        return $this->_data;
      case 'fail':
        return $this->_fail_data;
      case 'error':
        return $this->_error_data;
        break;
    }
  }
  
  function respond(&$controller) {
    $response = array('status' => $this->getStatus());
    
    switch($response['status']) {
      case 'success';
        $response['data'] = $this->_sanitizeData($controller, $this->_data);
        break;
      case 'fail':
        $response['data'] =  $this->_fail_data;
        break;
      case 'error':
        $response['message'] = $this->_error_message;
        $response['code'] = $this->_error_code;
        $response['data'] = $this->_error_data;
        break;
    }
    
    if (empty($controller->params['return'])) {
      header('Cache-Control: no-cache, must-revalidate');
      header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
      header('Content-type: application/json');
      if ($response['status'] !== 'success') {
        header('HTTP/1.1 400 Bad Request');
      }
      App::import('Helper', 'Javascript');
      $javascript = new JavascriptHelper();
      $controller->output = $javascript->object($response);
    } else {
      $controller->output = $response;
    }
  }
  
  function _sanitizeData(&$controller, $data) {
    if (empty($data)) {
      return $data;
    }
    if (is_int(key($data))) {
      foreach ($data as $i => $models) {
        $data[$i] = $this->_sanitizeData($controller, $models);
      }
    } else {
      foreach ($data as $model => $values) {
        if (array_key_exists('password', $values)) {
          unset($data[$model]['password']);
        }
        if (array_key_exists('dni', $values) || array_key_exists('phone', $values)) {
          if ($controller->Auth->user('id') === null || ($controller->Auth->user('type') === 'Estudiante' && $controller->Auth->user('id') != $values['id'])) {
            unset($data[$model]['dni']);
            unset($data[$model]['phone']);
          }
        }
        foreach ($values as $field => $value) {
          if (is_array($value)) {
            $data[$model][$field] = $this->_sanitizeData($controller, $value);
          }
        }
      }
    }
    return $data;
  }
}
