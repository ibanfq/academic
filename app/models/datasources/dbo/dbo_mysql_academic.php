<?php
APP::import('Core', 'DboMysql');
class DboMysqlAcademic extends DboMysql {
    function connect() {
        $connected = parent::connect();
        if ($connected) {
            $this->_execute('SET sql_mode=(SELECT REPLACE(REPLACE(@@sql_mode, "ONLY_FULL_GROUP_BY", ""), "STRICT_TRANS_TABLES", ""))');
        }
        return $connected;
    }
}
