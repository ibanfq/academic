<?php

/**
 * Simple acl component
 */
class AclComponent extends Object {
  var $components = array('Auth');
  
  function check($resource_name, $user = null) {
    if (empty($user)) {
        $user = $this->Auth->user();
    }

    $user_type = isset($user['type']) ? $user['type'] : $user['User']['type'];
    $acl_list = Configure::read('app.acl');

    return $user_type && !empty($acl_list[$user_type][$resource_name]) || !empty($acl_list['all'][$resource_name]);
  }
}
