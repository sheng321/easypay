<?php
namespace app\agent\controller;

use app\common\controller\AgentController;

class Index extends AgentController
{
    public function index()
    {
        $basic_data = [
            'title'=> '主页',
        ];

        return $this->fetch('', $basic_data);
    }

    /**
     * 首页欢迎界面
     * @return mixed
     */
    public function welcome() {
        $data =  \app\common\service\CountService::agent_today_account();

        if(!empty($data['data1'][$this->user['uid']])) $data['data1'] = $data['data1'][$this->user['uid']];
        if(empty($data['data1'][$this->user['uid']]['total_orders'])){
            $data['data1'][$this->user['uid']]['wait'] = 0;
        }else{
            $data['data1'][$this->user['uid']]['wait'] = $data['data1'][$this->user['uid']]['total_orders'] - $data['data1'][$this->user['uid']]['total_paid'];
        }

        if(!empty($data['data2'][$this->user['uid']])) $data['data2'] = $data['data2'][$this->user['uid']];
        if(empty($data['data2'][$this->user['uid']]['total_orders'])){
            $data['data2'][$this->user['uid']]['wait'] = 0;
        }else{
            $data['data2'][$this->user['uid']]['wait'] = $data['data2'][$this->user['uid']]['total_orders'] - $data['data2'][$this->user['uid']]['total_paid'];
        }


        //代理公告
        $Message =  model('app\common\model\Message')->where([
            ['title','<>','首页弹窗显示'],
            ['type','in',[0,2]]
        ])->order('id desc')->cache('Message_2',30)->select()->toArray();

        $basic_data = [
            'title'=> '主页',
            'message'=> $Message,
            'info'=> $data,//今天跑量详情
            'money'=> ['balance'=>$this->user['money']['balance'],'frozen_amount'=>$this->user['money']['frozen_amount']],
        ];

        return $this->fetch('',$basic_data);
    }

}
