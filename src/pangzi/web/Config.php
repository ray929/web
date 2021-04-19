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
            $configFile = G::$path_app . '/config.php';
            if (file_exists($configFile)) {
                self::$config = include($configFile);
            }
            if (G::$dev) {
                $configFile_dev = G::$path_app . '/config.dev.php';
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
