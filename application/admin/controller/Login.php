<?php
namespace app\admin\controller;
use app\common\controller\BaseController;
use think\facade\Cache;


class Login extends BaseController
{
    /**
     * 初始化
     * Login constructor.
     */
    public function __construct() {
        parent::__construct();
        $action = $this->request->action();
        if (!empty(session('admin_info.id')) && $action !== 'logout' ) return $this->redirect('@admin');
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


            //判断是否开启验证码登录选择验证规则
            $SysInfo = Cache::get('SysInfo');

            $SysInfo['VercodeType'] != 1 ? $validate_type = 'app\common\validate\Login.index_off' : $validate_type = 'app\common\validate\Login.index_on';
            //验证参数
            $validate = $this->validate($post, $validate_type);


            if (true !== $validate){
                return __error($validate);
            }

            $this->model = model('app\common\model\SysAdmin');

            //判断登录是否成功
            $login = $this->model->login($post['username'], $post['password']);
            if ($login['code'] == 0) {
                isset($login['user']['id']) ? $user_id = $login['user']['id'] : $user_id = '';
                return __error($login['msg']);
            }

            //谷歌验证码
            if($SysInfo['GoolecodeType'] == 1){

                $data1['google_token'] = $login['user']['google_token'];
                $data1['google'] = $post['goolecode'];

                $validate1 = $this->validate($data1, 'app\common\validate\common.google');
                if (true !== $validate1) return __error($validate1);
            }


            //储存session数据
            $login['user']['login_at'] = time();
            session('admin_info', $login['user']);

            $session_id  = session_id();
            //单点登入
            $this->model->save([
                'single_key'=>$session_id,
                'id'=>$login['user']['id']
            ],['id'=>$login['user']['id']]);
            session('admin_info.single_key', $session_id);

            __log('登入成功！正在跳转到系统');

            return __success($login['msg']);
        }

    }


    /**
     * 退出登录
     * @return \think\response\Json
     */
    public function logout() {

        __log('退出登录成功！');

        //删除自身菜单缓存
        Cache::rm(session('admin_info.id') . '_AdminMenu');

        //清空sesion数据
        session('admin_info', null);


        return msg_success('退出登录成功', url('@admin/login'));
    }


}
