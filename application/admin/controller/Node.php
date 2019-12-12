<?php


namespace app\admin\controller;

use app\common\controller\AdminController;

use app\common\service\NodeService;

/**
 * 节点管理
 * Class Node
 * @package app\admin\controller
 */
class Node extends AdminController {

    /**
     * node模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * node constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->model = model('app\common\model\SysNode');
    }

    /**
     * 节点列表
     */
    public function index() {
        if (!$this->request->isPost()) {
            //ajax访问获取数据
            if (!empty($this->request->get('module'))) {
                return json($this->model->nodeModuleList($this->request->get('module')));
            }

            $module_list = $this->model->where(['type' => 1])->order(['node'=>'asc'])->select()->toArray();


            foreach ($module_list as $k => $val) $k == 0 ? $module_list[$k]['is_selectd'] = true : $module_list[$k]['is_selectd'] = false;
            //基础数据
            $basic_data = [
                'title'       => '系统节点列表',
                'module_list' => $module_list,
            ];
            $this->assign($basic_data);

            return $this->fetch('');
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
     * 更改节点状态
     * @return \think\response\Json
     */
    public function status() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Node.status');
        if (true !== $validate) return __error($validate);

        //判断菜单状态
        $status = $this->model->where('id', $get['id'])->value('is_auth');
        $status == 1 ? list($msg, $status) = ['节点禁用成功', $status = 0] : list($msg, $status) = ['节点启用成功', $status = 1];

        //执行更新操作操作
        $data['id'] = $get['id'];
        $data['is_auth'] = $status;

        //执行更新操作操作
        $update = $this->model->__edit($data);
        return $update;

    }


    public function command() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Node.command');
        if (true !== $validate) return __error($validate);

        //判断菜单状态
        $status = $this->model->where('id', $get['id'])->value('command');
        $status == 1 ? list($msg, $status) = ['口令禁用成功', $status = 0] : list($msg, $status) = ['口令启用成功', $status = 1];


        $data['id'] = $get['id'];
        $data['command'] = $status;

        //执行更新操作操作
        $update = $this->model->__edit($data);
        return $update;
    }





    /**
     * 刷新节点
     */
    public function refresh_node() {

        if (!$this->request->isPost()){
            //get ajax 访问
            if ($this->request->get('type') == 'ajax') {
                $node_list = NodeService::refreshNode();
                if (!empty($node_list)) return __success('节点刷新成功！');
                return __error('暂无数据变化');
            }
            $modules = NodeService::getFolders(env('app_path'));

            $module_list = [];
            foreach ($modules as $k => $val) {
                $node = $this->model->where(['node' => $val, 'type' => 1])->find();
                !empty($node) ? $module_list[$k] = ['module' => $val, 'title' => $node['title']] : $module_list[$k] = ['module' => $val, 'title' => ''];
                $val == 'admin' ? $module_list[$k]['is_checked'] = true : $module_list[$k]['is_checked'] = false;
            }

            $basic_data = [
                'title'       => '系统节点列表',
                'module_list' => $module_list,
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->post();
            if (empty($post['module'])) return __error('请选中需要刷新节点的模块！');
            $node_list = NodeService::refreshNode($post['module']);
            if ($node_list['code'] == 0) {
                //清空菜单缓存
                clear_menu();
                return __success($node_list['msg']);
            } else {
                return __error($node_list['msg']);
            }
        }
    }

    /**
     * 清除失效节点
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function clear_node() {
        if (!$this->request->isPost()) {
            $basic_data = [
                'title' => '清除失效节点',
            ];
            return $this->fetch('', $basic_data);
        } else {
            $clean = NodeService::cleanNode();
            if ($clean['code'] == 0) {
                return __success($clean['msg']);
            } else {
                return __error($clean['msg']);
            }
        }

    }


}