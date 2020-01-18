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
    public function saveBark(){
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


        $info = array();
        if($this->request->isPost()){
            $data = $this->request->param();
            unset($data['bank_id']);unset($data['__token__']);
            if(!empty($this->request->param('bank_id'))){//编辑
                $result = Db::name("bank_card")->where("id",$this->request->param('bank_id'))->update($data);
            }else{//新增
                $data['mch_id'] = $this->user['uid'];
                $result = Db::name("bank_card")->insert($data);
            }
            if($result){
                return __success('操作成功');
            }
            return __error('操作失败');
        }else{//查看
            $info = Db::name("bank_card")->where("id",$this->request->param('id'))->find();
        }
        $this->assign("info",$info);
        return view("withdrawal/save_bark");
    }
    /**
     *  删除银行卡
     */
    public function delBank(){
        $id = $this->request->param('id');
        if(!is_array($id)){
            $info = Db::name("bank_card")->where("id",$id)->find();
            if(empty($info)) return __error('数据不存在');
        }
        $result = Db::name("bank_card")->where("id",'in',$id)->delete();
        if($result){
            return __success('删除成功');
        }
        return __error('删除失败');
    }








}