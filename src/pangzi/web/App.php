<?php

namespace pangzi\web;

class App
{
    public function __construct() {
        spl_autoload_register('\\pangzi\\web\\Loader::autoload');
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = preg_replace('/(\\/{2,})/' , '/' , $path);
        $module = $controller = $action = 'index';
        $parameter = [];
        if($path!='' && $path!='/') {
            if(!preg_match("/^(\\/[^\\/\\.]+\\.php)?(.*?)$/" , $path , $matches)) {
                $this->error_html('Error 400 occurred' , 'The uri '.$path.' which you request can not be resolved(0)!');
            }
            if(isset($matches[2])) {
                if(!preg_match_all("/(\\/(\w+))/" , $matches[2] , $matches2)) {
                    $this->error_html('Error 400 occurred' , 'The uri '.$matches[2].' which you request can not be resolved(1)!');
                }
                if(isset($matches2[2])) {
                    foreach($matches2[2] as $i=>$part) {
                        if($i==0) {
                            $module = strtolower($part);
                        } elseif($i==1) {
                            $controller = ucfirst(strtolower($part));
                        } elseif($i==2) {
                            $action = strtolower($part);
                        } else {
                            $parameter[] = $part;
                        }
                    }
                }
            }
        }
        $classController = "\\app\\controllers\\{$module}\\{$controller}Controller";
        $methodAction = "{$action}Action";
        if(!class_exists($classController)) {
            header("HTTP/1.1 404 Not Found");
            $this->error_html('Error 404 occurred' , 'The uri (controller) which you request can not be found!');
        }
        if(!method_exists($classController , $methodAction)) {
            header("HTTP/1.1 404 Not Found");
            $this->error_html('Error 404 occurred' , 'The uri  (action)  which you request can not be found!');
        }
        $controller = new $classController(new Request($module , $controller , $action , $parameter) , $this);
        $controller->init();
        $controller->$methodAction();
    }
    
    public function error_html(string $title , string $message , ?string $ext = ''):void {
    echo <<<HTMLERRORSTR
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error</title>
    <style>
        .container {
            width: 60%;
            margin: 10% auto 0;
            background-color: #f0f0f0;
            padding: 2% 5%;
            border-radius: 10px
        }

        ul {
            padding-left: 20px;
        }

            ul li {
                line-height: 2.3
            }

        a {
            color: #20a53a
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Opps!</h1>
        <h3>{$title}</h3>
        <ul>
            <li>{$message}</li>
        </ul>
        {$ext}
    </div>
    
</body>
</html>

HTMLERRORSTR;
        exit;
    }

}
