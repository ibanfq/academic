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
  
  
  function getParameter($name, $filters = null, $default = null) {
    $value = $_REQUEST;

    foreach (explode('.', $name) as $path) {
      if (!isset($value[$path])) {
        return $default;
      }
      $value = $value[$path];
    }
    
    foreach ((array) $filters as $filter) {
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
  
  function getStatus() {
    if (!empty($this->_error_message)) {
      return 'error';
    } elseif (!empty($this->_fail_data)) {
      return 'fail';
    } else {
      return 'success';
    }
  }
  
  function setViewVars(&$controller) {
    switch($this->getStatus()) {
      case 'success';
        $controller->set('status', 'success');
        $controller->set('data', $this->_sanatizeData($controller, $this->_data));
        break;
      case 'fail':
        $controller->set('status', 'fail');
        $controller->set('data', $this->_fail_data);
        break;
      case 'error':
        $controller->set('status', 'error');
        $controller->set('message', $this->_error_message);
        $controller->set('code', $this->_error_code);
        $controller->set('data', $this->_error_data);
        break;
    }
  }
  
  function _sanatizeData(&$controller, $data) {
    if (empty($data)) {
      return $data;
    }
    if (is_int(key($data))) {
      foreach ($data as $i => $models) {
        $data[$i] = $this->_sanatizeData($controller, $models);
      }
    } else {
      foreach ($data as $model => $values) {
        if (array_key_exists('password', $values)) {
          unset($data[$model]['password']);
        }
        if (array_key_exists('dni', $values) && $controller->Auth->user('type') === 'Estudiante' && $controller->Auth->user('id') != $values['id']) {
          unset($data[$model]['dni']);
        }
      }
    }
    return $data;
  }
}
