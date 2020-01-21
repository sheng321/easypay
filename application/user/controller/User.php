<?php

namespace app\user\controller;
use app\common\controller\UserController;
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
            $bank_id =  $this->request->get('id/d',0);
            if(!empty($bank_id)){
                $find = $Bank->where(['uid'=>$uid,"id"=>$bank_id])->find();
                if(empty($find)) return msg_error('该银行卡不存在');
            }else{
                $find = [];
            }
            $basic_data = [
                'title' => '添加IP',
                'info' => $find,
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
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only(['id','card_number','bank_name','branch_name','province','city','areas','account_name','__token__'], 'post');
            $post['uid'] = $uid;
            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Bank.edit');
            if (true !== $validate) return __error($validate);
            unset($post['__token__']);
            if(empty($post['id'])){
                return $Bank->__add($post);
            }else{
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
}