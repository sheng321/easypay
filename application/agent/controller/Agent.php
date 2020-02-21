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
     * 代理关系表
     * @return mixed|\think\response\Json
     */
    public function relations() {
        if (!$this->request->isPost()) {

            $model = model('app\common\model\Uprofile');
            //ajax访问
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['uid'] = $this->user['uid'];
                return json($model->aList($page, $limit, $search));
            }

            $model1 = model('app\common\model\Urelations');

            $agent = $model1->where([
                ['pid','=',$this->user['uid']],
                ['level','=',1],
                ['who','=',2]
            ])->cache('agent_'.$this->user['uid'],3)->count(1);
            $member = $model1->where([
                ['pid','=',$this->user['uid']],
                ['who','=',0],
                ['level','=',1],
            ])->cache('member_'.$this->user['uid'],3)->count(1);

            $agent_member = $model1
                ->where([
                    ['pid','=',$this->user['uid']],
                    ['level','=',2],
                    ['who','=',0]
                ])->cache('agent_member_'.$this->user['uid'],3)->count();


            //基础数据
            $basic_data = [
                'title' => '代理关系表列表',
                'uid' => $this->user['uid'],
                'data'  => ['agent'=>$agent,'member'=>$member,'agent_member'=>$agent_member],
            ];

            return $this->fetch('', $basic_data);
        }
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
            $count = $this->model->where(['uid'=>$this->user['uid']])->count(1);
            if($count > 7) return __error('分组个数不能超过 8 个');

            $post = $this->request->only('title,remark,type1');
            $post['uid'] = $this->user['uid'];
            $post['title'] = $this->user['uid'].$post['title'];
            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Level.add_agent');
            if (true !== $validate) return __error($validate);

            $post['type'] = 1;
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
                $search['status'] = 1;
                $result = model('app\common\model\ChannelGroup')->bList($page, $limit, $search);

                foreach ($result['data'] as $k => $v){
                    $result['data'][$k]['status1'] = 1;

                    $rate = \app\common\service\RateService::getGroupStatus($group_id,$v['id']);//当前费率情况

                    if(!empty($rate)){
                        $result['data'][$k]['c_rate'] = $rate['rate'];
                        $result['data'][$k]['status'] = $rate['status'];
                        // 上级状态优先级高
                        if($rate['type'] > 1){
                            $result['data'][$k]['status1'] = $rate['status'];
                          if(empty($rate['status'])) $result['data'][$k]['c_rate'] = '请联系上级设置';
                        }

                        //当为分组客户端隐藏的时候 且后台没有为其设置费率，不显示
                        if($rate['type'] != 1 && $v['cli'] == 0){
                            unset($result['data'][$k]);
                            continue;
                        }
                    }else{
                        //当为分组客户端隐藏的时候 且后台没有为其设置费率，不显示
                        if( $v['cli'] == 0){
                            unset($result['data'][$k]);
                            continue;
                        }
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


    /**
     * 商户分组费率设置
     * @return mixed|\think\response\Json
     */
    public function rate() {
        $group_id = $this->request->get('id/d',0);
        $this->model = model('app\common\model\Ulevel');
        if (!$this->request->isPost()) {
            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 100);
                $search = (array)$this->request->get('search', []);
                $result = model('app\common\model\PayProduct')->aList($page, $limit, $search);


                foreach ($result['data'] as $k => $v){
                    $result['data'][$k]['status1'] = 1;

                    $rate = \app\common\service\RateService::getGroupStatus($group_id,$v['id']);

                    if(!empty($rate)){
                        $result['data'][$k]['p_rate'] = $rate['rate'];
                        $result['data'][$k]['status'] = $rate['status'];
                        if( $rate['type'] > 1) $result['data'][$k]['status1'] = $rate['status'];
                    }
                    if($result['data'][$k]['status1'] == 0) $result['data'][$k]['p_rate'] = '未设置';
                }

                return json($result);
            }

            //基础数据
            $basic_data = [
                'title'  => '商户分组费率列表',
                'data'   => '',
                'status' => [['id' => 1, 'title' => '启用'], ['id' => 0, 'title' => '禁用']],
            ];

            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only('id,field,value');
            if($post['field'] != 'p_rate') return __error("数据错误1");//防止客户端传个其它字段

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_rate');
            if (true !== $validate) return __error($validate);

            $level = $this->model->quickGet(['id'=>$group_id,'uid'=>$this->user['uid']]);//防止客户端随便传个id过来
            if(empty($level)) return __error("数据错误2");

            $max = $this->model->getMaxRate($group_id,$post['id']);
            if($max >$post['value']) return __error('费率小于用户分组默认费率：'.$max);

            $model = model('app\common\model\SysRate');
            $temp['p_id'] =  $post['id'];
            $temp['group_id'] = $group_id;
            $temp['type'] =  $level['type'];
            $temp['uid'] =  $level['uid'];
            $temp['channel_id'] = 0;
            $id = $model->where($temp)->value('id');
            if(!empty($id)) $temp['id'] = $id;
            $temp['rate'] =  $post['value'];

            if(!empty($temp['id'])){
                $res = $model->__edit($temp);
            }else{
                $res = $model->__add($temp);
            }
            return $res;
        }
    }


    /**
     * 更改分组费率状态
     * @return \think\response\Json
     */
    public function status(){
        $get = $this->request->only('id,group_id');
        if(empty($get['group_id'])) exceptions('数据错误，请重试');

        $find =  model('app\common\model\Ulevel')->where(['id'=>$get['group_id'],'uid'=>$this->user['uid']])->field('type,type1,uid')->find();

        if(empty($find) || !isset($find['type1']))  exceptions('数据错误，请重试');

        //0 商户分组 1 代理分组
        if($find['type1'] == 1){
            $data['channel_id'] = $get['id'];
        }elseif($find['type1'] == 0){
            $data['p_id'] = $get['id'];
        }

        if(!empty($find['uid']))  $data['uid'] = $find['uid'];
        $data['group_id'] = $get['group_id'];
        $data['type'] = $find['type'];

        $model = model('app\common\model\SysRate');

        $SysRate = $model->where($data)->find();

        //验证数据
        if (empty($SysRate)){
            $data['status'] = 0;
            $update =  $model->__add($data,'禁用成功');
        }else{
            //判断状态
            $status = $SysRate['status'];
            $status == 1 ? list($msg, $status) = ['禁用成功', $status = 0] : list($msg, $status) = ['启用成功', $status = 1];
            //执行更新操作操作
            $update =  $model->__edit(['status' => $status,'id' => $SysRate['id'],'uid' =>$this->user['uid']],$msg);

            $code =  json_decode($update->getContent())->code;
            if($status == 0 && $code == 1){
                //删除代理下商户分组选中的通道
                $res = \app\common\model\Ulevel::delChennelGroupID($this->user['uid'],$SysRate['p_id'],$SysRate['id']);
            }

        }

        return $update;
    }


    /**
     * 删除分组
     * @return \think\response\Json
     * @throws \Exception
     */
    public function del(){
        $get = $this->request->only('id');
        $this->model = model('app\common\model\Ulevel');

        //验证数据
        if (!is_array($get['id']))  $get['id'] = [$get['id']];
        if(empty($get['id'])) return __error('数据异常');

        //使用事物保存数据
        $this->model->startTrans();
        $del = $this->model->destroy(function($query) use ($get){
            $query->where([
                ['id','in',$get['id']],
                ['uid','=',$this->user['uid']]
            ]);
        });
        model('app\common\model\SysRate')->destroy(function($query) use ($get){
            $query->where([
                ['group_id','in',$get['id']],
                ['uid','=',$this->user['uid']],
                ['type','=',1]//代理
            ]);
        });
        if (!($del >= 1)) {
            $this->model->rollback();
            return __error('数据有误，请稍后再试！');
        }
        $this->model->commit();

        return __success('删除成功');

    }



    //选择通道
    public function mode()
    {
        $this->model = model('app\common\model\Ulevel');
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 1000);
                $search = (array)$this->request->get('search', []);

                $id =  $this->request->get('id', 0);
                $channel =  $this->model->where(['id'=>$id,'uid'=>$this->user['uid']])->value('channel_id');
                $search['channel'] = json_decode($channel,true);
                $search['uid'] = $this->user['uid'];

                return json(model('app\common\model\ChannelGroup')->uList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '选择通道列表',
            ];

            return $this->fetch('', $basic_data);
        }
    }


    /**
     * 确认保存通道分组
     * @return \think\response\Json
     * @throws \Exception
     */
    public function confirm() {

        $this->model = model('app\common\model\Ulevel');
        $get = $this->request->get();

        $id =  $this->model->where(['id'=>$get['pid'],'uid'=>$this->user['uid']])->value('id');
        if(empty($id)) __error('数据错误');

        $mode = [];
        //验证数据
        foreach ($get['id'] as $k => $val){
            $data['id'] = $val;
            $validate = $this->validate($data, 'app\common\validate\ChannelGroup.channel');
            if (true !== $validate){
                unset($get['id'][$k]);
                continue;
            }
            $mode[] = $val;
        }

        if(empty($get['pid']) || empty($mode)) return __error('请选择通道分组！');

        $mode1 = [];
        if(!empty($mode)){
            $arr =  model('app\common\model\ChannelGroup')->where('id','in',$mode)->column('id,p_id','id');
            foreach ($arr as $k => $v){
                $mode1[$v][] = $k;
            }
        }

        $data['id'] = $id;
        $data['channel_id'] = json_encode($mode1);

        //执行更新操作操作
        $update = $this->model->__edit($data,'保存成功');
        return $update;
    }



    /**
     * 选择代理用户分组
     * @return mixed|\think\response\Json
     */
    public function agent_group(){
        $uid = $this->request->get('uid/d',0);
        $user = \app\common\model\Uprofile::where(['uid'=>$uid])->find();
        if(empty($user) || $user['pid'] != $this->user['uid']) exceptions('数据错误，请重试~');

        if (!$this->request->isPost()) {

            $type = ($user['who'] == 2)?1:0; //区分获取什么分组
            $group =   \app\common\model\Ulevel::where(['uid'=>$user['pid'],'type1'=>$type])->field('id,title')->select()->toArray();

            //基础数据
            $basic_data = [
                'title' => '选择用户分组',
                'group_id'  => $user['group_id'],
                'uid'  => $user['uid'],
                'group'  => $group,//用户分组
            ];

            return $this->fetch('', $basic_data);
        }else{
            $profile['id'] = $user['id'];
            $profile['group_id'] = $this->request->post('group_id/d',0);

            $res = model('\app\common\model\Uprofile')->__edit($profile);
            return $res;
        }
    }




}