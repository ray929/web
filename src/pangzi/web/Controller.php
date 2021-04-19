<?php

namespace pangzi\web;

/**
 *  控制器基类 
 * 
 * 
 */
class Controller
{
    protected ?string $layout = null;
    protected $tplVars = [];
    protected $return = ['code' => 400, 'result' => false, 'msg' => '', 'data' => []];
    protected $isjson = false;
    protected ?Request $request = null;
    protected ?App $app = null;

    public function __construct(Request $request , App $app) 
    {
        $this->request = $request;
        $this->app = $app;
    }
    public function __destruct() {
        if($this->isjson) {
            $GLOBALS['TYPE'] = 'application/json';
            header('Content-Type: application/json');
            $ohtml = ob_get_clean();
            if ($ohtml) $this->return['ohtml'] = $ohtml;
            echo json_encode($this->return);
        }
    }
    public function init(): void
    {
        session_set_save_handler(new Session(), true);
    }

    protected function assign($var, $value)
    {
        $this->tplVars[$var] = $value;
    }
    protected function display(?string $action='' , ?string $controller='' , ?string $module='')
    {
        
        $latte = new \Latte\Engine;
        $latte->setTempDirectory(G::$path_runtime . '/latte');
        // if (G::$dev) {
        //     $latte->setAutoRefresh(true);
        // }
        $latte->setAutoRefresh(true);

        if($this->layout!=null) {
            $finder = function (\Latte\Runtime\Template $template) {
                if (!$template->getReferenceType()) {
                    // it returns the path to the parent template file
                    return G::$path_app . '/views/layouts/' . $this->layout . '.latte';
                }
            };
            $latte->addProvider('coreParentFinder', $finder);
        }
        if(!$module) $module = $this->request->module;
        if(!$controller) $controller = $this->request->controller;
        if(!$action) $action = $this->request->action;
        
        try {
            $this->tplVars['RUNTIME'] = round((microtime(true)-G::$time_start)*1000);
            $latte->render(G::$path_app . '/views/' . $module . '/' . $controller . '/' .  $action .'.latte', $this->tplVars);
        } catch(\Exception $e) {
            die($e->getMessage());
        }
    }
    protected function get($key):?string {
        if(isset($_GET[$key])) return $_GET[$key];
        else return null;
    }
    protected function post($key):?string {
        if(isset($_POST[$key])) return $_POST[$key];
        else return null;
    }
    protected function parameter($key):?string {
        if(isset($this->request->parameter[$key])) return $this->request->parameter[$key];
        else return null;
    }
}