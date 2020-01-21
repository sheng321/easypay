<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\model\Umoney;
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
            }

            //处理中
            if ($post['status'] == 2) {

                //冻结通道金额
                if ($order['channel_id'] > 0) {

                    $change['change'] = $order['amount'];//变动金额
                    $change['relate'] = $order['system_no'];//关联订单号
                    $change['type'] = 5;//代付冻结金额类型

                    $res = Umoney::dispose($channel_money, $change); //处理
                    if (true !== $res['msg'] && $res['msg'] != '代付冻结大于可用金额') return __error('通道:' . $res['msg']);

                    $Umoney_data = $res['data'];
                    $UmoneyLog_data = $res['change'];
                }

            }

            //处理完成
            if ($post['status'] == 3){
                $Umoney = Umoney::quickGet(['uid' =>  $order['mch_id'], 'channel_id' =>0]); //会员金额
                $change['change'] = $order['amount'];//变动金额
                $change['relate'] = $order['system_no'];//关联订单号
                $change['type'] = 1;//成功解冻入账

                $res1 = Umoney::dispose($Umoney, $change); //会员处理
                if(true !== $res1['msg'] ) return __error('会员:' . $res1['msg']);

                $Umoney_data = $res1['data'];
                $UmoneyLog_data = $res1['change'];

                if ($order['channel_id'] > 0) {
                    $res2 = Umoney::dispose($channel_money, $change); //通道处理
                    if (true !== $res2['msg']) return __error('通道:' . $res2['msg']);

                    $Umoney_data = array_merge($Umoney_data,$res2['data']);
                    $UmoneyLog_data = array_merge($UmoneyLog_data,$res2['change']);
                }


            }


            //失败退款
            if ($post['status'] == 4){
                $Umoney = Umoney::quickGet(['uid' =>  $order['mch_id'], 'channel_id' =>0]); //会员金额
                $change['change'] = $order['amount'];//变动金额
                $change['relate'] = $order['system_no'];//关联订单号
                $change['type'] = 6;//失败解冻退款

                $res1 = Umoney::dispose($Umoney, $change); //会员处理
                if (true !== $res1['msg'] ) return __error('会员:' . $res1['msg']);

                $Umoney_data = $res1['data'];
                $UmoneyLog_data = $res1['change'];

                if ($order['channel_id'] > 0) {
                    $res2 = Umoney::dispose($channel_money, $change); //通道处理
                    if (true !== $res2['msg']) return __error('通道:' . $res2['msg']);

                    $Umoney_data = array_merge($Umoney_data,$res2['data']);
                    $UmoneyLog_data = array_merge($UmoneyLog_data,$res2['change']);
                }
            }


            //使用事物保存数据
            $this->model->startTrans();
            $save1 = $this->model->save($post, ['id' => $post['id']]);

            if ($order['channel_id'] > 0) {
                $save = model('app\common\model\Umoney')->isUpdate(true)->saveAll($Umoney_data);
                $add = model('app\common\model\UmoneyLog')->isUpdate(false)->saveAll($UmoneyLog_data);
            } else {
                $save = true;
                $add = true;
            }
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

        $res =  $this->model->save([
            'id'=>$pid,
            'channel_id'=>$Channel['id'],
            'channel_fee'=>$Channel['fee'],
            'lock_id'=>$this->user['id'],
            'record'=>empty($order['record'])?$this->user['username']."选择下发通道:".$Channel['title']:$order['record']."|".$this->user['username']."选择下发通道:".$Channel['title'],
            'verson'=>$verson, //防止多人操作
        ],['id'=>$pid]);

        if(!$res) return __error("操作失败");

        return __success('操作成功');
    }

}