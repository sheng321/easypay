<?php


namespace app\admin\controller;

use app\common\controller\AdminController;


/**用户费率
 * Class Level
 * @package app\admin\controller
 */
class Rate extends AdminController {

    /**
     * Rate模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * node constructor.
     */
    public function __construct() {
        parent::__construct();
        
        $this->model = model('app\common\model\SysRate');
    }

    /**
     * 费率列表
     */
    public function index(){
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = 0;
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '系统费率列表',
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
     * 个人用户费率列表
     */
    public function user(){
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = 1;
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '系统费率列表',
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
     * 添加费率
     * @return mixed|\think\response\Json
     */
    public function add() {
        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '添加费率',
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->only('title,remark,l_rate');

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Level.add');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            return $this->model->__add($post);
        }

    }

    /**
     * 修改费率
     * @return mixed|string|\think\response\Json
     */
    public function edit() {
        if (!$this->request->isPost()) {

            //查找所需修改费率
            $auth = $this->model->where('id', $this->request->get('id'))->find();
            if (empty($auth)) return msg_error('暂无数据，请重新刷新页面！');

            //基础数据
            $basic_data = [
                'title' => '修改费率信息',
                'auth'  => $auth,
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->only('id,title,remark,l_rate');

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Level.edit');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            $result = $this->model->__edit($post);

            return $result;
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
     * 删除费率
     * @return \think\response\Json
     * @throws \Exception
     */
    public function del() {
        $get = $this->request->get();

        //验证数据
        if (!is_array($get['id'])) {
            $validate = $this->validate($get, 'app\common\validate\Rate.del');
            if (true !== $validate) return __error($validate);
        }

        //执行更新操作操作
        if (!is_array($get['id'])) {
            $del = $this->model->where('id', $get['id'])->delete();
            model('app\common\model\SysRateNode')->where('auth', $get['id'])->delete();
        } else {
            $del = $this->model->whereIn('id', $get['id'])->delete();
            model('app\common\model\SysRateNode')->whereIn('auth', $get['id'])->delete();
        }

        if ($del >= 1) {

            return __success('删除成功！');
        } else {
            return __error('数据有误，请刷新重试！');
        }
    }


    /**
     * 更改费率状态
     * @return \think\response\Json
     */
    public function status() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Rate.status');
        if (true !== $validate) return __error($validate);

        //判断菜单状态
        $status = $this->model->where('id', $get['id'])->value('status');
        $status == 1 ? list($msg, $status) = ['费率禁用成功', $status = 0] : list($msg, $status) = ['费率启用成功', $status = 1];

        //执行更新操作操作
        $update =  $this->model->__edit(['status' => $status,'id' => $get['id']],$msg);

        return $update;
    }


}