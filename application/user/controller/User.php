<?php

namespace app\user\controller;
use app\common\controller\UserController;
use app\common\model\Umoney;
use app\common\model\Uprofile;
use think\facade\Session;

class User extends UserController {

    /**
     * User模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * User constructor.
     */
    public function __construct() {
        parent::__construct();

        $this->model = model('app\common\model\Umember');
    }


    /**
     * 代付设置
     * @return mixed|string|\think\response\Json
     */
    public function df_set() {
        $Umoney =  model('app\common\model\Umoney');
        $user =$Umoney->quickGet(['uid'=>$this->user['uid']]);
        if(empty($user)) return exceptions('数据错误，请重试！');

        if (!$this->request->isPost()){

            //基础数据
            $basic_data = [
                'title'  => '代付设置',
                'status' => [13=>'余额转代付金额',14=>'代付金额转余额'],
                'user'  => $user,//用户金额
                'api'  => $this->user['profile']['df_api'],
            ];
            return $this->fetch('', $basic_data);
        } else {
            $money = $this->request->only('change,type,api','post');


            //谷歌验证码
            if ($this->UserInfo['UserGoole'] == 1) {
                $data1['google_token'] = $this->user['google_token'];
                $data1['google'] = $this->request->post('google/d', 0);
                $validate1 = $this->validate($data1, 'app\common\validate\common.google');
                if (true !== $validate1) return __error($validate1);
            }

            //支付密码
            $data2['paypwd1'] = $this->user['profile']['pay_pwd'];
            $data2['paypwd'] = $this->request->post('paypwd/s', '');

            //验证数据
            $validate2 = $this->validate($data2, 'app\common\validate\Umember.paypwd');
            if (true !== $validate2) return __error($validate2);

            //token
            $__token__ = $this->request->param('__token__/s', '');
            $__hash__ = Session::pull('__token__');
            if ($__token__ !== $__hash__) return __error("令牌验证无效，请刷新重试");

            if(!in_array($money['type'],[13,14]) || !in_array($money['api'],[0,1])) return __error('数据异常');
            if($money['api'] === $this->user['profile']['df_api']) unset($money['api']);

            $money['change'] =   floatval($money['change']);

            if($money['change'] > 0){
                //处理金额
                $res =  $Umoney->dispose($user,$money);
                if (true !== $res['msg']) return __error($res['msg']);
            }

            //使用事物保存数据
            $Umoney->startTrans();
            if($money['change'] > 0) {
                $save = $Umoney->saveAll($res['data']);
                $add = model('app\common\model\UmoneyLog')->saveAll($res['change']);
            }else{
                $save = true;
                $add = true;
            }
            $save2 = true;
            if(in_array($money['api'],[0,1]))  $save2 = model('app\common\model\Uprofile')->save(['df_api'=>$money['api'],'id'=>$this->user['profile']['id']],['id'=>$this->user['profile']['id']]);

            if (!$save || !$add || !$save2) {
                $Umoney->rollback();
                $msg = '数据有误，请稍后再试！';
                if($money['change'] > 0)   __log($res['log'].'失败',2);
                return __error($msg);
            }
            $Umoney->commit();
            if($money['change'] > 0) __log($res['log'].'成功',2);
            empty($msg) && $msg = '操作成功';
            return __success($msg);
        }
    }




    /**
     * 绑定谷歌
     * @return mixed|string|\think\response\Json
     */
    public function save_google() {
        if (!$this->request->isPost()) {

            if(!empty($this->user['google_token'])){
                session('admin.google_token',$this->user['google_token']);
                $data['token'] = $this->user['google_token'];
            }else{
                $data['token'] = (new \tool\Goole())->createSecret(17);
            }

            $data['google_token'] = $this->user['google_token'];

            $basic_data = [
                'title' => '绑定谷歌',
                'user'  => $data,
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only(['google_token','__token__'], 'post');
            $post['id'] = $this->user['id'];
            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.save_google');
            if (true !== $validate) return __error($validate);
            $msg = '绑定成功';

            //修改密码数据
            return $this->model->__edit($post,$msg);
        }
    }


    /**
     * 修改用户自己的密码
     * @return mixed|string|\think\response\Json
     */
    public function changepwd() {
        if (!$this->request->isPost()) {

            $data['username'] = $this->user['username'];
            $basic_data = [
                'title' => '修改商户密码',
                'user'  => $data,
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only(['password','password1','old_password','__token__'], 'post');
            $post['id'] =  $this->user['id'];


            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.editPassword1');
            if (true !== $validate) return __error($validate);

            //修改密码数据
            return $this->model->editPassword($post);
        }
    }


    public function changepaypwd() {

        if (!$this->request->isPost()) {

            $data['username'] = $this->user['username'];
            $basic_data = [
                'title' => '修改支付密码',
                'user'  => $data,
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only(['password','password1','old_password','__token__'], 'post');
            $post['paypwd1'] =  $this->user['profile']['pay_pwd'];

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.paypwd1');
            if (true !== $validate) return __error($validate);
            $data['pay_pwd'] = password($post['password']);
            $data['id'] = $this->user['profile']['id'];
            return model('app\common\model\Uprofile')->__edit($data);

        }
    }

    /**
     * 修改自己的信息
     * @return mixed|string|\think\response\Json
     */
    public function edit_self() {
        if (!$this->request->isPost()) {
            $data['username'] = $this->user['username'];
            $data['nickname'] = $this->user['nickname'];
            $data['phone'] = $this->user['phone'];
            $data['qq'] = $this->user['qq'];
            $data['remark'] = $this->user['remark'];

            //基础数据
            $basic_data = [
                'title' => '修改商户信息',
                'user'  => $data,
            ];


            $this->assign($basic_data);

            return $this->fetch('form_self');
        } else {
            $post = $this->request->only(['username','nickname','phone','qq','mail','remark','__token__'], 'post');
            $post['id'] =  $this->user['id'];

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.editSelf');
            if (true !== $validate) return __error($validate);
            //保存数据,返回结果
            return $this->model->editSelf($post);
        }
    }

    /**
     *  ip管理
     */
    public function ip(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 10);
            $search = (array)$this->request->get('search', []);
            $search['uid'] = $this->user['uid'];
            return json(model('app\common\model\Ip')->aList($page, $limit, $search));
        }

        $basic_data = [
            'title' => 'IP白名单列表',
            'type' => [0=>'登入',1=>'结算',2=>'代付'],
        ];
        return $this->fetch('', $basic_data);
    }


    /**
     *  添加ip
     */
    public function save_ip(){

        $Bank =  model('app\common\model\Ip');
        $uid =  $this->user['uid'];

        if (!$this->request->isPost()){

            $basic_data = [
                'title' => '添加IP',
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only(['ip','type','__token__'], 'post');
            $post['uid'] = $uid;
            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Ip.edit');
            if (true !== $validate) return __error($validate);
            unset($post['__token__']);
            return $Bank->__add($post);
        }
    }

    /**
     *  删除ip
     */
    public function del_ip(){
        $get = $this->request->only('id');

        //验证数据
        if (!is_array($get['id'])) {
            $get['uid'] = $this->user['uid'];
            $validate = $this->validate($get, 'app\common\validate\Ip.del');
            if (true !== $validate) return __error($validate);
        }else{
            foreach ($get['id'] as $k => $val){
                $data['id'] = $val;
                $data['uid'] = $this->user['uid'];
                $validate = $this->validate($data, 'app\common\validate\Ip.del');
                if (true !== $validate) unset($get['id'][$k]);
            }
        }
        if(empty($get)) return __error('数据异常');

        //执行操作
        $del = model('app\common\model\Ip')->__del($get);
        return $del;

    }


    /**
     *  银行卡
     */
    public function bank(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 10);
            $search = (array)$this->request->get('search', []);
            $search['uid'] = $this->user['uid'];
            return json(model('app\common\model\Bank')->aList($page, $limit, $search));
        }

        $basic_data = [
            'title' => '银行卡列表',
        ];
        return $this->fetch('', $basic_data);
    }
    /**
     *  添加/编辑银行卡
     */
    public function saveBank(){

        $Bank =  model('app\common\model\Bank');
        $uid =  $this->user['uid'];

        if (!$this->request->isPost()){
           $bank_id =  $this->request->get('id/d',0);
           if(!empty($bank_id)){
               $find = $Bank->where(['uid'=>$uid,"id"=>$bank_id])->find();
               if(empty($find)) return msg_error('该银行卡不存在');
           }else{
               $find = [];
           }
            $basic_data = [
                'title' => '添加/编辑银行卡',
                'info' => $find,
                'bank' => config('bank.'),
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only(['id','card_number','bank_id','branch_name','province','city','account_name','__token__'], 'post');
            $post['uid'] = $uid;
            $post['bank_name'] = config('bank.'.$post['bank_id']);
            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Bank.edit');
            if (true !== $validate) return __error($validate);
            unset($post['__token__']);
            if(empty($post['id'])){
                __log( $this->user['user'].'编辑银行卡:'.$post['card_number'],2);
                return $Bank->__add($post);
            }else{
                __log( $this->user['user'].'添加银行卡:'.$post['card_number'],2);
                return $Bank->__edit($post);
            }
        }
    }
    /**
     *  删除银行卡
     */
    public function delBank(){
        $get = $this->request->only('id');

        //验证数据
        if (!is_array($get['id'])) {
            $get['uid'] = $this->user['uid'];
            $validate = $this->validate($get, 'app\common\validate\Bank.del');
            if (true !== $validate) return __error($validate);
        }else{
            foreach ($get['id'] as $k => $val){
                $data['id'] = $val;
                $data['uid'] = $this->user['uid'];
                $validate = $this->validate($data, 'app\common\validate\Bank.del');
                if (true !== $validate) unset($get['id'][$k]);
            }
        }
        if(empty($get)) return __error('数据异常');

        //执行操作
        $del = model('app\common\model\Bank')->__del($get);
        return $del;

    }

    /**行为日志
     * @return mixed|\think\response\Json
     */
    public function log(){

        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 10);
            $search = (array)$this->request->get('search', []);
            $search['type'] = 2;
            $search['uid'] = $this->user['uid'];
            return json(model('app\common\model\ActonRecord')->userList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '行为日志',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }



}