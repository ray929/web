<?php

namespace pangzi\web;

class Loader
{
    /* 路径映射 */
    public static $vendorMap = array(
        'app' => G::$path_app,
    );

    /**
     * 自动加载器
     */
    public static function autoload($class)
    {
        $file = self::findFile($class);
        if ($file && is_file($file)) {
            include $file;
        }
    }

    /**
     * 解析文件路径
     */
    private static function findFile($class)
    {
        // echo __FILE__ .' line'. __LINE__ .' find class: ' . $class . ' ' . round((microtime(true)-G::$time_start)*1000).'ms<br />';
        $vendor = substr($class, 0, strpos($class, '\\')); // 顶级命名空间
        if(!isset(self::$vendorMap[$vendor])) return false;
        $vendorDir = self::$vendorMap[$vendor]; // 文件基目录
        $filePath = substr($class, strlen($vendor)) . '.php'; // 文件相对路径
        return strtr($vendorDir . $filePath, '\\', DIRECTORY_SEPARATOR); // 文件标准路径
    }

}