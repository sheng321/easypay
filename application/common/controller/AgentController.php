<?php

namespace app\common\controller;

/**
 * 代理基础控制器
 * Class AdminController
 * @package controller
 */

class AgentController extends BaseController
{

    /**
     * 开启登录控制
     * @var bool
     */
    protected $is_login = true;

    /**
     * 开启权限控制
     * @var bool
     */
    protected $is_auth = '';

    /**
     * 登录用户信息
     * @var string
     */
    protected $user = '';

    /**
     * 代理配置信息
     * @var array
     */
    protected $UserInfo = [];


    public function __construct()
    {
        parent::__construct();


        //检测来源
        $REFERER = $this->request->server('HTTP_REFERER','');
        $url = $this->request->domain();
        if (strpos($REFERER, $url) !== 0) {
            $session_id  = getSessionid();
            if($session_id   !== session('agent_info.single_key')){
                $this->redirect(url('@agent/login/index'),302);
                session('agent_info', null);
            }
        }


        list( $this->is_login, $this->is_auth,) = [ true, true];

        $this->UserInfo = cache('AgentInfo');

        //检测登录情况
        if ($this->is_login == true) {
            $this->__checkLogin();
        }

        $user =  \app\common\model\Umember::quickGet(session('agent_info.id'));

        //判断是否异常用户
        $this->__checkLock($user);

        //是否单点登入
        if( isset($user['is_single']) && $user['is_single'] == '1'){
            $this->__single($user);
        }


        //绑定谷歌
        if( isset($this->UserInfo['AgentGoole']) && $this->UserInfo['AgentGoole'] == '1'){
            $this->__google($user);
        }


        //判断是否有权限进行访问
        if ($this->is_auth == true) {
            $this->__checkAuth();
        }

        // 登录会员信息
        $this->user = session('agent_info');

    }

    /**
     * 检测登录
     */
    public function __checkLogin()
    {
        $user1 = session('agent_info');
        //判断是否登录
        if (empty($user1)) {
            $data = ['type' => 'error', 'code' => 0, 'msg' => '抱歉，请重新登录！', 'url' => url('@agent/login/index')];
            exceptions($data);
        }
    }


    public function __checkLock($user)
    {
        if(!isset($user['status']) || $user['status'] != 1){
            $data = ['status' => 'error', 'code' => 0, 'msg' => '账号已被冻结，强制退出！', 'url' => url('@agent/login/logout')];
            __log( session('agent_info.nickname').' 账号已被冻结，强制退出！');
            session('agent_info', null);
            exceptions($data);
        }
    }


    /**
     * 检测登录情况
     */
    public function __checkAuth()
    {
        if (\app\common\service\AuthService::checkAgentNode() == false){
            $data = ['type' => 'error', 'code' => 0, 'msg' => '抱歉，您暂无该权限，请联系管理员！', 'url' => url('@agent')];
            exceptions($data);
        }
    }

    /**
     * 单点登入
     */
    public function __single($user)
    {
        $session_id  = getSessionid();
        if($user['single_key'] !== session('user_info.single_key')  ||  $user['single_key'] !== $session_id){
            $data = ['type' => 'error', 'code' => 0, 'msg' => '账号在其它设备登入，强制退出！', 'url' => url('@agent/login/logout')];
            __log( session('agent_info.nickname').' 账号在其它设备登入，强制退出！');
            session('agent_info', null);
            exceptions($data);
        }
    }

    public function __google($user)
    {
        if(empty($user['google_token']) && $this->request->controller() !== 'Index' && $this->request->action() !== 'save_google' && $this->request->action() !== 'getmenu' ){
            $data = ['type' => 'error', 'code' => 0, 'msg' => '请先绑定谷歌', 'url' => url('@agent/user/save_google')];
            exceptions($data);
        }
        //保存当前 google_token
        session('agent_info.google_token', $user['google_token']);
    }

}