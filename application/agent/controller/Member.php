<?php
namespace app\agent\controller;
use app\common\controller\UserController;

class Member extends UserController {

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
     * 管理员列表
     */
    public function index() {
        if (!$this->request->isPost()) {
            //ajax访问
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['uid'] = $this->user['uid'];
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title' => '管理员列表',
                'data'  => '',
            ];

            return $this->fetch('', $basic_data);
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
                'auth'  => model('app\common\model\SysAuth')->getList(1,1),//权限组
            ];
            $this->assign($basic_data);
            return $this->fetch('staff');
        } else {
            $member = $this->request->only('username,password,password1,nickname,phone,qq,auth_id');

            !isset($member['auth_id']) && $member['auth_id'] = [];
            //数组转json
            $member['auth_id'] = json_encode($member['auth_id']);
            $member['who'] = 1;//1 商户下的员工
            $member['uid'] = $this->user['uid'];
            $member['pid'] = $this->model->where(['uid'=>$this->user['uid'],'who'=>0])->value('id');

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
     * 员工
     * @return mixed|string|\think\response\Json
     */
    public function edit_staff() {
        //查找所需修改用户
        $Member = $this->model->where(['id'=>$this->request->get('id','0'),'uid'=>$this->user['uid']])->find();
        if (empty($Member)) return exceptions('暂无数据，请重新刷新页面！');

        if (!$this->request->isPost()) {

            $auth = model('app\common\model\SysAuth')->getList(1,1)->toArray();

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

            return $this->fetch('staff');
        } else {

            $post = $this->request->only('username,nickname,phone,qq,auth_id','post');

            !isset($post['auth_id']) && $post['auth_id'] = [];
            $post['auth_id'] = json_encode($post['auth_id']); //数组转json
            $post['id'] = $this->request->get('id','0');

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.edit');
            if (true !== $validate) return __error($validate);

            return   $this->model->__edit($post);

        }
    }

    /**
     * 商户单点登入
     * @return \think\response\Json
     */
    public function single() {
        $get = $this->request->get();

        $Member = $this->model->where(['id'=> $get['id'],'uid'=>$this->user['uid']])->find();
        if (empty($Member)) return __error('暂无数据，请重新刷新页面！');


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
     * 更改商户状态
     * @return \think\response\Json
     */
    public function status() {
        $get = $this->request->get();

        $Member = $this->model->where(['id'=> $get['id'],'uid'=>$this->user['uid']])->find();
        if (empty($Member)) return __error('暂无数据，请重新刷新页面！');

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




}