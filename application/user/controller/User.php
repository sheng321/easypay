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

}