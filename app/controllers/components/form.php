<?php

/**
 * Form component
 */
class FormComponent extends Object {

    var $controller;

    //llamado antes de  Controller::beforeFilter()
    function initialize(&$controller) {
        $this->controller =& $controller;
    }

    //llamado tras Controller::beforeFilter()
    function startup(&$controller) {
    }

    function filter($data, $list_name = null) {
        $field = $list_name;
        $is_a_list = false;

        foreach ($data as $key => $value) {
            if ($list_name || $key !== '_Token') {
                if (!$list_name) {
                    $field = $key;
                    $is_a_list = $this->_is_a_list($value);
                }
                
                if (isset($this->controller->fields_fillable)) {
                    if (isset($this->controller->fields_fillable[$field])) {
                        if (!$is_a_list) {
                            $filter = $this->controller->fields_fillable[$field];
                            $data[$key] = array_intersect_key($data[$key], array_flip($filter));
                        }
                    } elseif (! in_array($field, $this->controller->fields_fillable, true)) {
                        $is_a_list = false;
                        unset($data[$key]);
                    }
                }

                if (isset($data[$key]) && isset($this->controller->fields_guarded)) {
                    if (isset($this->controller->fields_guarded[$field])) {
                        if (!$is_a_list) {
                            $filter = $this->controller->fields_guarded[$field];
                            $data[$key] = array_diff_key($data[$key], array_flip($filter));
                        }
                    } elseif (in_array($field, $this->controller->fields_guarded, true)) {
                        $is_a_list = false;
                        unset($data[$key]);
                    }
                }

                if ($is_a_list) {
                    $data[$key] = $this->filter($data[$key], $field);
                }
            }
        }

        return $data;
    }

    function _is_a_list($list) {
        if (! is_array($list)) {
            return false;
        }

        foreach ($list as $row) {
            if (! is_array($row)) {
                return false;
            }
        }
        
        return true;
    }
}
