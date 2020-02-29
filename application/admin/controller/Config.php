<?php

namespace app\admin\controller;


use app\common\controller\AdminController;

/**
 * 系统配置
 * Class Config
 * @package app\admin\controller
 */
class Config extends AdminController {

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
        $this->model = model('app\common\model\SysConfig');
    }

    /**
     * 系统配置信息列表
     */
    public function index() {
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 500);
                $search = (array)$this->request->get('search', []);
                return json($this->model->configList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title' => '系统参数列表',
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
     * 设置
     * @return mixed
     */
    public function set() {
        $this->assign(['data'  => config('set.')]);
        if ($this->request->isPost()) {
            $post= $this->request->post();
            setconfig('set',$post);//配置文件只用单引号

            return __success('操作成功');

        }

        //基础数据
        $basic_data = [
            'title' => '网站配置',
        ];
        return $this->fetch('', $basic_data);
    }
}