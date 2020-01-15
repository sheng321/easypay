<?php
namespace app\admin\controller;

use app\common\controller\AdminController;

use app\common\model\UmoneyLog;

/**  金额流水
 * Class Log
 * @package app\admin\controller
 */
class Moneylog  extends AdminController
{

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
        $this->model = new UmoneyLog();
    }

    /**
     * 会员流水
     * @return mixed
     */
    public function index()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 0;
            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '会员流水列表',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }


    /**
     * 通道流水
     * @return mixed
     */
    public function channel()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 1;
            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '通道流水列表',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }


    /**
     * 平台流水
     * @return mixed
     */
    public function sys()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 2;
            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '平台流水列表',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }



    public function index_money()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 0;
            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '商户资金',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }

    public function channel_money()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 0;
            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '通道资金',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }

    public function sys_money()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 0;
            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '平台资金',
            'data'  => '',
        ];
        return $this->fetch('', $basic_data);
    }



}
