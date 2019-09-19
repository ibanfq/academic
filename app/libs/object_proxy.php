<?php

/**
 * ObjectProxy class
 */
class ObjectProxy  {
    var $__proxyObject;
    var $__proxyOverloads = array();

    function __construct($object)
    {
        $this->__proxyObject = $object;
    }

    function setProxyOverload($name, $overload)
    {
        $this->__proxyOverloads[$name] = $overload;
    }

    function unsetProxyOverload($name, $overload)
    {
        unset($this->__proxyOverloads[$name]);
    }

    function __call($method, $arguments)
    {
        if (isset($this->__proxyOverloads[$method])) {
            return call_user_func(
                $this->__proxyOverloads[$method],
                $this->__proxyObject,
                $arguments
            );
        }

        return call_user_func_array($this->__proxyObject, $arguments);
    }

    function __get($name)
    {
        return $this->__proxyObject->$name;
    }
    
    function __set($name, $value)
    {
        $this->__proxyObject->$name = $value;
    }
    
    function __isset($name)
    {
        return isset($this->__proxyObject->$name);
    }
    
    function __unset($name)
    {
        unset($this->__proxyObject->$name);
    }
    
    function __toString()
    {
        return (string) $this->__proxyObject;
    }
    
    function __invoke()
    {
        return call_user_func_array($this->__proxyObject, func_get_args());
    }
    
    function __clone()
    {
        $this->__proxyObject = clone $this->__proxyObject;
    }
}
