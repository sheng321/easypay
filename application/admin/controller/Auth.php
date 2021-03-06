<?php


namespace app\admin\controller;

use app\common\controller\AdminController;


class Auth extends AdminController {

    /**
     * Auth模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * node constructor.
     */
    public function __construct() {
        parent::__construct();
        
        $this->model = model('app\common\model\SysAuth');
    }

    /**
     * 角色列表
     */
    public function index() {
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = 0;
                return json($this->model->authList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '系统角色列表',
                'data'   => '',
                'status' => [['id' => 1, 'title' => '启用'], ['id' => 0, 'title' => '禁用']],
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
     * 商户角色
     * @return mixed|\think\response\Json
     */
    public function user() {
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = 1;
                return json($this->model->authList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '商户角色列表',
                'data'   => '',
                'status' => [['id' => 1, 'title' => '启用'], ['id' => 0, 'title' => '禁用']],
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
     * 添加角色
     * @return mixed|\think\response\Json
     */
    public function add() {
        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '添加角色',
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Auth.add');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            return $this->model->__add($post);
        }
    }

    /**
     * 修改管理员信息
     * @return mixed|string|\think\response\Json
     */
    public function edit() {
        if (!$this->request->isPost()) {

            //查找所需修改角色
            $auth = $this->model->where('id', $this->request->get('id'))->find();
            if (empty($auth)) return msg_error('暂无数据，请重新刷新页面！');

            //基础数据
            $basic_data = [
                'title' => '修改角色信息',
                'auth'  => $auth,
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Auth.edit');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            return $this->model->__edit($post);
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
     * 删除角色
     * @return \think\response\Json
     * @throws \Exception
     */
    public function del() {
        $get = $this->request->get();

        //验证数据
        if (!is_array($get['id'])) {
            $validate = $this->validate($get, 'app\common\validate\Auth.del');
            if (true !== $validate) return __error($validate);
        }

        //执行更新操作操作
        if (!is_array($get['id'])) {
            $del = $this->model->where('id', $get['id'])->delete();
            model('app\common\model\SysAuthNode')->where('auth', $get['id'])->delete();
        } else {
            $del = $this->model->whereIn('id', $get['id'])->delete();
            model('app\common\model\SysAuthNode')->whereIn('auth', $get['id'])->delete();
        }

        if ($del >= 1) {

            return __success('删除成功！');
        } else {
            return __error('数据有误，请刷新重试！');
        }
    }

    /**
     * 更改角色状态
     * @return \think\response\Json
     */
    public function status() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Auth.status');
        if (true !== $validate) return __error($validate);

        //判断菜单状态
        $status = $this->model->where('id', $get['id'])->value('status');
        $status == 1 ? list($msg, $status) = ['角色禁用成功', $status = 0] : list($msg, $status) = ['角色启用成功', $status = 1];

        //执行更新操作操作
        $update =  $this->model->__edit(['status' => $status,'id' => $get['id']],$msg);

        return $update;
    }

    /**
     * 授权信息
     * @return mixed|string|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function authorize() {

        if (!$this->request->isPost()) {

            //查找所需授权角色
            $auth = $this->model->where('id', $this->request->get('id/d'))->find();
            if (empty($auth)) return msg_error('暂无数据，请重新刷新页面！');

            $node = model('app\common\model\SysNode')->where(['is_auth' => 1])->order('node asc')->select();

            $auth_node = model('app\common\model\SysAuthNode')->where(['auth' => $auth['id']])->select();

            $type = $this->request->get('type/d',0);

            foreach ($node as $k=> &$vo) {
                if($type == 0  && ( strpos($vo['node'],'user') === 0 || strpos($vo['node'],'pay') === 0 || strpos($vo['node'],'index') === 0 || strpos($vo['node'],'agent') === 0 ) ){
                    unset($node[$k]);
                    continue;
                }
                if($type == 1  && (strpos($vo['node'],'admin') === 0 || strpos($vo['node'],'index') === 0)  ){
                    unset($node[$k]);
                    continue;
                }

                $i = 0;
                foreach ($auth_node as $al) {
                    $vo['id'] == $al['node'] && $i++;
                }
                $i == 0 ? $vo['is_checked'] = false : $vo['is_checked'] = true;
            }


            //基础数据
            $basic_data = [
                'title' => '角色授权',
                'auth'  => $auth,
                'node'  => $node,
            ];
            $this->assign($basic_data);

            return $this->fetch();
        } else {
            $post = $this->request->post();
            empty($post['node_id']) && $post['node_id'] = [];

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Auth.authorize');
            if (true !== $validate) return __error($validate);

            $insertAll = [];
            foreach ($post['node_id'] as $vo) {
                $insertAll[] = [
                    'auth' => $post['auth_id'],
                    'node' => $vo,
                ];
            }

            //清空旧数据
            model('app\common\model\SysAuthNode')->where(['auth' => $post['auth_id']])->delete();
            //保存数据,返回结果
            return model('app\common\model\SysAuthNode')->authorize($insertAll);
        }
    }
}