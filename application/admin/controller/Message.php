<?php
namespace app\admin\controller;

use app\common\controller\AdminController;

/**
 * 消息中心
 * Class Message
 * @package app\admin\controller
 */
class Message extends AdminController
{

    /**
     * Level模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * node constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->model = model('app\common\model\Message');
    }

    public function index()
    {
        if (!$this->request->isPost()){

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = $this->request->get('type1/d', 0);
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title' => '公告管理',
                'data' => '',
            ];

            return $this->fetch('', $basic_data);
        }
    }

    //添加公告
    public function add()
    {
        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '添加公告',
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->post();

            $data['type'] = $post['type'];
            $data['title'] = $post['data1'];

            if($data['type'] == 4){
                $data['title'] = '首页弹窗显示';
                $data['type'] = 0;
                $id =  $this->model->where($data)->value('id');
                if(!empty($id)){
                    $data['data'] = $post['data2'];
                    $data['id'] = $id;
                   return $this->model->__edit($data);
                }
            }
            $data['data'] = $post['data2'];

            //保存数据,返回结果
            return $this->model->__add($data);
        }
    }

    //删除公告
    public function del() {
        $get = $this->request->get();
        $del = $this->model->whereIn('id', $get['id'])->delete();
        if ($del >= 1) {
            return __success('删除成功！');
        } else {
            return __error('数据有误，请刷新重试！');
        }
    }

    public function form(){
        return $this->fetch('form');
    }


    /**
     * 任务处理中心
     *
     * @return mixed|\think\response\Json
     */
    public function task()
    {
        if (!$this->request->isPost()){

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = $this->request->get('type1/d', 0);
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title' => '任务中心',
                'data' => '',
            ];

            return $this->fetch('', $basic_data);
        }
    }

    /**
     * 处理任务
     * @return \think\response\Json
     */
    public function do_task() {
        $get = $this->request->get();
        $update = [];
        foreach ($get['id'] as $k=> $v){
            $update[$k] =['id'=>$v,'status'=>1];
        }
        $up = false;
       if(!empty($update)) $up = $this->model->saveAll($update);





        if (!$up) {
            return __error('数据有误，请刷新重试！');
        } else {
            return __success('处理成功！');
        }
    }



}
