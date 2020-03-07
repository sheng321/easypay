<?php
namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\model\ChannelGroup;
use app\common\model\ChannelProduct;
use app\common\model\PayProduct;

/**
 * 支付产品管理
 * Class Product
 * @package app\admin\controller
 */
class Product  extends AdminController
{

    /**
     * config模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * node constructor.
     */
    public function __construct() {
        parent::__construct();
      
        $this->model =(new PayProduct());
    }



    public function index()
    {

        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                return json($this->model->payList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '支付产品列表',
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
     * 更改支付产品状态
     * @return \think\response\Json
     */
    public function status() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\PayProduct.status');
        if (true !== $validate) return __error($validate);

        //判断菜单状态
        $status = $this->model->where('id', $get['id'])->value('status');
        $status == 1 ? list($msg, $status) = ['支付产品禁用成功', $status = 0] : list($msg, $status) = ['支付产品启用成功', $status = 1];

        if($status == 0){
            //使用事物保存数据
            $this->model->startTrans();
            $save = $this->model->save(['status' => $status,'id' => $get['id']],['id'=>$get['id']]);

          
            $del = (new ChannelProduct())->destroy(function($query) use ($get){
                $query->where(['p_id'=>$get['id']]);
            });

            if (!$save || !$del) {
                $this->model->rollback();
                $msg = '数据有误，请稍后再试！';
                return __error($msg);
            }
            $this->model->commit();

            return __success($msg);

        }else{
            //执行更新操作操作
            $update =  $this->model->__edit(['status' => $status,'id' => $get['id']],$msg);
            return $update;
        }

    }
    /**
     * 更改支付产品状态
     * @return \think\response\Json
     */
    public function cli() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\PayProduct.cli');
        if (true !== $validate) return __error($validate);

        //判断菜单状态
        $status = $this->model->where('id', $get['id'])->value('cli');
        $status == 1 ? list($msg, $status) = ['客户端显示成功', $status = 0] : list($msg, $status) = ['客户端隐藏成功', $status = 1];

        //执行更新操作操作
        $update =  $this->model->__edit(['cli' => $status,'id' => $get['id']],$msg);

        return $update;
    }




    /**
     * 添加支付产品
     * @return mixed|\think\response\Json
     */
    public function add() {
        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '添加支付产品',
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\PayProduct.add');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            return $this->model->__add($post);
        }
    }

    /**
     * 修改
     * @return mixed|string|\think\response\Json
     */
    public function edit() {
        if (!$this->request->isPost()) {

            //查找所需修改支付产品
            $auth = $this->model->where('id', $this->request->get('id'))->find();
            if (empty($auth)) return msg_error('暂无数据，请重新刷新页面！');

            //基础数据
            $basic_data = [
                'title' => '修改支付产品信息',
                'auth'  => $auth,
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\PayProduct.edit');
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
     * 删除支付产品
     * @return \think\response\Json
     * @throws \Exception
     */
    public function del() {
        $get = $this->request->get();



        //验证数据
        if (!is_array($get['id'])) {
            $validate = $this->validate($get, 'app\common\validate\PayProduct.del');
            if (true !== $validate) return __error($validate);
        }else{
            foreach ($get['id'] as $k => $val){
                $data['id'] = $val;
                $validate = $this->validate($data, 'app\common\validate\PayProduct.del');
                if (true !== $validate) unset($get['id'][$k]);
            }
        }

        //使用事物保存数据
        $this->model->startTrans();
        $del1 = $this->model->destroy($get['id']);

        //删除关联数据
        $del = (new ChannelProduct())->destroy(function($query) use ($get){
            $query->where(['p_id'=>$get['id']]);
        });

        if (!$del1 || !$del) {
            $this->model->rollback();
            $msg = '数据有误，请稍后再试！';
            return __error($msg);
        }
        $this->model->commit();
        return __success('删除成功');

    }
    


}
