<?php

namespace app\common\controller;
use think\facade\Session;

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
        $this->user = $user;

        $this->__cc($this->user['uid']);

        //ip 白名单验证
        $ip =  \app\common\model\Ip::bList($this->user['uid'],0);
/*        if(!in_array(get_client_ip(),$ip)){
            __log( session('agent_info.username').'登入IP白名单不包含此IP:'.get_client_ip(),2);
            session('agent_info', null);
            $data = ['type' => 'error', 'code' => 0, 'msg' =>'登入IP白名单不包含此IP:'.get_client_ip(), 'url' => url('@agent/login/index')];
            exceptions($data);
        }*/

    }

    /**
     * 检测登录
     */
    protected function __checkLogin()
    {
        $user1 = session('agent_info');
        //判断是否登录
        if (empty($user1)) {
            $data = ['type' => 'error', 'code' => 0, 'msg' => '抱歉，请重新登录！', 'url' => url('@agent/login/index')];
            exceptions($data);
        }
    }


    protected function __checkLock($user)
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
    protected function __checkAuth()
    {
        if (\app\common\service\AuthService::checkAgentNode() == false){
            $data = ['type' => 'error', 'code' => 0, 'msg' => '抱歉，您暂无该权限，请联系管理员！', 'url' => url('@agent')];
            exceptions($data);
        }
    }

    /**
     * 单点登入
     */
    protected function __single($user)
    {
        $session_id  = getSessionid();
        if($user['single_key'] !== session('agent_info.single_key')  ||  $user['single_key'] !== $session_id){
            $data = ['type' => 'error', 'code' => 0, 'msg' => '账号在其它设备登入，强制退出！', 'url' => url('@agent/login/logout')];
            __log( session('agent_info.nickname').' 账号在其它设备登入，强制退出！');
            session('agent_info', null);
            exceptions($data);
        }
    }

    protected function __google($user)
    {
        if(empty($user['google_token']) && $this->request->controller() !== 'Index' && $this->request->action() !== 'save_google' && $this->request->action() !== 'getmenu' ){
            $data = ['type' => 'error', 'code' => 0, 'msg' => '请先绑定谷歌', 'url' => url('@agent/user/save_google')];
            exceptions($data);
        }
        //保存当前 google_token
        session('agent_info.google_token', $user['google_token']);
    }

    //防止CC攻击 防止快速刷新
    protected function __cc($uid)
    {
        $seconds = '45'; //时间段[秒]
        $refresh = '1'; //刷新次数
        //设置监控变量
        $cur_time = time();
        if(Session::has('last_time'.$uid)){
            Session::set('refresh_times'.$uid, Session::get('refresh_times') + 1);
        }else{
            Session::set('refresh_times'.$uid,1);
            Session::set('last_time'.$uid,$cur_time);
        }
        //处理监控结果
        if($cur_time - Session::get('last_time'.$uid) < $seconds){
            if(Session::get('refresh_times'.$uid) >= $refresh){
                exceptions(['msg'=>'请求频率太快，稍候30秒后再访问！','wait'=>3]);
            }
        }else{
            Session::set('refresh_times'.$uid,0);
            Session::set('last_time'.$uid,$cur_time);
        }
    }

}