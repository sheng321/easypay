<?php
namespace app\admin\controller;

use app\common\controller\AdminController;
use redis\StringModel;

class Index  extends AdminController
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


    public function index()
    {
        //左侧菜单
        $apimenu = new \app\admin\controller\api\Menu();

        $basic_data = [
             'title'=> '主页',
            'menu_view' => $apimenu->getNav(),
        ];
        return $this->fetch('', $basic_data);
    }

    /**
     * 首页欢迎界面
     * @return mixed
     */
    public function welcome(){
        //当前访问量
        $redis1 = (new StringModel())->instance();
        $redis1->select(2);
        $keys =  $redis1->keys('flow_*');

        if(empty($keys)){
            $info = [];
        }else{
            $info = $redis1->mget($keys);
        }

        $option['legend'] = [];
        $option['xAxis'] = [];
        $option['series'] = [];
        foreach($info as $k=>$v){
            $des = json_decode($v,true);

            $option['legend'] = $des['title'];
            $option['xAxis'][$k] = $des['time'];

            foreach ($des['num'] as $k1 => $v1){
                $option['series'][$k1]['name'] = $des['title'][$k1];
                $option['series'][$k1]['data'][] = $des['num'][$k1];
            }
        }

        //今日会员跑量
        $search1['channel_id'] = 0;
        $search1['df_id'] = 0;
        $search1['withdraw_id'] = 0;
        $search1['user'] = 1;
        $search1['day'] = date('Y-m-d');
        $user = $this->model->aList(1, 100000, $search1);


        $user_data['legend'] = ['总交易金额','已支付金额','手续费'];
        $user_data['xAxis'] = [];
        $user_data['series'] = [];
        foreach ($user['data'] as $k =>$v){
            $name = '代理';
            if($v['type'] == 0){
                $name = '商户';
            }
            $user_data['xAxis'][$k] = $name.$v['uid'];

            $user_data['series']['total_fee_all']['name'] = '总交易金额';
            $user_data['series']['total_fee_all']['data'][] =!empty($v['total_fee_all'])?$v['total_fee_all']:0;

            $user_data['series']['total_fee_paid']['name'] = '已支付金额';
            $user_data['series']['total_fee_paid']['data'][] =!empty($v['total_fee_paid'])?$v['total_fee_paid']:0;

            $user_data['series']['total_fee']['name'] = '手续费';
            $user_data['series']['total_fee']['data'][] =!empty($v['total_fee'])?$v['total_fee']:0;

        }

        //今日通道跑量
        $search2['df_id'] = 0;
        $search2['uid'] = 0;
        $search2['withdraw_id'] = 0;
        $search2['type'] = 3;
        $search2['day'] = date('Y-m-d');
        $channel = $this->model->aList(1, 100000, $search2);

        $PayProduct =  \app\common\model\PayProduct::idArr();//支付产品
        $Channel1 =   \app\common\model\Channel::idRate();//通道
        $channel_data['legend'] = ['总交易金额','已支付金额','手续费'];
        $channel_data['xAxis'] = [];
        $channel_data['series'] = [];
        foreach ($channel['data'] as $k =>$v){
            $p_id = json_decode($Channel1[$v['channel_id']]['p_id'],true);
            $product_name = empty($p_id)?'未知':$PayProduct[$p_id[0]];

            $name = $Channel1[$v['channel_id']]['title'];
            $channel_data['xAxis'][$k] = $name.$product_name;

            $channel_data['series']['total_fee_all']['name'] = '总交易金额';
            $channel_data['series']['total_fee_all']['data'][] =!empty($v['total_fee_all'])?$v['total_fee_all']:0;

            $channel_data['series']['total_fee_paid']['name'] = '已支付金额';
            $channel_data['series']['total_fee_paid']['data'][] =!empty($v['total_fee_paid'])?$v['total_fee_paid']:0;

            $channel_data['series']['total_fee']['name'] = '手续费';
            $channel_data['series']['total_fee']['data'][] =!empty($v['total_fee'])?$v['total_fee']:0;

        }

        //平台盈利
        $search3['withdraw_id'] = 0;
        $search3['uid'] = 0;
        $search3['channel_id'] = 0;
        $search3['df_id'] = 0;
        $search3['type'] = 6;
        $sys = $this->model->bList(1, 7, $search3);

        $sys_data['legend'] = ['交易金额','收入金额','订单收益','下发收益','平台支出','平台收入','平台收益'];
        $sys_data['xAxis'] = [];
        $sys_data['series'] = [];
        foreach ($sys['data'] as $k=>$v ){
            $sys_data['xAxis'][$k] = $v['day'];


            $sys_data['series']['channel_total_fee_all']['name'] = '交易金额';
            $channel_total_fee_all = !empty($v['channel_total_fee_all'])?$v['channel_total_fee_all']:0;
            $sys_data['series']['channel_total_fee_all']['data'][] =$channel_total_fee_all;

            $sys_data['series']['channel_total_fee_paid']['name'] = '收入金额';
            $channel_total_fee_paid = !empty($v['channel_total_fee_paid'])?$v['channel_total_fee_paid']:0;
            $sys_data['series']['channel_total_fee_paid']['data'][] =$channel_total_fee_paid;


            $sys_data['series']['channel_platform']['name'] = '订单收益';
            $channel_platform = !empty($v['channel_platform'])?$v['channel_platform']:0;
            $sys_data['series']['channel_platform']['data'][] =$channel_platform;

            $sys_data['series']['withdraw_platform']['name'] = '下发收益';
            $withdraw_platform = !empty($v['withdraw_platform'])?$v['withdraw_platform']:0;
            $sys_data['series']['withdraw_platform']['data'][] =$withdraw_platform;


            $sys_data['series']['money_dec']['name'] = '平台支出';
            $money_dec = !empty($v['money_dec'])?$v['money_dec']:0;
            $sys_data['series']['money_dec']['data'][] = $money_dec;

            $sys_data['series']['money_inc']['name'] = '平台收入';
            $money_inc = !empty($v['money_inc'])?$v['money_inc']:0;
            $sys_data['series']['money_inc']['data'][] =$money_inc;

            $sys_data['series']['total']['name'] = '平台收益';
            $total = bcsub(bcadd(bcadd($channel_platform,$withdraw_platform),$money_inc),$money_dec,2);
            $sys_data['series']['total']['data'][] = $total;

        }


        $basic_data = [
            'title'=> '欢迎页',
            'option' => $option,
            'sys_data' => $sys_data,
            'user_data' => $user_data,
            'channel_data' => $channel_data,
        ];
        return $this->fetch('',$basic_data);
    }

}
