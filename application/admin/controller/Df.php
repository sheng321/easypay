<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\model\ChannelDf;
use app\common\model\Umoney;
use app\withdrawal\service\Payment;
use think\Db;

/**
 *  代付
 */
class Df extends AdminController {

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
        $this->model = model('app\common\model\Df');
    }

    /**
     *  代付列表
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
                'title' => '代付订单列表',
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

    /**处理中代付订单
     * @return mixed|\think\response\Json
     */
    public function dispose(){
        if (!$this->request->isPost()) {
            //ajax访问
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['status'] = 2;//处理中
                return json($this->model->alist($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title' => '处理中代付订单列表',
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
     * 选择出款代付通道
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
                return json(model('app\common\model\ChannelDf')->wList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '出款代付通道列表',
            ];

            return $this->fetch('', $basic_data);
        } else {

            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_field');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            return model('app\common\model\ChannelDf')->editField($post);
        }
    }





    /**
     * 代付设置
     * @return \think\response\Json|\think\response\View
     */
    public function config(){
        $this->assign(['data'  => config('custom.df')]);
        if ($this->request->isPost()) {
            $post= $this->request->post();
            setconfig('custom',$post);//配置文件只用单引号
            $this->assign(['data'  => $post]);
        }

        //基础数据
        $basic_data = [
            'title' => '代付设置',

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
            $validate = $this->validate($post, 'app\common\validate\Withdrawal.status_df');
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


            if(empty($order['channel_id'])) return __error('请先选择出款通道！');

             $channel_money = Umoney::quickGet(['uid' => 0, 'df_id' => $order['channel_id']]); //通道金额
            if(empty($channel_money)) __error('代付通道金额数据异常!');

            //处理中
            if ($post['status'] == 2) {
                $channel = ChannelDf::quickGet($order['channel_id']);
                if(empty($channel) || $channel['status'] != 1) __error('代付通道异常或者未开启!');
                $Payment =  Payment::factory($channel['code']);
                //先更新系统数据，再提交数据到上游

                //冻结通道金额
                $change['change'] = $order['channel_amount'] ;//变动金额
                $change['relate'] = $order['system_no'];//关联订单号
                $change['type'] = 5;//通道冻结金额类型

                $res = Umoney::dispose($channel_money, $change); //处理 通道金额
                if (true !== $res['msg'] && $res['msg'] != '申请金额冻结大于可用金额') return __error('代付通道:' . $res['msg']);

                $Umoney_data = $res['data'];
                $UmoneyLog_data = $res['change'];

                //使用事物保存数据
                $this->model->startTrans();
                $save1 = $this->model->save($post, ['id' => $post['id']]);

                $save = model('app\common\model\Umoney')->isUpdate(true)->saveAll($Umoney_data);
                $add = model('app\common\model\UmoneyLog')->isUpdate(false)->saveAll($UmoneyLog_data);

                if (!$save1 || !$save || !$add) {
                    $this->model->rollback();
                    return __error('数据有误，请稍后再试!');
                }
                //这里提交代付申请
                $order['bank'] = json_decode($order['bank'],true);
                $result = $Payment->pay($order);
                if(empty($result)|| !is_array($result)){
                    $this->model->rollback();
                    return __error('代付通道异常，请稍后再试!');
                }
                //成功
                if($result['code'] == 1){
                    //更新数据
                    if(!empty($result['data']) && is_array($result['data'])){
                        $arr = [];
                        foreach ($result['data'] as $k => $v){
                            if($k == 'actual_amount') $arr[$k] = $v;//实际到账
                            if($k == 'transaction_no') $arr[$k] = $v;//上游单号
                            if($k == 'remark') $arr[$k] = $v;//备注
                        }
                        if(!empty($arr)){
                            $arr['id'] = $post['id'];
                            $this->model->save($arr,['id'=>$post['id']]);
                        }
                    }

                    $this->model->commit();

                    //添加异步查询订单状态
                    \think\Queue::later(60,'app\\common\\job\\Df', $order['id'], 'df');//一分钟
                    // \think\Queue::push('app\\common\\job\\Df', $order['id'], 'df');//一分钟);
                    return __success('操作成功！');
                }else{
                    $this->model->rollback();
                    return __error('申请代付失败，请检查上游订单状，上游返回：'.$result['msg']);
                }
            }


            //处理完成
            if ($post['status'] == 3){
                if ($order['status'] != 2) return __error('请先选择处理中状态！');

                $Umoney = Umoney::quickGet(['uid' =>  $order['mch_id'], 'channel_id' =>0, 'df_id' =>0]); //会员金额
                $change['change'] = $order['amount'];//会员变动金额
                $change['relate'] = $order['system_no'];//关联订单号
                $change['type'] = 1;//成功解冻扣除

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
                $change['change'] = $order['amount'];//会员变动金额
                $change['relate'] = $order['system_no'];//关联订单号
                $change['type'] = 16;//代付失败解冻退款

                $res1 = Umoney::dispose($Umoney, $change); //会员处理
                if (true !== $res1['msg'] ) return __error('会员:' . $res1['msg']);

                $Umoney_data = $res1['data'];
                $UmoneyLog_data = $res1['change'];


                if($order['status'] == 2){
                    $change['change'] = $order['channel_amount'];//通道变动金额
                    $change['type'] = 6;//通道失败解冻退款
                    $res2 = Umoney::dispose($channel_money, $change); //通道处理
                    if (true !== $res2['msg']) return __error('通道:' . $res2['msg']);

                    $Umoney_data = array_merge($Umoney_data,$res2['data']);
                    $UmoneyLog_data = array_merge($UmoneyLog_data,$res2['change']);
                }

            }

            if ($post['status'] == 4||$post['status'] == 3){
                //使用事物保存数据
                $this->model->startTrans();
                $save1 = $this->model->save($post, ['id' => $post['id']]);

                $save = model('app\common\model\Umoney')->isUpdate(true)->saveAll($Umoney_data);
                $add = model('app\common\model\UmoneyLog')->isUpdate(false)->saveAll($UmoneyLog_data);

                if (!$save1 || !$save || !$add) {
                    $this->model->rollback();
                    return __error('数据有误，请稍后再试!');
                }
                $this->model->commit();
                return __success('操作成功！');
            }
        }
    }


    /**
     * 保存通道
     */
    public function confirm(){
        $channel_id = $this->request->param('id', 0);
        if(empty($channel_id[0]) || count($channel_id) > 1) return __error('请选择一条代付通道！！');

        $Channel = model('app\common\model\ChannelDf')->quickGet($channel_id[0]);
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
            'record'=>empty($order['record'])?$this->user['username']."选择下发代付通道:".$Channel['title']:$order['record']."|".$this->user['username']."选择下发代付通道:".$Channel['title'],
            'verson'=>$verson, //防止多人操作
        ],['id'=>$pid]);

        if(!$res) return __error("操作失败");

        return __success('操作成功');
    }


    /**代付通道列表
     * @return mixed|\think\response\Json
     */
    public function df()
    {
        $this->model = model('app\common\model\ChannelDf');

        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                return json($this->model->cList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '代付通道列表',
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
            $result = $this->model->editField($post);

            return $result;

        }
    }

    /**
     * 会员金额
     * @return mixed
     */
    public function money(){
        $id = $this->request->get('id/d',0);
        $Umoney =  model('app\common\model\Umoney');
        $user =$Umoney->quickGet(['df_id'=>$id,'uid'=>0]);
        if(empty($user)) return msg_error('数据错误，请重试！');

        if (!$this->request->isPost()){
            //基础数据
            $basic_data = [
                'status' => [9=>'人工冻结',10=>'人工解冻',3=>'添加',4=>'扣除'],
                'user'  => $user,//用户金额
            ];
            return $this->fetch('', $basic_data);
        } else {
            $money = $this->request->only('remark,change,type,__token__','post');

            //验证数据
            $validate = $this->validate($money, 'app\common\validate\Money.edit');
            if (true !== $validate) return __error($validate);

            //处理金额
            $res =  $Umoney->dispose($user,$money);
            if (true !== $res['msg']) return __error($res['msg']);

            unset($money['__token__']);

            //使用事物保存数据
            $Umoney->startTrans();

            $save = $Umoney->saveAll($res['data']);
            $add = model('app\common\model\UmoneyLog')->saveAll($res['change']);

            if (!$save || !$add) {
                $Umoney->rollback();
                $msg = '数据有误，请稍后再试！';
                __log($id.$res['log'].'失败');
                return __error($msg);
            }
            $Umoney->commit();

            __log($res['log'].'成功');
            empty($msg) && $msg = '操作成功';
            return __success($msg);
        }
    }



    /**
     * 添加代付通道
     * @return mixed|\think\response\Json
     */
    public function add_df(){
        $this->model = model('app\common\model\ChannelDf');
        if (!$this->request->isPost()) {

            //代付通道列表
            $Channel = \app\common\model\Channel::where(['pid'=>0,'type'=>0])->cache('Channel_0',3)->column('id,title','id');

            //基础数据
            $basic_data = [
                'title' => '添加代付支付通道',
                'data'  => [],
                'channel' => $Channel
            ];
            $this->assign($basic_data);

            return $this->fetch('form');
        } else {
            $post = $this->request->post();

            if(!empty($post['secretkey']))  $post['secretkey'] = (new Channel())->check_secretkey($post['secretkey']);

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Channel.df');
            if (true !== $validate) return __error($validate);
            unset($post['__token__']);

            $money = ['uid'=>0];
            if(!empty($post['channel_id'])){
                $channel_money = Umoney::quickGet(['uid'=>0,'channel_id'=>$post['channel_id']]);
                if(empty($channel_money)) __error('绑定支付通道的金额账户不存在');
                $money['id'] = $channel_money['id'];
            }else{
                $post['channel_id'] = 0;
            }


            //保存数据,返回结果
            //使用事物保存数据
            $this->model->startTrans();
            $channel = $this->model->create($post);//创建通道
            //创建代付通道金额账户
            $money['df_id'] = $channel['id'];
            if(empty($money['id'])){
                $Umoney = model('app\common\model\Umoney')->save($money);
            }else{
                $Umoney = model('app\common\model\Umoney')->save($money,['id'=>$money['id']]);
            }

            if (!$channel || !$Umoney ) {
                $this->model->rollback();
                empty($msg) && $msg = '数据有误，请稍后再试！!';
                return __error($msg);
            }
            $this->model->commit();
            empty($msg) && $msg = '添加成功!';
            return __success($msg);

        }
    }

    //置顶
    public function top() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Channel.sort');
        if (true !== $validate) return __error($validate);

        $this->model = model('app\common\model\ChannelDf');
        //判断菜单状态
        $get['sort'] == 2 && $msg = '置顶成功';
        $get['sort'] == 0 && $msg = '置后成功';

        //执行更新操作操作
        $update =  $this->model->__edit(['sort' => $get['sort'],'id' => $get['id']],$msg);

        return $update;
    }



    public function edit_df(){
        $this->model = model('app\common\model\ChannelDf');
        if (!$this->request->isPost()) {

          $data = $this->model->quickGet($this->request->get('id/d',0));
            if (empty($data)) return msg_error('暂无数据，请重新刷新页面！');


            //支付通道列表
            $Channel = \app\common\model\Channel::where(['pid'=>0,'type'=>0])->cache('Channel_0',3)->column('id,title','id');


            if(!empty($data['secretkey'])){
                $data1 = json_decode($data['secretkey']);
                $str = '';
                foreach ($data1 as $k => $v){
                    $str.= $k.'|'.$v."\n";
                }

                $data['secretkey'] = $str;
            }


            //基础数据
            $basic_data = [
                'title' => '添加代付支付通道',
                'data'  => $data,
                'channel' => $Channel
            ];
            $this->assign($basic_data);

            return $this->fetch('form');
        } else {
            $post = $this->request->post();

            if(!empty($post['secretkey']))  $post['secretkey'] = (new Channel())->check_secretkey($post['secretkey']);

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Channel.df');

            if (true !== $validate) return __error($validate);
            unset($post['__token__']);
            unset($post['channel_id']);//只有创建时才能绑定支付通道金额账户

            //保存数据,返回结果
           return $this->model->__edit($post);
        }
    }

    //查询通道余额
    public function search_df(){
        set_time_limit(90);

        $this->model =  model('app\common\model\ChannelDf');
        //查询所有开启的代付通道
       $code = $this->model->where(['status'=>1])->column('id,code,title','code');

        $str = '';
        $update = array();
       foreach ($code as $k => $v){
           $Payment = Payment::factory($k);
           $data  = $Payment->balance();
         if(empty($data)){
             $str .= $v['title'] . " DO代付更新失败\r\n\r\n<br/>";
         }else{
             $str .= $v['title'] . " DO代付更新成功\r\n\r\n<br/>";
             if(!empty($data['title']) && $v['title'] != $v['title'].'-'.$data['title'] ) $update[$k]['title'] = $v['title'].'-'.$data['title'];
             $update[$k]['id'] = $v['id'];
             $update[$k]['balance'] = $data['balance'];
             $update[$k]['total_balance'] = $data['total_balance'];
         }
       }
       if(!empty($update))   $this->model->saveAll($update);
        $str .=  " 更新完成！！！\r\n\r\n";
       return $str;
    }

    /**
     * 更改代付状态
     * @return \think\response\Json
     */
    public function status_df() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Channel.status');
        if (true !== $validate) return __error($validate);

        $this->model =  model('app\common\model\ChannelDf');

        //判断菜单状态
        $status = $this->model->where('id', $get['id'])->value('status');
        if($status == 3)  return __error('该通道已删除');
        $status == 1 ? list($msg, $status) = ['禁用成功', $status = 0] : list($msg, $status) = ['启用成功', $status = 1];

        //执行更新操作操作
        $update =  $this->model->__edit(['status' => $status,'id' => $get['id']],$msg);

        return $update;
    }

    public function inner() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Channel.status');
        if (true !== $validate) return __error($validate);

        $this->model =  model('app\common\model\ChannelDf');

        //判断菜单状态
        $status = $this->model->where('id', $get['id'])->value('inner');
        $status == 1 ? list($msg, $status) = ['内扣成功', $status = 0] : list($msg, $status) = ['外扣成功', $status = 1];

        //执行更新操作操作
        $update =  $this->model->__edit(['inner' => $status,'id' => $get['id']],$msg);

        return $update;
    }


    public function visit() {
        $get = $this->request->get();
        $this->model =  model('app\common\model\ChannelDf');

        $data = ['visit'=>(int)$get['value'],'id' => (int)$get['id']];
        if(!empty($get['verson'])) $data['verson'] = (int)$get['verson'];//锁

        //执行更新操作操作
        $update =  $this->model->__edit($data);

        return $update;
    }


    /**
     * 删除代付通道
     * @return \think\response\Json
     * @throws \Exception
     */
    public function del_df() {
        $get = $this->request->get();
        if(empty($get['id'])) __error('数据异常！');
        $get['status'] = 3;
        return  model('app\common\model\ChannelDf')->__edit($get,'删除代付通道成功');
    }


    /**
     * 跟踪订单状态
     * @return void
     */
    public function query_order(){

        if ($this->request->isPost()){
            $id = $this->request->get('id/d',0);
            $order =  $this->model->quickGet($id);
            if(empty($order) || empty($order['channel_id'])) return __error("订单不存在或者未选择出款通道");

            $ChannelDf = \app\common\model\ChannelDf::quickGet($order['channel_id']);
            if(empty($ChannelDf) || empty($ChannelDf['code'])) __jerror('代付通道异常');

            $Payment = Payment::factory($ChannelDf['code']);
            $res  = $Payment->query($order);

            if($res['code'] == 0) return json($res);

            $msg = '跟踪订单号：'.$order['system_no'];
            $msg .= "\n";
            $msg .= "状态：";

            if(empty($res['data']['status'])) $res['data']['status'] = 2;

           switch ($res['data']['status']){
               case 4://失败
                   $msg .= "<span style='color: red' >失败</span>";
                   break;
               case 3://成功
                   $msg .= "<span style='color: green' >成功</span>";
                   break;
               default;
                   $msg .= "处理中";
                   break;
           }
            $res['msg'] = $msg;
            return json($res);
        }


        return __error('系统异常');
    }

    /**
     * 批量处理代付订单
     * @return void
     */
    public function batch_process()
    {
        if (!$this->request->isPost()) {

            $id = $this->request->get('id', []);
            $num = count($id);
            $money = $this->model->where([['id', 'in', $id]])->sum('amount');

            if($num > 10) return msg_error('单数不能超过10笔！');


            $pid = ''; //数组拼接url
            foreach ($id as $k => $v) {
                $pid .= '&pid[]=' . $v;
            }

            //基础数据
            $basic_data = [
                'title' => '出款代付通道列表',
                'num' => $num,
                'money' => $money,
                'pid' => $pid,
            ];

            return $this->fetch('', $basic_data);
        }

    }

        /**
         * 批量保存通道并且处理
         */
        public function confirm1(){

            $channel_id = $this->request->param('id/d', 0);
            if(empty($channel_id)) return msg_error('请选择一条代付通道！！');

            $Channel = model('app\common\model\ChannelDf')->quickGet($channel_id);
            if(empty($Channel) || $Channel['status'] != 1 ) return msg_error('通道数据异常');


            $pid = $this->request->param('pid',[]);
            if(empty($pid) || !is_array($pid)) return __error('未选择代付订单！！');
            $num = count($pid);
            if($num > 10) return msg_error('单数不能超过10笔！');

            ignore_user_abort(true);    //关掉浏览器，PHP脚本也可以继续执行.
            ini_set('max_execution_time','180');

            foreach ($pid as $k => $v){
                $order =  $this->model->quickGet($v);
                if(empty($order)) continue;
                if($order['status'] != 1){
                    echo  '订单号'.$order['system_no']."不是未处理状态,失败\n";
                    continue;
                }
                if($order['lock_id'] != 0){
                    echo  '订单号'.$order['system_no']."已被锁定，失败\n";
                    continue;
                }
                //内扣
                if($Channel['inner'] == 0) $channel_amount = $order['amount'] - $order['fee'] + $Channel['fee'];
                //外扣
                if($Channel['inner'] == 1) $channel_amount = $order['amount'] - $order['fee'];

                if( $channel_amount < $Channel['min_pay']  || $channel_amount > $Channel['max_pay']){
                    echo  '订单号'.$order['system_no']."申请通道金额不在通道出款范围内！，失败\n";
                    continue;
                }

                //先更新系统数据，再提交数据到上游
                $channel_money = Umoney::quickGet(['uid' => 0, 'df_id' => $Channel['id']]); //通道金额
                if(empty($channel_money)){
                    echo '代付通道金额数据异常!';
                    echo "结束运行\n";
                    break;
                }

                //冻结通道金额
                $change['change'] = $channel_amount ;//变动金额
                $change['relate'] = $order['system_no'];//关联订单号
                $change['type'] = 5;//通道冻结金额类型
                $res = Umoney::dispose($channel_money, $change); //处理 通道金额
                if (true !== $res['msg'] && $res['msg'] != '申请金额冻结大于可用金额'){
                    echo '代付通道:' . $res['msg'];
                    echo "结束运行1\n";
                    break;
                }

               $data['channel']['code']  = $Channel['code'];
               $data['Umoney']  = $res['data'];
               $data['UmoneyLog'] = $res['change'];

               $data['order'] = [
                    'id'=>$v,
                    'status'=>2,
                    'channel_id'=>$Channel['id'],
                    'channel_fee'=>$Channel['fee'],
                    'channel_amount'=> $channel_amount,
                    'lock_id'=>$this->user['id'],
                    'record'=>empty($order['record'])?$this->user['username']."选择下发代付通道:".$Channel['title']:$order['record']."|".$this->user['username']."选择下发代付通道:".$Channel['title']."|更改状态处理中",
                    'verson'=>$order['verson']+1, //防止多人操作
                ];

                //这个地方提交批量处理任务
                $res =  \think\Queue::push('app\\common\\job\\Dfprocess', $data, 'dfprocess');
                unset($data);
                unset($change);
                if( $res === false ){
                    echo  '订单号'.$order['system_no']."提交任务失败，请稍后重试~\n";
                    unset($order);
                    continue;
                }
                echo  '订单号'.$order['system_no']."提交任务成功，请稍后刷新查看订单状态~\n";
                unset($order);
                continue;
            }

            echo "结束运行\n";
            exit();
        }



       public function confirm2(){

        $channel_id = $this->request->param('id/d', 0);
        if(empty($channel_id)) return msg_error('请选择一条代付通道！！');

        $Channel = model('app\common\model\ChannelDf')->quickGet($channel_id);
        if(empty($Channel) || $Channel['status'] != 1 ) return msg_error('通道数据异常');


        $pid = $this->request->param('pid',[]);
        if(empty($pid) || !is_array($pid)) return __error('未选择代付订单！！');
        $num = count($pid);
        if($num > 10) return msg_error('单数不能超过10笔！');

        ignore_user_abort(true);    //关掉浏览器，PHP脚本也可以继续执行.
        ini_set('max_execution_time','180');

        $Payment =  Payment::factory($Channel['code']);
        //使用事物保存数据
        $this->model->startTrans();
        foreach ($pid as $k => $v){
            $order =  $this->model->quickGet($v);
            if(empty($order)) continue;
            if($order['status'] != 1){
                echo  '订单号'.$order['system_no']."不是未处理状态,失败\n";
                continue;
            }
            if($order['lock_id'] != 0){
                echo  '订单号'.$order['system_no']."已被锁定，失败\n";
                continue;
            }
            //内扣
            if($Channel['inner'] == 0) $channel_amount = $order['amount'] - $order['fee'] + $Channel['fee'];
            //外扣
            if($Channel['inner'] == 1) $channel_amount = $order['amount'] - $order['fee'];

            if( $channel_amount < $Channel['min_pay']  || $channel_amount > $Channel['max_pay']){
                echo  '订单号'.$order['system_no']."申请通道金额不在通道出款范围内！，失败\n";
                continue;
            }


            //先更新系统数据，再提交数据到上游
            $channel_money = Umoney::quickGet(['uid' => 0, 'df_id' => $Channel['id']]); //通道金额
            if(empty($channel_money)){
                echo '代付通道金额数据异常!';
                echo "结束运行\n";
                break;
            }

            //冻结通道金额
            $change['change'] = $channel_amount ;//变动金额
            $change['relate'] = $order['system_no'];//关联订单号
            $change['type'] = 5;//通道冻结金额类型

            $res = Umoney::dispose($channel_money, $change); //处理 通道金额
            if (true !== $res['msg'] && $res['msg'] != '申请金额冻结大于可用金额'){
                echo '代付通道:' . $res['msg'];
                echo "结束运行1\n";
                break;
            }
            $Umoney_data = $res['data'];
            $UmoneyLog_data = $res['change'];

            //选择通道并且处理中
            $save1 =  $this->model->save([
                'id'=>$v,
                'status'=>2,
                'channel_id'=>$Channel['id'],
                'channel_fee'=>$Channel['fee'],
                'channel_amount'=> $channel_amount,
                'lock_id'=>$this->user['id'],
                'record'=>empty($order['record'])?$this->user['username']."选择下发代付通道:".$Channel['title']:$order['record']."|".$this->user['username']."选择下发代付通道:".$Channel['title']."|更改状态处理中",
                'verson'=>$order['verson']+1, //防止多人操作
            ],['id'=>$v]);


            $save = model('app\common\model\Umoney')->isUpdate(true)->saveAll($Umoney_data);
            $add = model('app\common\model\UmoneyLog')->isUpdate(false)->saveAll($UmoneyLog_data);

            if ( !$save1 || !$save || !$add ) {
                $this->model->rollback();
                echo  '订单号'.$order['system_no']."更新数据,失败\n";
                continue;
            }

            //这里提交代付申请
            $order['channel_amount'] = $channel_amount;
            $order['bank'] = json_decode($order['bank'],true);
            $result = $Payment->pay($order);
            if(empty($result)|| !is_array($result)){
                $this->model->rollback();

                echo '代付通道异常，请稍后再试!';
                echo "结束运行3\n";
                break;
            }

            //成功
            if($result['code'] == 1){
                //更新数据
                if(!empty($result['data']) && is_array($result['data'])){
                    $arr = [];
                    foreach ($result['data'] as $k1 => $v1){
                        if($k == 'actual_amount') $arr[$k1] = $v1;//实际到账
                        if($k == 'transaction_no') $arr[$k1] = $v1;//上游单号
                        if($k == 'remark') $arr[$k1] = $v1;//备注
                    }
                    if(!empty($arr)){
                        $arr['id'] = $v;
                        $this->model->save($arr,['id'=>$v]);
                    }
                }

                $this->model->commit();
                //添加异步查询订单状态
                \think\Queue::later(60,'app\\common\\job\\Df', $order['id'], 'df');//一分钟
                echo  '订单号 '.$order['system_no']." 处理成功\n";
                unset($order);
                unset($v);
                continue;

            }else{
                $this->model->rollback();
                echo  '订单号'.$order['system_no'].'申请代付失败，请检查上游订单状，上游返回：'.$result['msg']."，失败\n";
                unset($order);
                unset($v);
                continue;
            }
        }

        echo "结束运行4\n";
        exit();
    }


}