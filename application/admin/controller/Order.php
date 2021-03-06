<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\model\PayProduct;
use app\pay\service\Payment;
use redis\StringModel;
use think\facade\Session;

/**
 *  订单记录
 */
class Order extends AdminController {

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
        $this->model = new \app\common\model\Order();
    }
    /**
     *  订单列表
     * @return void
     */
    public function index(){

        if ($this->request->get('type') == 'ajax'){

            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->get('search', []);
            return json($this->model->alist($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title'  => '订单列表',
            'data'   => '',
            'order' => config('order.'),
            'product' => PayProduct::idArr(),//支付产品
        ];

        return $this->fetch('', $basic_data);
    }

    /**
     * 下载
     * @return void
     */
    public function export(){

        $field = [
            'id',
            'mch_id',
            'mch_id1',
            'mch_id2',
            'out_trade_no',
            'system_no',
            'transaction_no',
            'amount',
            'actual_amount',
            'cost_rate',
            'run_rate',
            'agent_rate',
            'agent_rate2',
            'total_fee',
            'Platform',
            'settle',
            'upstream_settle',
            'agent_amount',
            'agent_amount2',

            'payment_id',
            'channel_group_id',
            'pay_status',
            'channel_id',
            'over_time',
            'notice',
            'create_time',
            'pay_time',
            'ip',
            'update_at',
            'over_time',
        ];

        $title = [
            'id'=>'ID',
            'mch_id'=>'商户号',
            'mch_id1'=>'代理',
            'mch_id2'=>'上上代理',
            'out_trade_no'=>'商户单号',
            'system_no'=>'系统单号',
            'transaction_no'=>'上游单号',
            'amount'=>'下单金额',
            'actual_amount'=>'实际支付',
            'total_fee'=>'手续费',
            'cost_rate'=>'成本费率',
            'run_rate'=>'运营费率',

            'settle'=>'商户结算',
            'agent_rate'=>'上代理费率',
            'agent_rate2'=>'上上级代理费率',

            'upstream_settle'=>'上游结算',
            'agent_amount'=>'上级代理商结算',
            'agent_amount2'=>'上上级代理商结算',
            'Platform'=>'平台收益',


            'channelgroup_name'=>'通道分组',
            'product_name'=>'支付类型',
            'channel_name'=>'通道',
            'pay_status_name'=>'支付状态',
            'notice_name'=>'通知',
            'create_time'=>'提交时间',
            'pay_time'=>'支付时间',
            'ip'=>'IP',
            'update_at'=>'最近更新时间',
        ];

        if ($this->request->get('type') == 'ajax'){
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->except(['type','page','limit']);
            $search['field'] = $field;

            if(empty($search['table'])){
                return json($this->model->alist($page, $limit, $search));
            }else{
                //历史订单下载
                return json($this->model->hlist($page, $limit, $search));
            }


        }

        $field1 = [
            'pay_status_name',
            'notice_name',
            'product_name',
            'channel_name',
            'channelgroup_name',
        ];

        $field =  array_merge($field,$field1);

        //基础数据
        $basic_data = [
            'title'  => '订单列表',
            'url'  =>request() -> url(),
            'data'   => ['field'=>json_encode($field),'title'=>json_encode($title)],
        ];

        return $this->fetch('common@export/index', $basic_data);
    }

    /**
     *  历史订单列表
     * @return void
     */
    public function history(){

        if ($this->request->get('type') == 'ajax'){

            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->get('search', []);
            return json($this->model->hlist($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title'  => '历史订单列表',
            'data'   => '',
            'order' => config('order.'),
            'product' => PayProduct::idArr(),//支付产品
        ];

        return $this->fetch('', $basic_data);
    }






    /**
     * 代理分润订单列表
     * @return mixed|\think\response\Json
     */
    public function agent(){
        if ($this->request->get('type') == 'ajax'){
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->get('search', []);
            return json($this->model->alist($page, $limit, $search,1));
        }
        //基础数据
        $basic_data = [
            'title'  => '订单列表',
            'data'   => '',
            'order' => config('order.'),
            'product' =>  PayProduct::idArr()//支付产品
        ];

        return $this->fetch('', $basic_data);
    }


    /**
     *  处理订单列表
     * @return void
     */
    public function dispose(){
        if (!$this->request->isPost()) {
            if ($this->request->get('type') == 'ajax'){
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                return json($this->model->blist($page, $limit, $search));
            }
            //基础数据
            $basic_data = [
                'title'  => '处理订单列表',
                'data'   => '',
                'order' => config('order.'),
                'product' =>  PayProduct::idArr()//支付产品
            ];
            return $this->fetch('', $basic_data);

        } else {
            $post = $this->request->post();
            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_field');
            if (true !== $validate) return __error($validate);

            $post['field'] =  'remark';
            //保存数据,返回结果
            return model('app\common\model\OrderDispose')->editField($post);

        }
    }




    /**
     *  详情
     * @return void
     */
    public function details(){

        if (!$this->request->isPost()) {
            $id = $this->request->get('id/d',0);
            $order =  $this->model->quickGet($id);
            if(empty($order)) msg_error("订单不存在");

            $ChannelGroup =  \app\common\model\ChannelGroup::idArr();//通道分组
            $Channel =  \app\common\model\Channel::idRate();//通道
            $PayProduct =  \app\common\model\PayProduct::idArr();//支付产品


            $order['product_name'] = empty($PayProduct[$order['payment_id']])?'未知':$PayProduct[$order['payment_id']];
            $order['channelgroup_name'] = empty($ChannelGroup[$order['channel_group_id']])?'未知':$ChannelGroup[$order['channel_group_id']];
            $order['channel_name'] = empty($Channel[$order['channel_id']])?'未知':$Channel[$order['channel_id']]['title'];


            if(($order['pay_status'] == 0) && (time() > $order['over_time'])) $order['pay_status'] = 3;//显示订单关闭

            $order['over_time'] = date('Y-m-d H:i:s',$order['over_time']);

            $order['pay_status1'] = config('order.pay_status.'.$order['pay_status']);
            $order['notice1'] = config('order.notice.'.$order['notice']);

            $this->assign("order",$order);
            return $this->fetch('');

        } else {
            $id = $this->request->get('id/d',0);
            $order = $this->request->get('order/s',0);
            $type = $this->request->get('type/d',0);


            $thisOrder = $this->model->quickGet($id);
            if(empty($thisOrder) ||$thisOrder['pay_status'] == 1 ||$thisOrder['pay_status'] == 2 ) return __error('订单状态为下单失败和已支付不可以更改');

            $OrderDispose = new \app\common\model\OrderDispose();

            $Dispose = $OrderDispose->quickGet(['pid'=>$id]);

            $this->model->startTrans();

            if($type == 0){
                $save = $this->model->save(['pay_status'=>0,'id'=>$id,'over_time'=>time()+3600],['id'=>$id]);//开启订单
                if(empty($Dispose)){
                    $save1 =  $OrderDispose->create([
                        'pid'=>$id,
                        'system_no'=>$order,
                        'record'=>$this->user['username'].'-开启',
                    ]);
                }else{
                    $save1 = $OrderDispose->save([
                        'id'=>$Dispose['id'],
                        'record'=>$Dispose['record'].'|'.$this->user['username'].'-开启',
                    ],['id'=>$Dispose['id']]);
                }
            }else{
                $save = $this->model->save(['pay_status'=>3,'id'=>$id],['id'=>$id]);//关闭订单
                if(empty($Dispose)){
                    $save1 =  $OrderDispose->create([
                        'pid'=>$id,
                        'system_no'=>$order,
                        'record'=>$this->user['username'].'-关闭',
                    ]);
                }else{
                    $save1 = $OrderDispose->save([
                        'id'=>$Dispose['id'],
                        'record'=>$Dispose['record'].'|'.$this->user['username'].'-关闭',
                    ],['id'=>$Dispose['id']]);
                }
            }

            if (!$save || !$save1) {
                $this->model->rollback();
                return __error('数据有误，请稍后再试！');
            }
            $this->model->commit();
            return __success('操作成功！');


        }
    }


    /**
     *  补发通知
     *
     * @return void
     */
    public function replacement(){

        if ($this->request->isPost()){

            $id = $this->request->get('id/d',0);

            $__token__ = $this->request->get('__token__/s','');
            $__hash__ = Session::pull('__hash__');
            if($__token__ !== $__hash__)  return __error("Token验证失败");


            $order =  $this->model->quickGet($id);
            if(empty($order) || $order['pay_status'] == 1 ) return __error("订单不存在或者下单失败");


            //订单已关闭 订单未支付
            if( $order['pay_status'] == 0 || $order['pay_status'] == 3){
                //不能修改前一天的订单
                $time =   strtotime($order['create_at']) - strtotime(date('Y-m-d'));
                if($time < 0) return __error('只能修改当天的订单！');

                $res = \app\common\service\MoneyService::api($order['system_no']);//修改金额
                if($res !== true)  msg_error("系统异常，变动金额失败");

                $order['pay_status'] = 2;//已支付
            }

            if($order['pay_status'] == 2){
                $data = $this->model->notify($order['system_no']);
                $ok = \tool\Curl::post($data['url'],$data['data']);
                if(md5(strtolower($ok)) == md5('ok')){
                    $this->model->save(['id'=>$data['order']['id'],'notice'=>2,'repair'=>1],['id'=>$data['order']['id']]);

                    return __success('手动回调单号-'.$order['system_no']." 成功！\n  商户返回： ".$ok);
                }else{
                    $this->model->save(['id'=>$data['order']['id'],'notice'=>3,'repair'=>1],['id'=>$data['order']['id']]);
                    $str = '手动回调单号-'.$order['system_no'].' 失败！ 商户返回： ';
                    $str.=  "\n";
                    $str.=  "\n";
                    $str.=  "<code>";
                    $str.=  "\n";
                    if(!is_string($ok)){
                        $ok.= json_encode($ok);
                    }
                    $str.=  $ok;
                    $str.=  "</code>";
                    return __success($str);

                }
            }
        }


        return __error('系统异常');
    }


    /**
     * 查询订单状态
     * @return void
     */
    public function query_order(){

        if ($this->request->isPost()){
            $id = $this->request->get('id/d',0);

            $order =  $this->model->quickGet($id);
            if(empty($order) || $order['pay_status'] == 1 ) return __error("订单不存在或者该订单下单失败");

            $code = \app\common\model\Channel::get_code($order['channel_id']);
            if(empty($code)) __jerror('支付服务不存在0');

            $Payment = Payment::factory($code);
            $res  = $Payment->query($order);

            if($res['code'] == 0) return json($res);

            //添加到订单查询日志
            logs($res['data'],$type = 'order/query/'.$code);

            $msg = "查询订单号：".$order['system_no']."--支付成功--";
            $msg .= "\n";
            $msg .= "\n";
            $msg .= '返回报文：';
            $msg .= "\n";
            $msg .= $res['data'];
            $msg .= "\n";
            $res['data'] = $msg;

            return json($res);
        }


        return __error('系统异常');
    }

    /**手动退单
     * @return \think\response\Json
     */
    public function back_order(){

        if ($this->request->isPost()){
            $id = $this->request->get('id/d',0);

            $order =  $this->model->quickGet($id);
            if(empty($order) || $order['pay_status'] != 2   ) return __error("订单不存在或者该订单未支付");

            //不能修改前一天的订单
            $time =   strtotime($order['create_at']) - strtotime(date('Y-m-d'));
            if($time < 0) return __error('只能修改当天的订单！');

            $res = \app\common\service\MoneyService::back($order['system_no']);//修改金额
            if($res !== true)  __error("系统异常，变动金额失败");

            return __success('操作成功');
        }


        return __error('系统异常');
    }


    /**
     * 异常IP
     */
    public function ip(){
        if ($this->request->get('type') == 'ajax'){
            $redis = (new StringModel())->instance();
            $redis->select(2);

            $ip = $redis->keys('IP_*');
            $data = [];
            foreach ($ip as $k => $v){
                $data[$k] = json_decode($redis->get($v),true);
            }

            $list = [
                'code'  => 0,
                'msg'   => '查询成功',
                'count' => 100,
                'info'  => $data,
                'data'  => $data,
            ];
            return json($list);
        }

        //基础数据
        $basic_data = [
            'title'  => '异常IP列表',
        ];

        return $this->fetch('', $basic_data);
    }
    public function add_ip(){
        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '添加IP',
            ];
            $this->assign($basic_data);

            return $this->fetch('');
        } else {
            $post = $this->request->post();



            $data['id'] = 'IP_'.$post['uid'].strtr($post['ip'], '.', '_');
            $data['ip'] = $post['ip'];
            $data['uid'] = $post['uid'];

            switch ($post['auth_id']){
                case 1:
                    $time = 30*60;
                    break;
                case 2:
                    $time = 30*60*3;
                    break;
                case 3:
                    $time = 30*60*6;
                    break;
                case 4:
                    $time = 30*60*24;
                    break;
                case 5:
                    $time = 30*60*24*3;
                    break;
                case 6:
                    $time = 30*60*24*7;
                    break;
            }

            $data['over_at'] = date('Y-m-d H:i:s', time() + $time);

            $redis = (new StringModel())->instance();
            $redis->select(2);
            $redis->set($data['id'], json_encode($data));
            $redis->expire($data['id'],$time);
            return __success('添加成功');
        }

    }

    public function del_ip(){
        $get = $this->request->only('id');
        if(empty($get['id'])) __error('无数据');

        $redis = (new StringModel())->instance();
        $redis->select(2);
        $redis->del($get['id']);

        return __success('删除成功');
    }


    /**
     * 异常商户
     */
    public function merch(){
        if ($this->request->get('type') == 'ajax'){
            $redis = (new StringModel())->instance();
            $redis->select(2);

            $ip = $redis->keys('merch_*');
            $data = [];
            foreach ($ip as $k => $v){
                $data[$k] = json_decode($redis->get($v),true);
            }

            $list = [
                'code'  => 0,
                'msg'   => '查询成功',
                'count' => 100,
                'info'  => $data,
                'data'  => $data,
            ];
            return json($list);
        }

        //基础数据
        $basic_data = [
            'title'  => '异常商户列表',
        ];

        return $this->fetch('', $basic_data);
    }
    public function add_merch(){

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



        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'channel'  => $Channel_data,
                'title' => '添加异常商户',
            ];
            $this->assign($basic_data);

            return $this->fetch('');
        } else {
            $post = $this->request->post();

            $data['id'] = 'merch_'.$post['channel_id'].$post['uid'];
            $data['channel_id'] = $post['channel_id'];
            $data['uid'] = $post['uid'];
            $data['title'] = $Channel_data[$post['channel_id']];

            switch ($post['auth_id']){
                case 1:
                    $time = 30*60;
                    break;
                case 2:
                    $time = 30*60*3;
                    break;
                case 3:
                    $time = 30*60*6;
                    break;
                case 4:
                    $time = 30*60*24;
                    break;
                case 5:
                    $time = 30*60*24*3;
                    break;
                case 6:
                    $time = 30*60*24*7;
                    break;
            }

            $data['over_at'] = date('Y-m-d H:i:s', time() + $time);

            $redis = (new StringModel())->instance();
            $redis->select(2);
            $redis->set($data['id'], json_encode($data));
            $redis->expire($data['id'],$time);
            return __success('添加成功');
        }

    }







}