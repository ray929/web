<?php

namespace pangzi\web;

use \Symfony\Component\Cache\Adapter\FilesystemAdapter;
use \Symfony\Contracts\Cache\ItemInterface;

class Admin
{
    public ?array $adminuser = null;
    public ?array $admingroup = null;
    public array $adminuser_arr = [];

    private ?Request $request = null;
    private bool $isLayout = true;
    private array $tplVars = ['html_title'=>['Admin']];
    private array $return = ['code' => 400, 'result' => false, 'msg' => '', 'data' => []];
    private bool $isJson = false;
    private bool $isInFrame = false;
    private ?\Symfony\Component\Cache\Adapter\FilesystemAdapter $cache = null;



    private string $title = 'Admin';
    private string $static_url = '';
    private string $default_page = '/admin/index/index';
    private string $runtime_path = '';
    private string $session_prefix = 'admin_fqxCgbrv_';
    private string $secretKey = 'aX75MbQf3HRdcPNy';

    public function __construct(Request $request, array $config = [])
    {
        $this->static_url = G::$path_assets.'/admin';
        $this->runtime_path = G::$path_runtime . '/admin';
        $this->request = $request;
        if (strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false || $this->request->get('json')) {
            $this->isJson = true;
        }
        empty($config['title']) or $this->title = $config['title'];
        empty($config['static_url']) or $this->static_url = $config['static_url'];
        empty($config['default_page']) or $this->default_page = $config['default_page'];
        empty($config['runtime_path']) or $this->runtime_path = $config['runtime_path'];
        empty($config['session_prefix']) or $this->session_prefix = $config['session_prefix'];
        empty($config['static_url']) or $this->static_url = $config['static_url'];
        empty($config['secretKey']) or $this->secretKey = $config['secretKey'];

        is_dir($this->runtime_path) or mkdir($this->runtime_path, 0777, true);
        $this->cache = new FilesystemAdapter(directory : $this->runtime_path . '/cache');
    }
	public function __destruct()
	{
		if($this->isJson) {
			$ohtml = ob_get_clean();
			header('Content-Type: application/json;charset=utf8');
			if ($ohtml) $this->return['ohtml'] = $ohtml;
			echo json_encode($this->return);
		}
	}
    public function checklogin()
    {
        session_start();
        if ('login' == $this->request->get('act')) {
            $serial = $this->request->get('serial');
            if ($serial) {
                $serial_arr = explode('-', $serial);
                if (count($serial_arr) == 2) {
                    if ($serial_arr[1] == md5($serial_arr[0] . $this->secretKey)) {
                    } else {
                        $serial = null;
                    }
                } else {
                    $serial = null;
                }
            }


            if ('submit' === $this->request->get('func')) {
                if (!$serial) {
                    $this->error(code:0,msg:'登录状态错误,请刷新页面重试', data:['reload_vcode' => false, 'empty_vcode' => false]);
                }
                $_username = trim($this->request->post($this->request->post('fik1')));
                $_password = trim($this->request->post($this->request->post('fik2')));
                $_vcode = trim($this->request->post('vcode'));
                if (!$_username) {
                    $this->error(code:0,msg:'请输入用户名', data:['reload_vcode' => false, 'empty_vcode' => false]);
                }
                if (!$_password) {
                    $this->error(code:0,msg:'请输入密码', data:['reload_vcode' => false, 'empty_vcode' => false]);
                }
                if (!$_vcode) {
                    $this->error(code:0,msg:'请输入验证码', data:['reload_vcode' => false, 'empty_vcode' => false]);
                }
                if (!preg_match("/^([abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789]{4})$/", $_vcode)) {
                    $this->error(code:0,msg:'验证码不符合规则', data:['reload_vcode' => false, 'empty_vcode' => true]);
                }

                $vcodeData = $this->cache->getItem('login.vcode.' . $serial);
                $vcode = $vcodeData->get();
                if (!preg_match("/^([abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789]{4})$/", $vcode)) {
                    $this->error(code:0,msg:'验证码超时,请重试', data:['reload_vcode' => true, 'empty_vcode' => true]);
                }
                if ($_vcode != $vcode) {
                    $this->error(code:0,msg:'验证码错误', data:['reload_vcode' => false, 'empty_vcode' => true]);
                }
                $this->cache->deleteItem('login.vcode.' . $serial);
                $_adminuser = Leader::Db()->get('adminusers', '*', ['username' => $_username,'isdel'=>0]);
                if (!$_adminuser) {
                    $this->error(code:0,msg:'用户不存在', data:['reload_vcode' => true, 'empty_vcode' => true]);
                }
                if ($_adminuser['password'] != md5($_password . $this->secretKey)) {
                    $this->error(code:0,msg:'密码错误', data:['reload_vcode' => true, 'empty_vcode' => true]);
                }
                $_SESSION[$this->session_prefix . 'adminuser_id'] = $_adminuser['adminuser_id'];

                $data = array(
                    'login_ip'        =>    $this->request->userIp,
                    'login_time'    =>    time(),
                );
                Leader::Db()->update('adminusers' , $data , ['adminuser_id'    =>    $_adminuser['adminuser_id']]);
                $this->success('登录成功');
                exit;
            }


            if ('vcode' == $this->request->get('func')) {
                if (!$serial) $this->error('错误');
                $GLOBALS['TYPE'] = 'image/png';
                header('Content-Type: image/png');
                $vcode = new ValidateCode();
                $vcode->doimg();
                $vcodeData = $this->cache->getItem('login.vcode.' . $serial);
                $vcodeData->expiresAfter(300);
                $vcodeData->set($vcode->getCode());
                $this->cache->save($vcodeData);
                exit;
            }
            if ('showcode' == $this->request->get('func')) {
                if (!$serial) $this->error('错误');
                $vcodeData = $this->cache->getItem('login.vcode.' . $serial);
                echo $vcodeData->get();
                exit;
            }
            $this->assign('fik1', 'i_' . md5('fik1_' . microtime()));
            $this->assign('fik2', 'i_' . md5('fik2_' . microtime()));
            $serial1 = md5(microtime(true) . $this->request->userIp . mt_rand(1000, 9999));
            $serial2 = md5($serial1 . $this->secretKey);
            $this->assign('serial', $serial1 . '-' . $serial2);
            $this->isLayout = false;
            $this->inner_display('login', null);
            exit;
        }

        if (empty($_SESSION[$this->session_prefix . 'adminuser_id'])) {
            $this->error('请登录', $this->default_page . '?act=login');
        }
        $adminuser_id = (int) $_SESSION[$this->session_prefix . 'adminuser_id'];
        if ($adminuser_id <= 0) {
            $this->error('请登录', $this->default_page . '?act=login');
        }
        $this->adminuser = Leader::Db()->get('adminusers', '*', ['adminuser_id' => $adminuser_id]);
        if (!$this->adminuser || $this->adminuser['admingroup_id'] <= 0) {
            unset($_SESSION[$this->session_prefix . 'adminuser_id']);
            $this->error('您没有权限访问', $this->default_page . '?act=login');
        }
        $this->admingroup = Leader::Db()->get('admingroups', '*', ['isdel' => 0, 'admingroup_id' => $this->adminuser['admingroup_id']]);
        if (!$this->admingroup) {
            unset($_SESSION[$this->session_prefix . 'adminuser_id']);
            $this->error('您没有权限访问', $this->default_page . '?act=login');
        }


        $this->admingroup['pages'] = explode('|', $this->admingroup['pages']);
        $this->admingroup['menus'] = explode('|', $this->admingroup['menus']);


        $thispage = Leader::Db()->get('adminpages', '*', ['controller' => $this->request->controller, 'admingroup_id' => $this->request->action, 'disabled' => 0]);

        if ($this->request['action'] != 'index' && !$thispage) {
            $this->error('未定义');
        }
        if ($this->request['action'] != 'index' && !$thispage['common']) {
            if ($this->adminuser['admingroup_id'] != 1 && !in_array($this->request['controller'] . '/' . $this->request['action'], $this->admingroup['pages'])) {
                $this->error('无权限');
            }
        }
    }
    protected function inner_display(string $tpl)
    {
        $latte = new \Latte\Engine;
        $latte->setTempDirectory($this->runtime_path . '/latte');
        // if (G::$dev) {
        //     $latte->setAutoRefresh(true);
        // }
        $latte->setAutoRefresh(true);

        if ($this->isLayout) {
            $layout = 'page';
            if ($this->isInFrame) {
                $layout = 'pageinframe';
            }
            $finder = function (\Latte\Runtime\Template $template) use ($layout) {
                if (!$template->getReferenceType()) {
                    // it returns the path to the parent template file
                    return __DIR__ . '/views/admin/layout-' . $layout . '.latte';
                }
            };
            $latte->addProvider('coreParentFinder', $finder);
        }

        $this->tplVars['RUNTIME'] = round((microtime(true) - G::$time_start) * 1000);
        $this->tplVars['static_url'] = $this->static_url;
        $this->tplVars['default_page'] = $this->default_page;
        $this->tplVars['title'] = $this->title;
        $this->tplVars['html_title'] = implode(' - ' , array_reverse($this->tplVars['html_title']));

        try {
            $latte->render(__DIR__ . '/views/admin/' . $tpl . '.latte', $this->tplVars);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }
    public function display(?string $action = '', ?string $controller = '', ?string $module = '')
    {
    }
    protected function assign($var, $value)
    {
        $this->tplVars[$var] = $value;
    }
    protected function error($msg='', $url = null, $code = 400, $top = false , $data=[])
    {
        if ($this->isJson) {
            $this->return['code'] = $code;
            $this->return['data'] = $data;
            $this->return['result'] = false;
            $this->return['url'] = $url;
            $this->return['msg'] = $msg;
            exit;
        }
        $this->assign('top', $top);
        $this->assign('msg', $msg);
        $this->assign('url', $url);
        $this->assign('data', $data);
        $this->inner_display('error');
        exit;
    }
    protected function success($msg = '', $data = [] , $url = null, $top = false)
    {
        if ($this->isJson) {
            $this->isjson = true;
            $this->return['code'] = 0;
            $this->return['data'] = $data;
            $this->return['result'] = true;
            $this->return['url'] = $url;
            $this->return['msg'] = $msg;
            exit;
        }
        $this->assign('top', $top);
        $this->assign('msg', $msg);
        $this->assign('data', $data);
        $this->assign('url', $url);
        $this->inner_display('success');
        exit;
    }
}
