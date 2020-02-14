<?php

namespace app\agent\controller;


use app\common\controller\AgentController;



class Agent extends AgentController {

    /**
     * Agent模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * User constructor.
     */
    public function __construct() {
        parent::__construct();

        $this->model = model('app\common\model\Umember');
    }

    /**
     * 代理列表
     * @return mixed
     */
    public  function  index(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 10);
            $search = (array)$this->request->get('search', []);
            $search['uid'] = $this->user['uid'];
            return $this->model->aList($page, $limit, $search);
        }

        $basic_data = [
            'title' => 'IP白名单列表',
            'type' => [0=>'登入',1=>'结算',2=>'代付'],
        ];
        return $this->fetch('', $basic_data);
    }


    /**商户列表
     * @return mixed
     */
    public  function  member(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 10);
            $search = (array)$this->request->get('search', []);
            $search['uid'] = $this->user['uid'];
            return $this->model->aList($page, $limit, $search);
        }

        $basic_data = [
            'title' => 'IP白名单列表',
            'type' => [0=>'登入',1=>'结算',2=>'代付'],
        ];
        return $this->fetch('', $basic_data);
    }



    /**
     * 分组管理
     * @return mixed
     */
    public  function  group(){

        $this->model = model('app\common\model\Ulevel');

        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = 1;//代理
                $search['uid'] = $this->user['uid'];
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '代理分组列表',
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
            return $this->model->editField($post);
        }
    }

    //添加代理分组
    public function add_agent() {
        $this->model = model('app\common\model\Ulevel');
        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '添加代理分组',
            ];
            $this->assign($basic_data);

            return $this->fetch('agent_form');
        } else {
            $post = $this->request->only('title,remark,type1,uid');

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Level.add_agent');
            if (true !== $validate) return __error($validate);

            $post['type'] = 1;
            $search['uid'] = $this->user['uid'];
            $res = $this->model->__add($post);
            return $res;
        }
    }

    /**
     * 代理分组费率设置
     * @return mixed|\think\response\Json
     */
    public function agent_rate() {

        $group_id = (int)$this->request->get('id',0);
        $model = model('app\common\model\SysRate');
        $this->model = model('app\common\model\Ulevel');
        if (!$this->request->isPost()) {
            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 100);
                $search = (array)$this->request->get('search', []);
                $result = model('app\common\model\ChannelGroup')->bList($page, $limit, $search);

                foreach ($result['data'] as $k => $v){
                    $result['data'][$k]['status1'] = 1;

                    $rate = \app\common\service\RateService::getGroupStatus($group_id,$v['id']);//当前费率情况

                    if(!empty($rate)){
                        $result['data'][$k]['c_rate'] = $rate['rate'];
                        $result['data'][$k]['status'] = $rate['status'];
                        if( $rate['type'] > 1) $result['data'][$k]['status1'] = $rate['status'];
                    }
                }
                return json($result);
            }

            //基础数据
            $basic_data = [
                'title'  => '系统分组费率列表',
                'data'   => '',
            ];

            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only('id,field,value');

            if($post['field'] != 'c_rate') return __error("数据错误1");//防止客户端传个其它字段

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_rate');
            if (true !== $validate) return __error($validate);

            $level = $this->model->quickGet(['id'=>$group_id,'uid'=> $this->user['uid']]);//防止客户端随便传个id过来
            if(empty($level)) return __error("数据错误2");

            if($level['uid']>0){
                $group_id1 = \app\common\model\Uprofile::where(['uid'=>$level['uid']])->value('group_id');
                $max = 0;
                if(!empty($group_id1)){
                    $GroupStatus =  \app\common\service\RateService::getGroupStatus($group_id1,$post['id']); //上级代理分组费率
                    $max = isset($GroupStatus['rate'])?$GroupStatus['rate']:0;
                }
                if($max > $post['value']) return __error('费率小于上级用户分组默认费率：'.$max);
            }


            $temp['channel_id'] = $post['id'];
            $temp['p_id'] =  0;
            $temp['group_id'] = $group_id;
            $temp['uid'] =  $level['uid'];
            $id = $model->where($temp)->value('id');
            if(!empty($id)) $temp['id'] = $id;
            $temp['type'] =  $level['type'];
            $temp['rate'] =  $post['value'];

            if(!empty($temp['id'])){
                $res = $model->__edit($temp);
            }else{
                $res = $model->__add($temp);
            }
            return $res;
        }
    }




}