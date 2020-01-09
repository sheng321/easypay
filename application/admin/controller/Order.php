<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\model\PayProduct;

/**
 * Undocumented 订单记录
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
        $this->model = model('app\common\model\Order');
    }
    /**
     * Undocumented 订单列表
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
            'title'  => '支付产品列表',
            'data'   => '',
            'order' => config('order.'),
            'product' =>  PayProduct::idArr()//支付产品
        ];

        return $this->fetch('', $basic_data);
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

            $OrderDispose = model('app\common\model\OrderDispose');

            $Dispose = $OrderDispose->quickGet(['pid'=>$id]);

            $this->model->startTrans();

            $save = $this->model->save(['pay_status'=>0,'id'=>$id,'over_time'=>time()+3600],['id'=>$id]);//开启订单
            if(empty($Dispose)){
                $save1 =  $OrderDispose->create([
                   'pid'=>$id,
                    'systen_no'=>$order,
                    'record'=>$this->user['username'].'-开启',
                ]);
            }else{
                $save1 = $OrderDispose->save([
                    'id'=>$Dispose['id'],
                    'record'=>$Dispose['record'].'|'.$this->user['username'].'-开启',
                ],['id'=>$Dispose['id']]);
            }

            if (!$save || !$save1) {
                $this->model->rollback();
                return __error('数据有误，请稍后再试！');
            }
            $this->model->commit();
            return __success('开启成功！');


        }
    }


    /**
     *  补发通知
     *
     * @return void
     */
    public function replacement(){

        $id = $this->request->get('id/d',0);
        $order =  $this->model->quickGet($id);
        if(empty($order) || $order['notice'] == 2 ) msg_error("订单不存在");

        $res = \app\common\service\MoneyService::api($order);//修改金额

        if($res === true){
            $data = $this->model->notify($order);

            $ok = \tool\Curl::post($data['url'],$data['data']);
            if(md5(strtolower($ok)) == md5('ok')){
                (new Order)->save(['id'=>$data['order']['id'],'notice'=>2],['id'=>$data['order']['id']]);

                $this->model->save(['id'=>$data['order']['id'],'notice'=>2],['id'=>$data['order']['id']]);
            }else{
                $this->model->save(['id'=>$data['order']['id'],'notice'=>3],['id'=>$data['order']['id']]);
                if(is_string($ok)){
                    halt(htmlspecialchars($ok));
                }else{
                    halt(htmlspecialchars(json_encode($ok)));
                }
            }

        }





        $data = [];
        $data['sign'] = 'xxxxxxxxxxx';
        $result = $this->model->orderSend($data,$info->id);
        return __success('发送成功,异步返回：'.$result);
    }









    
}