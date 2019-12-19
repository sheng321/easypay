<?php

namespace app\admin\controller;

use app\common\controller\AdminController;


class Member extends AdminController {

    /**
     * Member模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * Member constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->model = model('app\common\model\Umember');
    }

    /**
     * 商户列表
     */
    public function index() {
        if (!$this->request->isPost()) {
            //ajax访问
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title' => '商户列表',
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
     * 代理列表
     */
    public function agent() {
        if (!$this->request->isPost()) {
            //ajax访问
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                return json($this->model->bList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title' => '商户列表',
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
     * 添加商户或者代理
     * @return mixed
     */
    public function add(){

        if (!$this->request->isPost()) {
            $agent = $this->model->where([
                ['who','=','2'],
                ['status','=','1']
            ])->field('uid,id')->select()->toArray();

            $group =   \app\common\model\Ulevel::field('id,title')->select()->toArray();

            //基础数据
            $basic_data = [
                'title' => '添加商户或者代理',
                'auth'  => model('app\common\model\SysAuth')->getList(1),//权限组
                'group'  => $group,//用户分组
                'agent'  => $agent,//所有的代理
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {

            $member = $this->request->only('username,password,password1,nickname,phone,qq,who,remark,auth_id');
            $profile = $this->request->only('a_id,group_id');

            !isset($member['auth_id']) && $member['auth_id'] = [];
            //数组转json
            $member['auth_id'] = json_encode($member['auth_id']);

            //验证数据
            $validate = $this->validate($member, 'app\common\validate\Umember.add');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            $member['password'] = password($member['password']);
            $member['status'] = 1;

            //保存数据,返回结果
            //使用事物保存数据
            $this->model->startTrans();
            $result = $this->model->save($member);

            if (!$result || !$this->model->id ) {
                $this->model->rollback();
                empty($msg) && $msg = '数据有误，请稍后再试!';
                return __error($msg);
            }
            $this->model->commit();
            $find = $this->model->field('uid')->get($this->model->id);

            if(!empty($profile)){
                $Uprofile =  model('\app\common\model\Uprofile');
                if($member['who'] == 2){
                    $agent_level = 0;
                   if(!empty($profile['a_id'])) $agent_level  = $Uprofile->where('uid',$profile['a_id'])->value('agent_level');
                    $profile['agent_level'] = $agent_level + 1;
                }

                $profile['id'] = $find['profile']['id'];
                $profile['uid'] = $find['profile']['uid'];
                $Uprofile->__edit($profile);
            }

            empty($msg) && $msg = '添加成功!';
            return __success($msg);

        }

    }



    /**
     * 添加员工
     * @return mixed
     */
    public function add_staff(){

        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '添加员工',
                'auth'  => model('app\common\model\SysAuth')->getList(1),//权限组
            ];
            $this->assign($basic_data);
            return $this->staff();
        } else {
            $member = $this->request->only('username,password,password1,nickname,phone,qq,who,remark,auth_id,pid,who');

            !isset($member['auth_id']) && $member['auth_id'] = [];
            //数组转json
            $member['auth_id'] = json_encode($member['auth_id']);

            //验证数据
            $validate = $this->validate($member, 'app\common\validate\Umember.add_staff');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            $member['password'] = password($member['password']);
            $member['status'] = 1;
            $result = $this->model->__add($member);
            return $result;

        }
    }


    /**
     * 修改商户信息
     * @return mixed|string|\think\response\Json
     */
    public function edit() {
        if (!$this->request->isPost()) {

            $agent = $this->model->where([
                ['who','=','2'],
                ['status','=','1']
            ])->field('uid,id')->select()->toArray();

            $group =   \app\common\model\Ulevel::field('id,title')->select()->toArray();
            //查找所需修改用户
            $Member = $this->model->where('id', $this->request->get('id'))->find();
            if (empty($Member)) return msg_error('暂无数据，请重新刷新页面！');

            $auth = model('app\common\model\SysAuth')->getList(1)->toArray();

            $auth_id = json_decode($Member['auth_id'], true);

            foreach ($auth as $k => $val) {
                $is_checked = false;
                foreach ($auth_id as $k_1) $val['id'] == $k_1 && $is_checked = true;
                $auth[$k]['is_checked'] = $is_checked;
            }

            foreach ($group as $k => $val) {
                $is_checked = false;
                if($Member['profile']['group_id'] == $val['id']) $is_checked = true;
                $group[$k]['group_checked'] = $is_checked;
            }

            foreach ($agent as $k => $val) {
                $is_checked = false;
                if($Member['profile']['a_id'] == $val['uid']) $is_checked = true;
                $agent[$k]['agent_checked'] = $is_checked;
            }

            //基础数据
            $basic_data = [
                'title' => '修改商户信息',
                'user'  => $Member->hidden(['password','who','pid','is_single','single_key','google_token']),
                'auth'  => $auth,
                'group'  => $group,//用户分组
                'agent'  => $agent,//所有的代理
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {



            $post = $this->request->only('username,nickname,phone,qq,who,remark,auth_id,id');
            $profile = $this->request->only('a_id,group_id');
            $pid = $this->request->post('pid','0');


            !isset($post['auth_id']) && $post['auth_id'] = [];
            $post['auth_id'] = json_encode($post['auth_id']); //数组转json

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.edit');
            if (true !== $validate) return __error($validate);

            $res = $this->model->__edit($post);

            if(!empty($profile)){
                $profile['id'] = $pid;
                model('\app\common\model\Uprofile')->__edit($profile);
            }
            return  $res;
        }
    }


    /**
     * 员工
     * @return mixed|string|\think\response\Json
     */
    public function edit_staff() {
        if (!$this->request->isPost()) {

            //查找所需修改用户
            $Member = $this->model->where('id', $this->request->get('id','0'))->find();
            if (empty($Member)) return msg_error('暂无数据，请重新刷新页面！');

            $auth = model('app\common\model\SysAuth')->getList(1)->toArray();

            $auth_id = json_decode($Member['auth_id'], true);

            foreach ($auth as $k => $val) {
                $is_checked = false;
                foreach ($auth_id as $k_1) $val['id'] == $k_1 && $is_checked = true;
                $auth[$k]['is_checked'] = $is_checked;
            }

            //基础数据
            $basic_data = [
                'title' => '修改商户员工信息',
                'user'  => $Member->hidden(['password','who','pid','is_single','single_key','google_token']),
                'auth'  => $auth,
            ];
            $this->assign($basic_data);

            return $this->staff();
        } else {
            $post = $this->request->post();

            !isset($post['auth_id']) && $post['auth_id'] = [];
            $post['auth_id'] = json_encode($post['auth_id']); //数组转json

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.edit');
            if (true !== $validate) return __error($validate);

            return   $this->model->__edit($post);

        }
    }


    /**
     * 表单模板
     * @return mixed
     */
    protected function form(){
        return $this->fetch('form');
    }
    protected function staff(){
        return $this->fetch('staff');
    }

    /**
     * 商户的删除
     * @return \think\response\Json
     */
    public function del() {
        $get = $this->request->get();

        //验证数据
        if (!is_array($get['id'])) {
            $validate = $this->validate($get, 'app\common\validate\Umember.del');
            if (true !== $validate) return __error($validate);
        }

        //执行删除操作
        if (!is_array($get['id'])) {

            $del = $this->model->where('id', $get['id'])->update(['status' => 3]);
        } else {

            $del = $this->model->whereIn('id', $get['id'])->update(['status' => 3]);
        }

        if ($del >= 1) {

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

            $Member = $this->model->quickGet(['id'=> $this->request->get('id')]);
            if (empty($Member)) return msg_error('暂无用户信息，请关闭页面刷新重试！');
            $basic_data = [
                'title' => '修改商户密码',
                'user'  => $Member,
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.edit_password');
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

            $Member = $this->model->quickGet(['id'=> $this->Member['id']]);
            if (empty($Member)) return msg_error('暂无用户信息，请关闭页面刷新重试！');

            if(!empty($Member['google_token'])){
                session('admin.google_token',$Member['google_token']);
                $Member['token'] = $Member['google_token'];
            }else{
                $Member['token'] = (new \tool\Goole())->createSecret(17);
            }

            $basic_data = [
                'title' => '绑定谷歌',
                'Member'  => $Member,
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->post();

            $post['id'] = session('admin_info.id');
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

            $Member = $this->model->quickGet(['id'=> $this->Member['id']]);
            if (empty($Member)) return msg_error('暂无用户信息，请关闭页面刷新重试！');
            $basic_data = [
                'title' => '修改商户密码',
                'Member'  => $Member,
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->post();


            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.edit_password1');
            if (true !== $validate) return __error($validate);

            //修改密码数据
            return $this->model->editPassword($post);
        }
    }


    /**
     * 重置自己的谷歌
     */
    public function change_google() {

        $Member = $this->model->quickGet(['id'=> $this->Member['id']]);
        if (empty($Member)) return msg_error('暂无用户信息，请关闭页面刷新重试！');

        $data['id'] =  $this->Member['id'];
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
     * 更改商户状态
     * @return \think\response\Json
     */
    public function status() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Umember.status');
        if (true !== $validate) return __error($validate);

        //判断商户状态
        $status = $this->model->where('id', $get['id'])->value('status');
        $status == 1 ? list($msg, $status) = ['禁用成功', $status = 0] : list($msg, $status) = ['启用成功', $status = 1];


        $data['id'] = $get['id'];
        $data['status'] = $status;
        return   $this->model->__edit($data,$msg);
    }

    /**
     * 商户单点登入
     * @return \think\response\Json
     */
    public function single() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Umember.single');
        if (true !== $validate) return __error($validate);

        //判断商户状态
        $status = $this->model->where('id', $get['id'])->value('is_single');
        $status == 1 ? list($msg, $status) = ['禁用成功', $status = 0] : list($msg, $status) = ['启用成功', $status = 1];


        $data['id'] = $get['id'];
        $data['is_single'] = $status;
        return   $this->model->__edit($data,$msg);
    }





    /**
     * 重置谷歌
     * @return \think\response\Json
     */
    public function google() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Umember.google');
        if (true !== $validate) return __error($validate);

        //判断商户状态
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

            //查找所需修改用户
            $Member =  $this->model->quickGet(['id'=>$this->Member['id']]);
            if (empty($Member)) return msg_error('暂无数据，请重新刷新页面！');

            //基础数据
            $basic_data = [
                'title' => '修改商户信息',
                'Member'  => $Member->hidden(['password']),
            ];
            $this->assign($basic_data);

            return $this->fetch('form_self');
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.editSelf');
            if (true !== $validate) return __error($validate);


            //保存数据,返回结果
            return $this->model->editSelf($post);
        }
    }

}