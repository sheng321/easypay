<?php

namespace app\common\controller;


/**
 * 后台基础控制器
 * Class AdminController
 * @package controller
 */
class AdminController extends BaseController
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
     * 后台配置信息
     * @var array
     */
    protected $SysInfo = [];


    public function __construct()
    {
        parent::__construct();

        list( $this->is_login, $this->is_auth,) = [ true, true];

        //检测登录情况
        if ($this->is_login == true) {
            $this->__checkLogin();
        }

        $user =  \app\common\model\SysAdmin::quickGet(session('admin_info.id'));
        $this->SysInfo = cache('SysInfo');

        $this->__checkLock($user);

        //是否单点登入
        if( isset($this->SysInfo['single']) && $this->SysInfo['single'] == '1'){
            $this->__single($user);
        }


        //绑定谷歌
        if( isset($this->SysInfo['GoolecodeType']) && $this->SysInfo['GoolecodeType'] == '1'){
            $this->__google($user);
        }


        //判断是否有权限进行访问
        if ($this->is_auth == true) {
            $this->__checkAuth();
        }


        // 登录会员信息
        $this->user = session('admin_info');
        $this->assign('admin_info', session('admin_info'));



    }



    /**
     * 检测登录
     */
    public function __checkLogin()
    {
        $user1 = session('admin_info');
        //判断是否登录
        if (empty($user1)) {
            $data = ['type' => 'error', 'code' => 0, 'msg' => '抱歉，请重新登录！', 'url' => url('@admin/login/index')];
            exceptions($data);
        }
    }


    public function __checkLock($user)
    {
        if(!isset($user['status']) || $user['status'] != 1){
            $data = ['status' => 'error', 'code' => 0, 'msg' => '账号已被冻结，强制退出！', 'url' => url('@admin/login/logout')];
            __log( session('admin_info.nickname').' 账号已被冻结，强制退出！');
            session('admin_info', null);
            exceptions($data);
        }
    }




    /**
     * 检测登录情况
     */
    public function __checkAuth()
    {
        if (\app\common\service\AuthService::checkNode() == false){
            $data = ['type' => 'error', 'code' => 0, 'msg' => '抱歉，您暂无该权限，请联系管理员！', 'url' => url('@admin')];
            exceptions($data);
        }
    }

    /**
     * 单点登入
     */
    public function __single($user)
    {
        if($user['single_key'] !== session('admin_info.single_key')){
            $data = ['type' => 'error', 'code' => 0, 'msg' => '账号在其它设备登入，强制退出！', 'url' => url('@admin/login/logout')];
            __log( session('admin_info.nickname').' 账号在其它设备登入，强制退出！');
            session('admin_info', null);
            exceptions($data);
        }
    }

    public function __google($user)
    {
        if(empty($user['google_token']) && $this->request->controller() !== 'Index' && $this->request->action() !== 'save_google'){
            $data = ['type' => 'error', 'code' => 0, 'msg' => '请绑定谷歌', 'url' => url('@admin/user/save_google')];
            exceptions($data);
        }
        session('admin_info.google_token', $user['google_token']);
    }

}