<?php

namespace app\agent\controller;


use app\common\controller\AgentController;


class Agent extends AgentController {

    /**
     * Agent模型对象
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
     * 代理列表
     * @return mixed
     */
    public  function  index(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 10);
            $search = (array)$this->request->get('search', []);
            $search['uid'] = $this->user['uid'];
            return $this->model->aList($page, $limit, $search);
        }

        $basic_data = [
            'title' => 'IP白名单列表',
            'type' => [0=>'登入',1=>'结算',2=>'代付'],
        ];
        return $this->fetch('', $basic_data);
    }

    /**
     * 分组管理
     * @return mixed
     */
    public  function  group(){

        $this->model = model('app\common\model\Ulevel');

        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = 0;
                $search['uid'] = $this->user['uid'];
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '代理分组列表',
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

    /**商户列表
     * @return mixed
     */
    public  function  member(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 10);
            $search = (array)$this->request->get('search', []);
            $search['uid'] = $this->user['uid'];
            return $this->model->aList($page, $limit, $search);
        }

        $basic_data = [
            'title' => 'IP白名单列表',
            'type' => [0=>'登入',1=>'结算',2=>'代付'],
        ];
        return $this->fetch('', $basic_data);
    }




}