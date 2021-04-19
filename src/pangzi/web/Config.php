<?php

namespace pangzi\web;

class Config
{
    static private ?array $config = null;
    static public function get($key)
    {
        self::load();
        if(array_key_exists($key, self::$config)) {
            return self::$config[$key];
        }
    }

    private static function load()
    {
        if (!self::$config) {
            $configFile = APP_PATH . '/config.php';
            if (file_exists($configFile)) {
                self::$config = include($configFile);
            }
            if (defined('PANGZI_DEV') && PANGZI_DEV) {
                $configFile_dev = APP_PATH . '/config.dev.php';
                if (file_exists($configFile_dev)) {
                    $config_dev = include($configFile_dev);
                    foreach (self::$config as $key => $value) {
                        if(is_array($config_dev[$key])) {
                            foreach ($config_dev[$key] as $key1=>$value1) {
                                self::$config[$key][$key1] = $value1;
                            }
                        }
                    }
                }
            }
        }
    }
}
