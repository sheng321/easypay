<?php

namespace app\admin\controller;

use app\common\controller\AdminController;


class User extends AdminController {

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
        $this->model = model('app\common\model\SysAdmin');
    }

    /**
     * 管理员列表
     */
    public function index() {
        if (!$this->request->isPost()) {
            //ajax访问
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                return json($this->model->userList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title' => '管理员列表',
                'data'  => '',
            ];

            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_field');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            return $this->model->editField($post);
        }
    }




    /**
     * 添加管理员
     * @return mixed
     */
    public function add() {
        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '添加管理员',
                'auth'  => model('app\common\model\SysAuth')->getList(),
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->post();
            !isset($post['auth_id']) && $post['auth_id'] = [];
            //数组转json
            $post['auth_id'] = json_encode($post['auth_id']);

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\SysUser.add');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            $post['password'] = password($post['password']);
            $post['status'] = 1;
            return $this->model->__add($post);
        }
    }

    /**
     * 修改管理员信息
     * @return mixed|string|\think\response\Json
     */
    public function edit() {
        if (!$this->request->isPost()) {

            //查找所需修改用户
            $user = $this->model->where('id', $this->request->get('id'))->find();
            if (empty($user)) return msg_error('暂无数据，请重新刷新页面！');

            $auth = model('app\common\model\SysAuth')->getList()->toArray();

            $auth_id = json_decode($user['auth_id'], true);

            foreach ($auth as $k => $val) {
                $is_checked = false;
                foreach ($auth_id as $k_1) $val['id'] == $k_1 && $is_checked = true;
                $auth[$k]['is_checked'] = $is_checked;
            }

            //基础数据
            $basic_data = [
                'title' => '修改管理员信息',
                'user'  => $user->hidden(['password']),
                'auth'  => $auth,
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->post();

            !isset($post['auth_id']) && $post['auth_id'] = [];

            $post['id'] != 1 && $post['auth_id'] = json_encode($post['auth_id']); //数组转json

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\SysUser.edit');
            if (true !== $validate) return __error($validate);

            return   $this->model->__edit($post);

        }
    }

    /**
     * 表单模板
     * @return mixed
     */
    protected function form() {
        return $this->fetch('form');
    }

    /**
     * 管理员的删除
     * @return \think\response\Json
     */
    public function del() {
        $get = $this->request->get();

        //验证数据
        if (!is_array($get['id'])) {
            $validate = $this->validate($get, 'app\common\validate\SysUser.del');
            if (true !== $validate) return __error($validate);
        }

        //执行删除操作
        if (!is_array($get['id'])) {

            if($get['id'] == 1) return __error('主账号不可以删除！');

            $del = $this->model->where('id', $get['id'])->update(['status' => 3]);
        } else {
            $arr = array_merge(array_diff($get['id'], [1]));//去掉主账号

            $del = $this->model->whereIn('id', $arr)->update(['status' => 3]);
        }

        if ($del >= 1) {

            //清空菜单缓存
            clear_menu();

            return __success('删除成功！');
        } else {
            return __error('数据有误，请刷新重试！');
        }
    }

    /**
     * 修改用户密码
     * @return mixed|string|\think\response\Json
     */
    public function edit_password() {
        if (!$this->request->isPost()) {
            if (empty($this->request->get('id'))) return msg_error('暂无用户信息！');

            $user = $this->model->quickGet(['id'=> $this->request->get('id')]);
            if (empty($user)) return msg_error('暂无用户信息，请关闭页面刷新重试！');
            $basic_data = [
                'title' => '修改管理员密码',
                'user'  => $user,
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\SysUser.edit_password');
            if (true !== $validate) return __error($validate);

            //修改密码数据
            return $this->model->editPassword($post);
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
            $post = $this->request->post();

            $post['id'] = session('admin_info.id');
            //验证数据
            $validate = $this->validate($post, 'app\common\validate\SysUser.save_google');
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
                'title' => '修改管理员密码',
                'user'  => $data,
            ];

            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->post();


            //验证数据
            $validate = $this->validate($post, 'app\common\validate\SysUser.edit_password1');
            if (true !== $validate) return __error($validate);

            //修改密码数据
            return $this->model->editPassword($post);
        }
    }


    /**
     * 重置自己的谷歌
     */
    public function change_google() {

        $data['id'] =  $this->user['id'];
        $data['google_token'] =  '';

        //使用事物保存数据
        $this->model->startTrans();
        $save = $this->model->save($data,['id'=>$data['id']]);
        if (!$save) {
            $this->model->rollback();
            return msg_error('数据有误，请稍后再试！');
        }
        $this->model->commit();
        return msg_success('重置成功~');

    }





    /**
     * 更改管理员状态
     * @return \think\response\Json
     */
    public function status() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\SysUser.status');
        if (true !== $validate) return __error($validate);

        //判断管理员状态
        $status = $this->model->where('id', $get['id'])->value('status');
        $status == 1 ? list($msg, $status) = ['禁用成功', $status = 0] : list($msg, $status) = ['启用成功', $status = 1];


        $data['id'] = $get['id'];
        $data['status'] = $status;
        return   $this->model->__edit($data,$msg);
    }



    /**
     * 重置谷歌
     * @return \think\response\Json
     */
    public function google() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\SysUser.google');
        if (true !== $validate) return __error($validate);

        //判断管理员状态
        $google_token = $this->model->where('id', $get['id'])->value('google_token');

        if(empty($google_token)){
            return __error('错误操作！');
        }else{
            $data['id'] = $get['id'];
            $data['google_token'] = '';
            return   $this->model->__edit($data,'重置成功');
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
                'title' => '修改管理员信息',
                'user'  => $data,
            ];
            $this->assign($basic_data);
            return $this->fetch('form_self');
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\SysUser.editSelf');
            if (true !== $validate) return __error($validate);


            //保存数据,返回结果
            return $this->model->editSelf($post);
        }
    }


    /**
     *  银行卡列表
     */
    public function bank(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 10);
            $search = (array)$this->request->get('search', []);
            return json(model('app\common\model\Bank')->aList($page, $limit, $search));
        }

        $basic_data = [
            'title' => '银行卡列表',
        ];
        return $this->fetch('', $basic_data);
    }


}