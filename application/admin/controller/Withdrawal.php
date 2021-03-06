<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\model\Umember;
use app\common\model\Umoney;
use app\common\model\UmoneyLog;
use think\Db;
use think\facade\Env;

/**
 *  提现记录
 */
class Withdrawal extends AdminController {
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
        $this->model = new \app\common\model\Withdrawal();
    }

    /**
     *  提现列表
     */
    public function index(){
        if (!$this->request->isPost()) {
            //ajax访问
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                return json($this->model->alist($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title' => '结算订单列表',
                'status'  =>config('custom.status'),
                'data'  => '',
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
     * 下载
     * @return void
     */
    public function export(){

        $field = [
            "mch_id",
            'system_no',
            "amount",
            "channel_amount",
            "bank",

            "status",

            "channel_id",
            "fee",
            "channel_fee",
            "transaction_no",

            "remark",
            "remark1",
            "actual_amount",
            "create_at",
            "update_at",
            "record",
        ];


        $title = [
            "mch_id"=>'商户号',
            'system_no'=>'订单号',
            "amount"=>'申请金额',
            "channel_amount"=>'通道申请金额',

            "card_number"=>'银行卡号',
            "account_name"=>'开户人',
            "bank_name"=>'银行名称',
            "branch_name"=>'支行',
            "status_title"=>'状态',

            "channel_title"=>'出款通道',
            "fee"=>'手续费',
            "channel_fee"=>'通道手续费',
            "transaction_no"=>'上游订单号',

            "remark"=>'备注',
            "remark1"=>'商户说明',
            "actual_amount"=>'实际到账',
            "create_at"=>'申请时间',
            "update_at"=>'更新时间',
            "record"=>'操作记录',
        ];

        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 3000);
            $search = (array)$this->request->get('search', []);
            $search['field'] = $field;
            return json($this->model->alist($page, $limit, $search));
        }

        $field[] = 'channel_title';
        $field[] = 'status_title';
        $field[] = 'card_number';
        $field[] = 'account_name';
        $field[] = 'bank_name';


        //基础数据
        $basic_data = [
            'title'  => '提现列表',
            'url'  =>request() -> url(),
            'data'   => ['field'=>json_encode($field),'title'=>json_encode($title)],
        ];

        return $this->fetch('common@export/index', $basic_data);
    }



    /**
     * 选择出款通道
     */
    public function channel()
    {
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 1000);
                $search = (array)$this->request->get('search', []);
                $search['channel_id'] = $this->request->get('channel_id/d', 0);
                return json(model('app\common\model\Channel')->wList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '出款通道列表',
            ];

            return $this->fetch('', $basic_data);
        } else {

            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_field');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            return model('app\common\model\Channel')->editField($post);
        }
    }

    /**
     * 结算设置
     * @return \think\response\Json|\think\response\View
     */
    public function config(){
        $this->assign(['data'  => config('custom.withdrawal')]);
        if ($this->request->isPost()) {
            $post= $this->request->post();
            setconfig('custom',$post);//配置文件只用单引号
            $this->assign(['data'  => $post]);
        }

        //基础数据
        $basic_data = [
            'title' => '结算设置',

        ];
        return $this->fetch('', $basic_data);
    }

    //更新状态
    public function status() {

        $status = config('custom.status');
        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '更新状态',
                'status'  => $status,
            ];
            $this->assign($basic_data);

            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only(['id', 'status', 'verson'], 'post');

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Withdrawal.status');
            if (true !== $validate) return __error($validate);

            $order = $this->model->quickGet($post['id']);
            if ($order['status'] >= $post['status']) return __error('选择状态重复或者错误！');

             //解除订单锁定
            if ($post['status'] == 9){
                unset($post['status']);
                $post['lock_id'] = 0;
                $post['record'] = empty($order['record']) ? $this->user['username'] . "解除锁定"  : $order['record'] . "|" . $this->user['username'] . "解除锁定" ;
              return $this->model->__edit($post);
            }

            $post['lock_id'] = $this->user['id'];
            $post['record'] = empty($order['record']) ? $this->user['username'] . "更新状态:" . $status[$post['status']] : $order['record'] . "|" . $this->user['username'] . "更新状态:" . $status[$post['status']];

            //如果选择了出款通道
            if ($order['channel_id'] > 0) {
                $channel_money = Umoney::quickGet(['uid' => 0, 'channel_id' => $order['channel_id']]); //通道金额
            }else{
                //没有选择通道，默认就用平台金额
                $channel_money = Umoney::quickGet(['uid' => 0, 'channel_id' => 0, 'df_id' => 0,'id'=>1]);
                 $order['channel_amount'] = $order['amount'];
            }
            if(empty($channel_money)) __error('通道金额数据异常!');


            //处理中
            if ($post['status'] == 2) {

                //冻结通道金额
                    $change['change'] = $order['channel_amount'];//变动金额
                    $change['relate'] = $order['system_no'];//关联订单号
                    $change['type'] = 5;//提现冻结金额类型

                    $res = Umoney::dispose($channel_money, $change); //处理
                    if (true !== $res['msg'] && $res['msg'] != '申请金额冻结大于可用金额') return __error('通道:' . $res['msg']);

                    $Umoney_data = $res['data'];
                    $UmoneyLog_data = $res['change'];
            }

            //处理完成
            if ($post['status'] == 3){
                if ($order['status'] != 2) return __error('请先选择处理中状态！');

                $Umoney = Umoney::quickGet(['uid' =>  $order['mch_id'], 'channel_id' =>0]); //会员金额
                $change['change'] = $order['amount'];//变动金额
                $change['relate'] = $order['system_no'];//关联订单号
                $change['type'] = 1;//成功解冻入账

                $res1 = Umoney::dispose($Umoney, $change); //会员处理
                if(true !== $res1['msg'] ) return __error('会员:' . $res1['msg']);

                $Umoney_data = $res1['data'];
                $UmoneyLog_data = $res1['change'];

                $change['change'] = $order['channel_amount'];//通道变动金额
                $res2 = Umoney::dispose($channel_money, $change); //通道处理
                if (true !== $res2['msg']) return __error('通道:' . $res2['msg']);

                $Umoney_data = array_merge($Umoney_data,$res2['data']);
                $UmoneyLog_data = array_merge($UmoneyLog_data,$res2['change']);


                $post['actual_amount'] = $order['amount'] - $order['fee'];//实际到账
            }


            //失败退款
            if ($post['status'] == 4){

                    $Umoney = Umoney::quickGet(['uid' =>  $order['mch_id'], 'channel_id' =>0]); //会员金额
                    $change['change'] = $order['amount'];//变动金额
                    $change['relate'] = $order['system_no'];//关联订单号
                    $change['type'] = 6;//会员失败解冻退款

                    $res1 = Umoney::dispose($Umoney, $change); //会员处理
                    if (true !== $res1['msg'] ) return __error('会员:' . $res1['msg']);

                    $Umoney_data = $res1['data'];
                    $UmoneyLog_data = $res1['change'];

                 if($order['status'] == 2) { //处理中 的订单

                    $change['change'] = $order['channel_amount'];//通道变动金额
                    $res2 = Umoney::dispose($channel_money, $change); //通道处理
                    if (true !== $res2['msg']) return __error('通道:' . $res2['msg']);

                    $Umoney_data = array_merge($Umoney_data,$res2['data']);
                    $UmoneyLog_data = array_merge($UmoneyLog_data,$res2['change']);

                }

            }


            //使用事物保存数据
            $this->model->startTrans();
            $post['lock_id'] = $this->user['id'];
            $save1 = $this->model->save($post, ['id' => $post['id']]);

                $save = (new Umoney())->isUpdate(true)->saveAll($Umoney_data);
                $add = (new UmoneyLog())->isUpdate(false)->saveAll($UmoneyLog_data);

            if (!$save1 || !$save || !$add) {
                $this->model->rollback();
                return __error('数据有误，请稍后再试!');
            }
            $this->model->commit();
            return __success('操作成功！');
        }
    }


    /**
     * 保存通道
     */
    public function confirm(){
        $channel = $this->request->param('id', 0);
        if(empty($channel[0])) return __error('请选择一条通道！！');

        $Channel = model('app\common\model\Channel')->quickGet($channel[0]);
        if(empty($Channel)) return __error('数据异常');

        $pid = $this->request->get('pid/d', 0);
        $verson = $this->request->get('verson/d', 0);
        $order =  $this->model->quickGet($pid);
        if(empty($order)) return __error('订单不存在!');
        if($order['status'] != 1) return __error('只有订单未处理状态，才可以选择出款通道!');

        //内扣
        if($Channel['inner'] == 0) $channel_amount = $order['amount'] - $order['fee'] + $Channel['fee'];
        //外扣
        if($Channel['inner'] == 1) $channel_amount = $order['amount'] - $order['fee'];

        if( $channel_amount < $Channel['min_pay']  || $channel_amount > $Channel['max_pay'])  return __error('申请通道金额不在通道出款范围内！');

       $res =  $this->model->save([
             'id'=>$pid,
            'channel_id'=>$Channel['id'],
            'channel_fee'=>$Channel['fee'],
           'channel_amount'=> $channel_amount,
           'lock_id'=>$this->user['id'],
            'record'=>empty($order['record'])?$this->user['username']."选择下发通道:".$Channel['title']:$order['record']."|".$this->user['username']."选择下发通道:".$Channel['title'],
           'verson'=>$verson, //防止多人操作
        ],['id'=>$pid]);

       if(!$res) return __error("操作失败");

       return __success('操作成功');
    }

    /**
     *  异常银行卡
     */
    public function bank(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 10);
            $search = (array)$this->request->get('search', []);
            $search['uid'] = 0;
            return json(model('app\common\model\Bank')->aList($page, $limit, $search));
        }

        $basic_data = [
            'title' => '异常银行卡列表',
        ];
        return $this->fetch('', $basic_data);
    }
    /**
     *  添加/编辑异常银行卡
     */
    public function saveBank(){

        $Bank =  model('app\common\model\Bank');

        if (!$this->request->isPost()){
            $basic_data = [
                'title' => '添加异常银行卡',
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only(['card_number','bank_name','branch_name','account_name','__token__'], 'post');
            $post['uid'] = 0;
            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Bank.edit');
            if (true !== $validate) return __error($validate);
            unset($post['__token__']);
            return $Bank->__add($post);
        }
    }
    /**
     *  删除异常银行卡
     */
    public function delBank(){
        $get = $this->request->only('id');

        //验证数据
        if (!is_array($get['id'])) {
            $get['uid'] = 0;
            $validate = $this->validate($get, 'app\common\validate\Bank.del');
            if (true !== $validate) return __error($validate);
        }else{
            foreach ($get['id'] as $k => $val){
                $data['id'] = $val;
                $data['uid'] = 0;
                $validate = $this->validate($data, 'app\common\validate\Bank.del');
                if (true !== $validate) unset($get['id'][$k]);
            }
        }
        if(empty($get)) return __error('数据异常');

        //执行操作
        $del = model('app\common\model\Bank')->__del($get);
        return $del;

    }

}