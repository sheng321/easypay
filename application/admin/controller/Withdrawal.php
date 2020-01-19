<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use think\Db;

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
        $this->model = model('app\common\model\Withdrawal');
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

            //权重 和 并发 编辑
            if($post['field'] == 'weight' || $post['field'] == 'concurrent'){

                $ChannelProduct = model('app\common\model\ChannelProduct');

                $id = $ChannelProduct->where(['channel_id'=>$post['id']])->value('id');
                if(empty($id)) return '数据错误，请重试';

                $data2['id'] = $id;
                $data2['field'] = $post['field'];
                $data2['value'] = $post['value'];

                //保存数据,返回结果
                return $ChannelProduct->editField($data2);

            }else{
                //保存数据,返回结果
                return model('app\common\model\Channel')->editField($post);
            }

        }


    }


    /**
     * 结算设置
     * @return \think\response\Json|\think\response\View
     */
    public function config(){

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

       $res =  $this->model->save([
             'id'=>$pid,
            'channel_id'=>$Channel['id'],
            'fee'=>$Channel['fee'],
            'record'=>empty($order['record'])?$this->user['username']."选择下发通道:".$Channel['title']:$order['record']."|".$this->user['username']."选择下发通道:".$Channel['title'],
           'verson'=>$verson,
        ],['id'=>$pid]);

       if(!$res) return __error("操作失败");

       return __success('操作成功');
    }




    /**
     *  锁定/解锁 出款/退款
     */
    public function with_save(){
        $data = $this->request->param();
        $info = $this->model->where("id",$data['id'])->find();
        if(!$info) return __error("数据不存在");
        return $this->model->saveWith($data,$info,$this->user->username);
    }











    
}