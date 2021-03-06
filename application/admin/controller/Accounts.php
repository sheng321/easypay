<?php
namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\service\CountService;

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
     * 通道成功率
     */
    public function index() {
        $data = CountService::success_rate();

        //根据字段last_name对数组$data进行降序排列
        if(!empty($data['channel'])){
            $rate1 = array_column($data['channel'],'rate');
            array_multisort($rate1,SORT_DESC,$data['channel']);
        }else{
            $data['channel'] = [];
        }
        if(!empty($data['payment'])){
            $rate2 = array_column($data['payment'],'rate');
            array_multisort($rate2,SORT_DESC,$data['payment']);
        }else{
            $data['payment'] = [];
        }
        if(!empty($data['channel_group'])){
            $rate3 = array_column($data['channel_group'],'rate');
            array_multisort($rate3,SORT_DESC,$data['channel_group']);
        }else{
            $data['channel_group'] = [];
        }

        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '通道成功率',
                'data'  => $data,
            ];

            return $this->fetch('', $basic_data);
        }
    }

    /**
     * 会员对账
     * @return mixed
     */
    public function user()
    {
        CountService::mem_account();//商户统计

        CountService::agent_account();//代理统计

        //ajax访问
        if ($this->request->get('type') == 'ajax'){
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['channel_id'] = 0;
            $search['df_id'] = 0;
            $search['withdraw_id'] = 0;
            $search['user'] = 1;
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

        CountService::channel_account();//通道统计
        //ajax访问
        if ($this->request->get('type') == 'ajax'){
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['df_id'] = 0;
            $search['uid'] = 0;
             $search['withdraw_id'] = 0;
            $search['type'] = 3;
            return json($this->model->aList($page, $limit, $search));
        }

        $Channel =   \app\common\model\Channel::idRate();//通道
        $PayProduct =  \app\common\model\PayProduct::idArr();//支付产品

        $Channel_data = [];
        foreach ($Channel as $k =>$v){
            if($v['pid'] != 0){
                $p_id = json_decode($v['p_id'],true);
                $product_name = empty($p_id)?'未知':$PayProduct[$p_id[0]];
                $Channel_data[$k] = $v['title'].'-'.$product_name;
            }
        }

        //基础数据
        $basic_data = [
            'title' => '支付通道对账列表',
            'data'  => '',
            'channel'  => $Channel_data,
        ];

        return $this->fetch('', $basic_data);
    }

    public function info2()
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
     * 提现结算对账
     * @return mixed
     */
    public function withdraw()
    {

        CountService::withdraw_account();//提现下发统计
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['df_id'] = 0;
            $search['uid'] = 0;
            $search['channel_id'] = 0;
            $search['type'] = 4;
            return json($this->model->aList($page, $limit, $search));
        }

        $Channel =   \app\common\model\Channel::idRate();//通道
        $Channel_data = [];
        foreach ($Channel as $k =>$v){
            if($v['pid'] == 0) $Channel_data[$k] = $v['title'];
        }

        //基础数据
        $basic_data = [
            'title' => '提现结算对账列表',
            'data'  => '',
            'channel'  => $Channel_data,
        ];

        return $this->fetch('', $basic_data);
    }



    public function info1()
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
     * 代付通道对账
     * @return mixed
     */
    public function df()
    {

        CountService::df_account();//代付下发统计
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['withdraw_id'] = 0;
            $search['uid'] = 0;
            $search['channel_id'] = 0;
            $search['type'] = 5;
            return json($this->model->aList($page, $limit, $search));
        }


        $Channel =   \app\common\model\ChannelDf::info();//通道
        $Channel_data = [];
        foreach ($Channel as $k =>$v){
             $Channel_data[$k] = $v['title'];
        }

        //基础数据
        $basic_data = [
            'title' => '代付通道对账列表',
            'data'  => '',
            'channel'  => $Channel_data,
        ];

        return $this->fetch('', $basic_data);
    }

    /**
     * 平台对账
     * @return mixed
     */
    public function sys()
    {
        CountService::mem_account();//商户统计
        CountService::agent_account();//代理统计
        CountService::channel_account();//通道统计
        CountService::withdraw_account();//提现下发统计
        CountService::df_account();//代付下发统计
        CountService::sys_account();//平台统计

        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['withdraw_id'] = 0;
            $search['uid'] = 0;
            $search['channel_id'] = 0;
            $search['df_id'] = 0;
            $search['type'] = 6;
            return json($this->model->bList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '平台统计列表',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }
    
}
