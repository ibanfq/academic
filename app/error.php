<?php

if (!class_exists('ErrorHandler')) {
    include_once LIBS . 'error.php';
}

class AppError extends ErrorHandler {
    function __construct($method, $messages) {
        if (class_exists('Configure') && Configure::read('app') === null) {
            Configure::load('app');
        }

        if (! class_exists('ConnectionManager')) {
            App::import('Model', 'ConnectionManager', false);
        }

        if (! class_exists('ConnectionManager')) {
            return parent::__construct($method, $messages);
        }

        $db = ConnectionManager::getDataSource('default');

        if (! $db->isConnected()) {
            return parent::__construct($method, $messages);
        }

        if ($method === 'missingTable' && $messages[0]['table'] === 'log') {
            return parent::__construct($method, $messages);
        }

        if (($method === 'missingController' || $method === 'missingAction') && class_exists('Configure') && ! Configure::read('debug')) {
            if (empty($_COOKIE[Configure::read('Session.cookie')])) {
                return parent::__construct($method, $messages);
            }
            if (!empty($_SERVER['HTTP_HOST'])) {
                $pos = empty($_SERVER['HTTP_REFERER']) ? false : strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']);
                if ($pos === false || $pos > 8) {
                    return parent::__construct($method, $messages);
                }
            }
        }

        $log_content = array(
            'messages' => $messages,
            'request' => array(
                'uri' => $this->_getRequestUri(),
                'method' => array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : '',
                'params' => $_GET,
                'cookie' => $_COOKIE,
                'user_agent' => array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : '',
                'referer' => array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : '',
                'body' => $this->_sanitizePost($_POST)
            )
        );

        $data = array(
            'institution_id' => null,
            'channel' => 'error_handler',
            'description' => $method,
            'ip' => $this->_getClientIP(),
            'content' => json_encode($log_content, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            'client_date' => null,
            'server_date' => date('Y-m-d H:i:s')
        );

        $query = array(
            'table' => 'log',
            'fields' => implode(', ', array_map(array($db, 'name'), array_keys($data))),
            'values' => implode(', ', array_map(array($db, 'value'), $data))
        );

        $db->execute($db->renderStatement('create', $query));

        return parent::__construct($method, $messages);
    }

    function _getRequestUri() {
        if (PHP_SAPI === 'cli') {
            return $_SERVER['SCRIPT_FILENAME'];
        }

        return \array_key_exists('HTTP_HOST', $_SERVER)
            ? $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
            : $_SERVER['REQUEST_URI'];
    }

    function _getClientIP($safe = true) {
        if (!$safe && env('HTTP_X_FORWARDED_FOR') != null) {
            $ipaddr = preg_replace('/(?:,.*)/', '', env('HTTP_X_FORWARDED_FOR'));
        } else {
            if (env('HTTP_CLIENT_IP') != null) {
                $ipaddr = env('HTTP_CLIENT_IP');
            } else {
                $ipaddr = env('REMOTE_ADDR');
            }
        }

        if (env('HTTP_CLIENTADDRESS') != null) {
            $tmpipaddr = env('HTTP_CLIENTADDRESS');

            if (!empty($tmpipaddr)) {
                $ipaddr = preg_replace('/(?:,.*)/', '', $tmpipaddr);
            }
        }
        return trim($ipaddr);
    }

    function _sanitizePost($content) {
        foreach ($content as $k => $v) {
            if (is_array($v)) {
                $content[$k] = $this->_sanitizePost($content[$k]);
            } elseif (!empty($v) && in_array($k, ['password', 'old_password', 'new_password', 'password_confirmation'], true)) {
                $content[$k] = '*****';
            } elseif (is_string($v)) {
                $content[$k] = \strlen($v) > 300 ? \substr($v, 0, 300) . '...' : $v;
            }
        }
        return $content;
    }
}
