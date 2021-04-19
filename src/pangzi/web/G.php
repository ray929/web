<?php

namespace pangzi\web;

class G
{
    public static string $path_app = '';
    public static string $path_root = '';
    public static string $path_runtime = '';
    public static string $path_assets = '/public/static/web-assets';
    public static bool $dev = true;
    public static float $time_start = microtime(true);
}