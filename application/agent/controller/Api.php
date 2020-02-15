<?php
namespace app\agent\controller;

use app\common\controller\AgentController;

class Api extends AgentController
{

    /**通道费率
     * @return mixed
     */
    public function index()
    {
        if (!$this->request->isPost()) {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 100);
            $search = (array)$this->request->get('search', []);
            $result = model('app\common\model\ChannelGroup')->bList($page, $limit, $search);

            $data = [];
            foreach ($result['data'] as $k => $v){
                $result['data'][$k]['status1'] = 1;

                $rateStatus = \app\common\service\RateService::getAgentStatus($this->user['uid'],$v['id']); //当前用户的费率状态
                if(!empty($rateStatus) && $rateStatus['code'] ==1){
                    $result['data'][$k]['status1'] = $rateStatus['status'];
                    $result['data'][$k]['c_rate'] = $rateStatus['rate'];
                }

                if( $result['data'][$k]['status'] == 1 ){
                    $data[$k]['status'] = $result['data'][$k]['status1'];
                    $data[$k]['c_rate'] = $result['data'][$k]['c_rate'] * 1000 .'‰';
                    $data[$k]['title'] = $result['data'][$k]['title'];
                    $data[$k]['product'] = $result['data'][$k]['product'];
                }
            }



            //基础数据
            $basic_data = [
                'title'  => '通道分组费率',
                'data'   => $data,
            ];

            return $this->fetch('', $basic_data);
        }
    }

}
