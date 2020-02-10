<?php
namespace app\admin\controller;

use app\common\controller\AdminController;

/**
 * 对账管理
 * Class Accounts
 * @package app\admin\controller
 */
class Accounts  extends AdminController
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
        $this->model = model('app\common\model\Accounts');
    }

    /**
     * 会员对账
     * @return mixed
     */
    public function member()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax'){
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['channel_id'] = 0;
            $search['df_id'] = 0;
            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '会员对账列表',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }


    //通道分析
    public function info()
    {
        $id = $this->request->get('id/d', 0);
        $info = json_decode($this->model->where(['id'=>$id])->value('info'),true);
        if(empty($info)) return msg_error('无数据，请重试。');

        //基础数据
        $basic_data = [
            'title' => '通道分析',
            'info'  => $info,
        ];

        return $this->fetch('', $basic_data);

    }



    /**
     * 通道对账
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
            'title' => '通道对账列表',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }

    /**
     * 代付通道对账
     * @return mixed
     */
    public function channel_df()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 3;
            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '代付通道对账列表',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }

    /**
     * 平台对账
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
            'title' => '平台对账列表',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }
    
}
