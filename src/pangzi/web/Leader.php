<?php
namespace pangzi\web;
class Leader {
    static private ?\Medoo\Medoo $db=null;
    static public function Db() : \Medoo\Medoo {
        if(!self::$db) {
            $config_db = Config::get('db');
            self::$db = new \Medoo\Medoo([
                'database_type' => 'mysql',
                'database_name' => $config_db['database_name'],
                'server' => $config_db['server'],
                'username' => $config_db['username'],
                'password' => $config_db['password'],
                'charset' => $config_db['charset'],
                'port' => $config_db['port'],
                'prefix' => $config_db['prefix'],
                'option' => [
                    \PDO::ATTR_CASE => \PDO::CASE_NATURAL,
                    \PDO::ATTR_PERSISTENT => true,
                ],
            ]);
        }
        return self::$db;
    }
}