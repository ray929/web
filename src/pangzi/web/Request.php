<?php
namespace pangzi\web;
class Request {
    public string $module = 'index';
    public string $controller = 'index';
    public string $action = 'index';
    public string $userIp = '';
    public array $parameter = [];
    public function __construct(string $module ,string $controller , string $action , array $parameter) {
        $this->module = $module;
        $this->controller = $controller;
        $this->action = $action;
        $this->parameter = $parameter;


        if (isset($_SERVER["HTTP_X_YOUR_IP"]) && !empty($_SERVER["HTTP_X_YOUR_IP"]) && $this->is_public_ip($_SERVER["HTTP_X_YOUR_IP"])) {
        } elseif (isset($_SERVER["HTTP_X_REAL_IP"]) && !empty($_SERVER["HTTP_X_REAL_IP"]) && $this->is_public_ip($_SERVER["HTTP_X_REAL_IP"])) {
        } elseif (isset($_SERVER["HTTP_CDN_SRC_IP"]) && !empty($_SERVER["HTTP_CDN_SRC_IP"]) && $this->is_public_ip($_SERVER["HTTP_CDN_SRC_IP"])) {
        } elseif (isset($_SERVER["HTTP_X_VARNISH_FOR"]) && !empty($_SERVER["HTTP_X_VARNISH_FOR"]) && $this->is_public_ip($_SERVER["HTTP_X_VARNISH_FOR"], $_ip)) {
        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && !empty($_SERVER["HTTP_X_FORWARDED_FOR"]) && $this->is_public_ip($_SERVER["HTTP_X_FORWARDED_FOR"], $_ip)) {
        } else {
            $this->userIp = $_SERVER["REMOTE_ADDR"];
        }
    }
    public function get($key):?string {
        if(isset($_GET[$key])) return $_GET[$key];
        else return null;
    }
    public function post($key):?string {
        if(isset($_POST[$key])) return $_POST[$key];
        else return null;
    }
    public function parameter($key):?string {
        if(isset($this->request->parameter[$key])) return $this->request->parameter[$key];
        else return null;
    }
    private function is_public_ip($ip) {
        $_ip = null;
        $ip = explode(',', $ip);
        if(is_array($ip)) {
            foreach ($ip as $_v) {
                $_v = trim($_v);
                if(preg_match("/^::ffff:(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/" ,$_v , $matches)) {
                    $_v = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[4]}";
                }
                if(!preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/" , $_v)) {
                    continue;
                } elseif(preg_match("/^10\./" , $_v)) {
                    continue;
                } elseif(preg_match("/^172\.(\d{1,3})\./" , $_v , $matchs)) {
                    if($matchs[1]>=16 && $matchs[1]<=31) {
                        continue;
                    } else {
                        if(!$_ip) $_ip = $_v;
                        break;
                    }
                } elseif(preg_match("/^192\.168\./" , $_v)) {
                    continue;
                } elseif(preg_match("/^127\./" , $_v)) {
                    continue;
                } else {
                    if(!$_ip) $_ip = $_v;
                    break;
                }
            }
        }
        if($_ip) {
            return true;
            $this->userIp = $_ip;
        } else return false;
    
    }
}