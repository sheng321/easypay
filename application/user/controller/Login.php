<?php
namespace app\user\controller;
use app\common\controller\BaseController;
use think\facade\Cache;
use app\common\service\GeetestLib;

class Login extends BaseController
{

    /**
     * 商户配置信息
     * @var array
     */
    protected $UserInfo = [];
    /**
     * 初始化
     * Login constructor.
     */
    public function __construct() {
        parent::__construct();
        $action = $this->request->action();
        if (!empty(session('user_info.id')) && $action !== 'logout' && $action !== 'startGeetest' ) return $this->redirect('@user');
        
        $this->UserInfo = cache('UserInfo');

    }

    /**
     * 初始化极验
     */
    public function startGeetest() {
        $data = [
            "user_id" => session_id(), # 网站用户id
            "client_type" => isMobile()?'h5':"web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => $this->request->ip() # 请在此处传输用户请求验证时所携带的IP
        ];
        $GtSdk = new GeetestLib(config('geetest.captcha_id'), config('geetest.private_key'));
        $status = $GtSdk->pre_process($data, 1);
        session('gtserver', $status);
        session('user_id', session_id());
        return $GtSdk->get_response_str();
    }

    /**
     * 极验检查
     */
    public function checkGeetest($post) {
        $data = [
            "user_id" => session_id(), # 网站用户id
            "client_type" => isMobile()?'h5':"web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => $this->request->ip() # 请在此处传输用户请求验证时所携带的IP
        ];
        $GtSdk = new GeetestLib(config('geetest.captcha_id'), config('geetest.private_key'));
        $param = $post;
        if (session('gtserver') == 1) {
            $result = $GtSdk->success_validate(
                $param['geetest_challenge'],
                $param['geetest_validate'],
                $param['geetest_seccode'], $data);
            if ($result == 1) return true;
        } else { //宕机
             $result = $GtSdk->fail_validate(
                 $param['geetest_challenge'],
                 $param['geetest_validate'],
                 $param['geetest_seccode']);
            if ($result == 1) return true;
            logs('商户登入：'.json_encode($param),'error');
        }
        return false;
    }


    public function index()
    {
        if ($this->request->isGet()) {

            //基础数据
            $basic_data = [
                'title' => '聚合 · 后台管理',
                'data'  => '',
            ];
            $this->assign($basic_data);

            return $this->fetch('');
        } else {
            $post = $this->request->post();

         if(!$this->checkGeetest($post)){
                return __error('验证不通过~');
            }
            
            //验证参数
            $validate = $this->validate($post, 'app\common\validate\Login.user_login');
            if (true !== $validate){
                return __error($validate);
            }

            $this->model = model('app\common\model\Umember');

            //判断登录是否成功
            $login = $this->model->login($post['username'], $post['password']);
            if ($login['code'] == 0) {
                isset($login['user']['id']) ? $user_id = $login['user']['id'] : $user_id = '';
                return __error($login['msg']);
            }

            //谷歌验证码
            if($this->UserInfo['UserGoole'] == 1){

                $data1['google_token'] = $login['user']['google_token'];
                $data1['google'] = $post['googlecode'];
                $validate1 = $this->validate($data1, 'app\common\validate\common.google');
                if (true !== $validate1) return __error($validate1);
            }


            //储存session数据
            $login['user']['login_at'] = time();
            session('user_info', $login['user']);

            $session_id  = session_id();

            //单点登入
            if(!empty($session_id)){
                $this->model->save([
                    'single_key'=>$session_id,
                    'id'=>$login['user']['id']
                ],['id'=>$login['user']['id']]);
                session('user_info.single_key', $session_id);
            }
            __log($login['msg'],2);

            session('gtserver', null);
            session('user_id', null);

            return __success($login['msg']);
        }
    }


    /**
     * 退出登录
     * @return \think\response\Json
     */
    public function logout() {

        __log('退出登录成功！',2);

        //删除自身菜单缓存
        Cache::rm(session('user_info.id') . '_UserMenu');

        //清空sesion数据
        session('user_info', null);

        return msg_success('退出登录成功', url('@user/login'));

    }


}
