<?php

namespace app\common\controller;

/**
 * 商户端基础控制器
 * Class AdminController
 * @package controller
 */

class UserController extends BaseController
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
     * 商户配置信息
     * @var array
     */
    protected $UserInfo = [];


    public function __construct()
    {
        parent::__construct();

        list( $this->is_login, $this->is_auth,) = [ true, true];

        //检测登录情况
        if ($this->is_login == true) {
            $this->__checkLogin();
        }

        $user =  \app\common\model\Umember::quickGet(session('user_info.id'));
        $this->UserInfo = cache('UserInfo');
        //判断是否异常用户
        $this->__checkLock($user);

        //是否单点登入
        if( isset($user['is_single']) && $user['is_single'] == '1'){
            $this->__single($user);
        }


        //绑定谷歌
        if( isset($this->UserInfo['UserGoole']) && $this->UserInfo['UserGoole'] == '1'){
            //$this->__google($user);
        }


        //判断是否有权限进行访问
        if ($this->is_auth == true) {
            $this->__checkAuth();
        }


        // 登录会员信息
        $this->user = session('user_info');
        $this->assign('user_info', session('user_info'));

    }

    /**
     * 检测登录
     */
    public function __checkLogin()
    {
        $user1 = session('user_info');
        //判断是否登录
        if (empty($user1)) {
            $data = ['type' => 'error', 'code' => 0, 'msg' => '抱歉，请重新登录！', 'url' => url('@user/login/index')];
            exceptions($data);
        }
    }


    public function __checkLock($user)
    {
        if(!isset($user['status']) || $user['status'] != 1){
            $data = ['status' => 'error', 'code' => 0, 'msg' => '账号已被冻结，强制退出！', 'url' => url('@user/login/logout')];
            __log( session('user_info.nickname').' 账号已被冻结，强制退出！');
            session('user_info', null);
            exceptions($data);
        }
    }


    /**
     * 检测登录情况
     */
    public function __checkAuth()
    {
        if (\app\common\service\AuthService::checkUserNode() == false){
            $data = ['type' => 'error', 'code' => 0, 'msg' => '抱歉，您暂无该权限，请联系管理员！', 'url' => url('@user')];
            exceptions($data);
        }
    }

    /**
     * 单点登入
     */
    public function __single($user)
    {
        if($user['single_key'] !== session('user_info.single_key')){
            $data = ['type' => 'error', 'code' => 0, 'msg' => '账号在其它设备登入，强制退出！', 'url' => url('@user/login/logout')];
            __log( session('user_info.nickname').' 账号在其它设备登入，强制退出！');
            session('user_info', null);
            exceptions($data);
        }
    }

    public function __google($user)
    {
        if(empty($user['google_token']) && $this->request->controller() !== 'Index' && $this->request->action() !== 'save_google' && $this->request->action() !== 'getmenu' ){
            $data = ['type' => 'error', 'code' => 0, 'msg' => '请先绑定谷歌', 'url' => url('@user/user/save_google')];
            exceptions($data);
        }
        //保存当前 google_token
        session('user_info.google_token', $user['google_token']);
    }

}