<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\model\Uprofile;

class Member extends AdminController {

    /**
     * Member模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * Member constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->model = model('app\common\model\Umember');
    }

    /**
     * 会员金额
     * @return mixed
     */
    public function money(){
        $uid = $this->request->get('uid/d',0);
        $Umoney =  model('app\common\model\Umoney');
        $user =$Umoney->where(['uid'=>$uid])->field('id,uid,balance,total_money,frozen_amount,frozen_amount_t1,artificial,channel_id,df')->find();
        if(empty($user)) return msg_error('数据错误，请重试！');
        $user = $user->toArray();

        if (!$this->request->isPost()){
            //基础数据
            $basic_data = [
                'status' => [9=>'人工冻结',10=>'人工解冻',3=>'添加',4=>'扣除',13=>'余额转代付金额',14=>'代付金额转余额'],
                'user'  => $user,//用户金额
            ];
            return $this->fetch('', $basic_data);
        } else {
            $money = $this->request->only('remark,change,type,__token__','post');

            //验证数据
            $validate = $this->validate($money, 'app\common\validate\Money.edit');
            //if (true !== $validate) return __error($validate);

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
                __log($uid.$res['log'].'失败');
                return __error($msg);
            }
            $Umoney->commit();

            __log($uid.$res['log'].'成功');
            empty($msg) && $msg = '操作成功';
            return __success($msg);
        }
    }




    /**
     * 选择用户分组
     * @return mixed|\think\response\Json
     */
    public function group(){
        if (!$this->request->isPost()) {
            $uid = $this->request->get('uid/d',0);
            $user = \app\common\model\Uprofile::where(['uid'=>$uid])->find();

            $agent = $this->model->where([
                ['who','=','2'],
                ['status','=','1']
            ])->field('uid,id,who')->select()->toArray();

            $type = $user['who'] == 2 ?1:0; //区分获取什么分组
            $group =   \app\common\model\Ulevel::where(['uid'=>$user['pid'],'type1'=>$type])->field('id,title')->select()->toArray();

            //基础数据
            $basic_data = [
                'title' => '选择用户分组',
                'user'  => $user,
                'group'  => $group,//用户分组
                'agent'  => $agent,//所有的代理
            ];

            return $this->fetch('', $basic_data);
        }else{
            $profile['id'] = $this->request->post('id/d',0);
            $profile['group_id'] = $this->request->post('group_id/d',0);

            $res = model('\app\common\model\Uprofile')->__edit($profile);
           return $res;
        }
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
                $search['uid'] = (int)$this->request->get('uid', 0);
                return json($model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title' => '代理关系表列表',
                'data'  => '',
            ];

            return $this->fetch('', $basic_data);
        }
    }


    public function rate() {

        $group_id = (int)$this->request->get('id',0);
        $model = model('app\common\model\SysRate');
        $SysRate = $model->where(['group_id'=>$group_id])->select()->toArray();

        if (!$this->request->isPost()) {
            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 100);
                $search = (array)$this->request->get('search', []);
                $result = model('app\common\model\PayProduct')->aList($page, $limit, $search);

                foreach ($result['data'] as $k => $v){

                    $result['data'][$k]['status1'] = 0;
                    //支付产品是开启的 才给开启修改
                    if($v['status'] == 1) $result['data'][$k]['status1'] = 1;

                    foreach ($SysRate as $k1 => $v1){
                        if($v1['p_id'] == $v['id']){
                            $result['data'][$k]['p_rate'] = $v1['rate'];

                            if($v['status'] == 1)  $result['data'][$k]['status'] = $v1['status'];
                        }
                    }
                }


                return json($result);
            }

            //基础数据
            $basic_data = [
                'title'  => '系统分组费率列表',
                'data'   => '',
                'status' => [['id' => 1, 'title' => '启用'], ['id' => 0, 'title' => '禁用']],
            ];

            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only('id,field,value');

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_rate');
            if (true !== $validate) return __error($validate);

            $uid = $this->model->where(['id'=>$group_id])->value('uid');
            $type = $this->model->where(['id'=>$group_id])->value('type');

            $data = array();
            if(empty($SysRate)){
                //都不存在的情况
                $PayProduct =   model('app\common\model\PayProduct')->field('id,p_rate')->select()->toArray();
                foreach ($PayProduct as $k=>$val){
                    $temp =  [];
                    $temp['type'] =  $type;
                    $temp['p_id'] =  $val['id'];
                    $temp['group_id'] = $group_id;
                    if($post['id'] == $val['id']){
                        $temp['rate'] = $post['value'];
                    }else{
                        $temp['rate'] = $val['p_rate'];
                    }
                    if(!empty($uid)) $temp['uid'] = $uid;
                    $data[]=$temp;
                }
            }else{

                //单条不存在的情况
                $id = $model->where(['group_id'=>$group_id,'p_id'=>$post['id']])->value('id');
                $temp['p_id'] =  $post['id'];
                $temp['group_id'] = $group_id;
                $temp['rate'] = $post['value'];
                $temp['type'] =  $type;
                if(!empty($id)) $temp['id'] = $id;
                if(!empty($uid)) $temp['uid'] = $uid;
                $data[]=$temp;
            }

            $msg = '操作失败';
            $res = 0;
            if(!empty($data)) $res = $model->saveAll($data) &&   $msg = '操作成功'  ;

            if($res >= 1){
                return __success($msg);
            }else{
                return __error($msg);
            }
        }
    }


    //商户的支付产品
    public function product(){
        $uid = $this->request->get('uid/d','0');
        $profile = Uprofile::quickGet(['uid'=>$uid]);

        if(empty($profile) || $profile['who'] != 0 )  return msg_error('数据错误，请重试~');
        if(empty($profile['group_id'])) return msg_error('数据错误，该商户未选着用户分组');


        if (!$this->request->isPost()){
            //ajax访问
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 100);
                $search = (array)$this->request->get('search', []);
                $result = model('app\common\model\PayProduct')->aList($page, $limit, $search);

                foreach ($result['data'] as $k => $v){

                    $result['data'][$k]['status1'] = 1;

                    $rateStatus = \app\common\service\RateService::getMemStatus($uid,$v['id']); //当前用户的费率状态
                    //当支付产品和通道分组关闭是不给修改
                    if($rateStatus['type'] > 1 && $rateStatus['status'] == 0){
                        $result['data'][$k]['status1'] = 0;
                    }

                    if($rateStatus['id'] == $v['id']){
                        $result['data'][$k]['status'] = $rateStatus['status'];
                        $result['data'][$k]['p_rate'] = $rateStatus['rate'];
                    }

                }

                return json($result);
            }

            //基础数据
            $basic_data = [
                'title' => '商户支付产品费率列表',
                'data' => '',
            ];

            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only('id,field,value');

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_rate');
            if (true !== $validate) return __error($validate);

            if($profile['pid'] > 0){
                $rate1 = \app\common\service\RateService::getMemRate($profile['pid'],$post['id']); //当前用户分组的费率状态
                if($rate1 > $post['value'] ){
                    return __error("设置费率不能小于上级设定费率：".$rate1);
                }
            }


            $model =  model('app\common\model\SysRate');
            $rate['type'] = 2;
            $rate['uid'] = $uid;
            $rate['p_id'] = $post['id'];
            $id =  $model->where($rate)->value('id');

            $rate['rate'] = $post['value'];
            $rate['status'] = 1;

            if(!empty($id)){
                $rate['id'] = $id;
                $res = $model->__edit($rate);
            }else{
                $res = $model->__add($rate);
            }

            return $res;
        }
    }

    //代理的支付通道分组
    public function channel(){
        $uid = $this->request->get('uid/d','0');
        $profile = Uprofile::quickGet(['uid'=>$uid]);
        if(empty($profile) || $profile['who'] != 2 )  return msg_error('数据错误，请重试~');
        if(empty($profile['group_id'])) return msg_error('数据错误，该商户未选着用户分组');

        if (!$this->request->isPost()){
            //ajax访问
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 100);
                $search = (array)$this->request->get('search', []);
                $result = model('app\common\model\ChannelGroup')->bList($page, $limit, $search);

                foreach ($result['data'] as $k => $v){

                    $result['data'][$k]['status1'] = 1;

                    $rateStatus = \app\common\service\RateService::getAgentStatus($uid,$v['id']); //当前用户的费率状态

                    //当平台通道分组关闭  上级支付通道分组  是不给修改
                    if($rateStatus['type'] > 1 && $rateStatus['status'] == 0){
                        $result['data'][$k]['status1'] = 0;
                    }

                    if($rateStatus['id'] == $v['id']){
                        $result['data'][$k]['status'] = $rateStatus['status'];
                        $result['data'][$k]['c_rate'] = $rateStatus['rate'];
                    }

                }

                return json($result);
            }

            //基础数据
            $basic_data = [
                'title' => '代理通道分组费率列表',
                'data' => '',
            ];

            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only('id,field,value');

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_rate');
            if (true !== $validate) return __error($validate);

            if($profile['pid'] > 0){
                $rate1 = \app\common\service\RateService::getAgentRate($profile['pid'],$post['id']); //当前用户分组的费率状态
                if($rate1 > $post['value'] ){
                    return __error("设置费率不能小于上级设定费率：".$rate1);
                }
            }



            $model =  model('app\common\model\SysRate');
            $rate['uid'] = empty($profile['pid'])?0:$profile['pid'];
            $rate['p_id'] = 0;
            $rate['type'] = empty($profile['pid'])?0:1; //是否代理分组 平台分组
            $rate['channel_id'] = $post['id'];
            $rate['group_id'] = $profile['group_id'];
            $id =  $model->where($rate)->value('id');
            if(!empty($id)) $rate['id'] = $id;
            $rate['rate'] = $post['value'];
            $rate['status'] = 1;

            if(!empty($rate['id'])){
                $res = $model->__edit($rate);
            }else{
                $res = $model->__add($rate);
            }

            return $res;
        }
    }


    //修改代理费率状态
    public function channelStatus() {
        $channel_id = $this->request->get('id/d','0');//支付通道分组ID
        $uid = $this->request->get('uid/d','0');
        $profile = Uprofile::quickGet(['uid'=>$uid]);
        if(empty($profile) || $profile['who'] != 2 )  return msg_error('数据错误，请重试~');
        if(empty($profile['group_id']))  return msg_error('数据错误，该商户未选着用户分组');

        $data['uid'] = empty($profile['pid'])?0:$profile['pid'];
        $data['p_id'] = 0;
        $data['type'] = empty($profile['pid'])?0:1; //是否代理分组 平台分组
        $data['channel_id'] = $channel_id;
        $data['group_id'] = $profile['group_id'];
        $model =  model('app\common\model\SysRate');
        //判断状态
        $SysRate =  $model->quickGet($data);

        if(!empty($SysRate)){
            $data['status'] = $SysRate['status'];
            $data['id'] = $SysRate['id'];
        }else{
            $data['rate'] = \app\common\service\RateService::getAgentRate($uid,$channel_id);
            $data['status'] = 1;//默认开启
        }

        $data['status'] == 1 ? list($msg, $status) = ['禁用成功', $status = 0] : list($msg, $status) = ['启用成功', $status = 1];

        $data['status'] = $status;
        if(!empty($data['id'])){
            $res = $model->__edit($data,$msg);
        }else{
            $res = $model->__add($data,$msg);
        }

        return $res;
    }



    /**
     * 修改个人费率状态
     * @return \think\response\Json
     */
    public function rateStatus() {
        $p_id = $this->request->get('id/d','0');//支付产品
        $uid = $this->request->get('uid/d','0');
        $profile = Uprofile::quickGet(['uid'=>$uid]);
        if(empty($profile) || $profile['who'] != 0 )  return msg_error('数据错误，请重试~');
        if(empty($profile['group_id']))  return msg_error('数据错误，该商户未选着用户分组');

        $data['uid'] = $uid;
        $data['p_id'] = $p_id;
        $data['type'] = 2;
        $model =  model('app\common\model\SysRate');
        //判断状态
        $SysRate =  $model->where($data)->find();

        if(!empty($SysRate)){
            $data['status'] = $SysRate['status'];
            $data['id'] = $SysRate['id'];
        }else{
            $data['status'] = 1;//默认开启
            $data['rate'] = \app\common\service\RateService::getMemRate($uid,$p_id);
        }

        $data['status'] == 1 ? list($msg, $status) = ['禁用成功', $status = 0] : list($msg, $status) = ['启用成功', $status = 1];

        $data['status'] = $status;
        if(!empty($data['id'])){
            $res = $model->__edit($data,$msg);
        }else{
            $res = $model->__add($data,$msg);
        }

        return $res;
    }


    /**
     * 商户列表
     */
    public function index() {
        if (!$this->request->isPost()) {
            //ajax访问
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title' => '商户列表',
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
     * 代理列表
     */
    public function agent() {
        if (!$this->request->isPost()) {
            //ajax访问
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                return json($this->model->bList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title' => '商户列表',
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
     * 添加商户或者代理
     * @return mixed
     */
    public function add(){

        if (!$this->request->isPost()) {
            $agent = $this->model->where([
                ['who','=','2'],
                ['status','=','1']
            ])->field('uid,id,who')->select()->toArray();

            $group =   \app\common\model\Ulevel::field('id,title')->select()->toArray();

            //基础数据
            $basic_data = [
                'title' => '添加商户或者代理',
                'auth'  => model('app\common\model\SysAuth')->getList(1),//权限组
                'group'  => $group,//用户分组
                'agent'  => $agent,//所有的代理
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {

            $member = $this->request->only('username,password,password1,nickname,phone,qq,who,remark,auth_id');
            $pid = $this->request->post('pid/d',0);

            !isset($member['auth_id']) && $member['auth_id'] = [];
            //数组转json
            $member['auth_id'] = json_encode($member['auth_id']);

            //验证数据
            $validate = $this->validate($member, 'app\common\validate\Umember.add');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            $member['password'] = password($member['password']);
            $member['status'] = 1;
            $member['profile_pid'] = $pid; //上级代理UID
            $member['pid'] = $pid;

            //保存数据,返回结果
            //使用事物保存数据
            $this->model->startTrans();
            $result = $this->model->create($member);

            //如果属性账户和金额账户创建失败则返回
            if (!$result || empty($result['id'])  || empty($result['profile'])  || empty($result['money']) ) {
                $this->model->rollback();
                return __error('数据有误，请稍后再试!');
            }
            $this->model->commit();
            return __success('添加成功!');

        }

    }


    /**
     * 添加员工
     * @return mixed
     */
    public function add_staff(){

        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '添加员工',
                'auth'  => model('app\common\model\SysAuth')->getList(1),//权限组
            ];
            $this->assign($basic_data);
            return $this->staff();
        } else {
            $member = $this->request->only('username,password,password1,nickname,phone,qq,who,remark,auth_id,pid,who');

            !isset($member['auth_id']) && $member['auth_id'] = [];
            //数组转json
            $member['auth_id'] = json_encode($member['auth_id']);

            //验证数据
            $validate = $this->validate($member, 'app\common\validate\Umember.add_staff');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            $member['password'] = password($member['password']);
            $member['status'] = 1;
            $result = $this->model->__add($member);
            return $result;

        }
    }


    /**
     * 修改商户信息
     * @return mixed|string|\think\response\Json
     */
    public function edit() {
        if (!$this->request->isPost()) {

            $agent = $this->model->where([
                ['who','=','2'],
                ['status','=','1']
            ])->field('uid,id,who')->select()->toArray();

            $group =   \app\common\model\Ulevel::field('id,title')->select()->toArray();
            //查找所需修改用户
            $Member = $this->model->where('id', $this->request->get('id'))->find();
            if (empty($Member)) return msg_error('暂无数据，请重新刷新页面！');

            $auth = model('app\common\model\SysAuth')->getList(1)->toArray();

            $auth_id = json_decode($Member['auth_id'], true);

            foreach ($auth as $k => $val) {
                $is_checked = false;
                foreach ($auth_id as $k_1) $val['id'] == $k_1 && $is_checked = true;
                $auth[$k]['is_checked'] = $is_checked;
            }

            foreach ($group as $k => $val) {
                $is_checked = false;
                if($Member['profile']['group_id'] == $val['id']) $is_checked = true;
                $group[$k]['group_checked'] = $is_checked;
            }

            foreach ($agent as $k => $val) {
                $is_checked = false;
                if($Member['profile']['pid'] == $val['uid']) $is_checked = true;
                $agent[$k]['agent_checked'] = $is_checked;
            }

            //基础数据
            $basic_data = [
                'title' => '修改商户信息',
                'user'  => $Member->hidden(['password','who','pid','is_single','single_key','google_token']),
                'auth'  => $auth,
                'group'  => $group,//用户分组
                'agent'  => $agent,//所有的代理
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {

            $post = $this->request->only('username,nickname,phone,qq,who,remark,auth_id,id');
            $profile = $this->request->only('pid');
            $pid = $this->request->post('p_id','0');


            !isset($post['auth_id']) && $post['auth_id'] = [];
            $post['auth_id'] = json_encode($post['auth_id']); //数组转json

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.edit');
            if (true !== $validate) return __error($validate);

            $res = $this->model->__edit($post);

            if(!empty($profile)){

               $Uprofile = model('\app\common\model\Uprofile');

                $profile['id'] = $pid;

                $agent_level = 0;
                if($post['who'] == 2){
                    if(!empty($profile['pid'])){
                        //代理等级
                        $agent_level  = $Uprofile->where('uid',$profile['pid'])->value('level');
                    }
                    $agent_level = $agent_level + 1;
                }
                $profile['level'] = $agent_level;
                $profile['who'] = $post['who'];

                $Uprofile->__edit($profile);
            }
            return  $res;
        }
    }


    /**
     * 员工
     * @return mixed|string|\think\response\Json
     */
    public function edit_staff() {
        if (!$this->request->isPost()) {

            //查找所需修改用户
            $Member = $this->model->where('id', $this->request->get('id','0'))->find();
            if (empty($Member)) return msg_error('暂无数据，请重新刷新页面！');

            $auth = model('app\common\model\SysAuth')->getList(1)->toArray();

            $auth_id = json_decode($Member['auth_id'], true);

            foreach ($auth as $k => $val) {
                $is_checked = false;
                foreach ($auth_id as $k_1) $val['id'] == $k_1 && $is_checked = true;
                $auth[$k]['is_checked'] = $is_checked;
            }

            //基础数据
            $basic_data = [
                'title' => '修改商户员工信息',
                'user'  => $Member->hidden(['password','who','pid','is_single','single_key','google_token']),
                'auth'  => $auth,
            ];
            $this->assign($basic_data);

            return $this->staff();
        } else {
            $post = $this->request->post();

            !isset($post['auth_id']) && $post['auth_id'] = [];
            $post['auth_id'] = json_encode($post['auth_id']); //数组转json

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.edit');
            if (true !== $validate) return __error($validate);

            return   $this->model->__edit($post);

        }
    }


    /**
     * 表单模板
     * @return mixed
     */
    protected function form(){
        return $this->fetch('form');
    }
    protected function staff(){
        return $this->fetch('staff');
    }

    /**
     * 商户的删除
     * @return \think\response\Json
     */
    public function del() {
        $get = $this->request->get();

        $data = array();
        //执行删除操作
        if (!is_array($get['id'])) {
            $pid =   $this->model->where('pid', $get['id'])->column('id');
            foreach ($pid as $k =>$v){
                $data[$k]['id'] = $v;
                $data[$k]['status'] = '3';
            }
            $data[]['id'] = $get['id'];
            $data[]['status'] = 3;

        } else {
            foreach ($get['id'] as $k =>$v){
                $data[$k]['id'] = $v;
                $data[$k]['status'] = 3;
            }
        }
        $del = $this->model->saveAll($data);

        if (!!$del ) {
            return __success('删除成功！');
        } else {
            return __error('数据有误，请刷新重试！');
        }
    }

    /**
     * 修改用户密码
     * @return mixed|string|\think\response\Json
     */
    public function edit_password() {
        if (!$this->request->isPost()) {
            if (empty($this->request->get('id'))) return msg_error('暂无用户信息！');

            $Member = $this->model->quickGet(['id'=> $this->request->get('id')]);
            if (empty($Member)) return msg_error('暂无用户信息，请关闭页面刷新重试！');
            $basic_data = [
                'title' => '修改商户密码',
                'user'  => $Member,
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.edit_password');
            if (true !== $validate) return __error($validate);

            //修改密码数据
            return $this->model->editPassword($post);
        }
    }



    /**
     * 绑定谷歌
     * @return mixed|string|\think\response\Json
     */
    public function save_google() {
        if (!$this->request->isPost()) {

            $Member = $this->model->quickGet(['id'=> $this->Member['id']]);
            if (empty($Member)) return msg_error('暂无用户信息，请关闭页面刷新重试！');

            if(!empty($Member['google_token'])){
                session('admin.google_token',$Member['google_token']);
                $Member['token'] = $Member['google_token'];
            }else{
                $Member['token'] = (new \tool\Goole())->createSecret(17);
            }

            $basic_data = [
                'title' => '绑定谷歌',
                'Member'  => $Member,
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->post();

            $post['id'] = session('admin_info.id');
            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.save_google');
            if (true !== $validate) return __error($validate);
            $msg = '绑定成功';

            //修改密码数据
            return $this->model->__edit($post,$msg);
        }
    }


    /**
     * 修改用户自己的密码
     * @return mixed|string|\think\response\Json
     */
    public function changepwd() {
        if (!$this->request->isPost()) {

            $Member = $this->model->quickGet(['id'=> $this->Member['id']]);
            if (empty($Member)) return msg_error('暂无用户信息，请关闭页面刷新重试！');
            $basic_data = [
                'title' => '修改商户密码',
                'Member'  => $Member,
            ];
            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->post();


            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.edit_password1');
            if (true !== $validate) return __error($validate);

            //修改密码数据
            return $this->model->editPassword($post);
        }
    }


    /**
     * 重置自己的谷歌
     */
    public function change_google() {

        $Member = $this->model->quickGet(['id'=> $this->Member['id']]);
        if (empty($Member)) return msg_error('暂无用户信息，请关闭页面刷新重试！');

        $data['id'] =  $this->Member['id'];
        $data['google_token'] =  '';

        //使用事物保存数据
        $this->model->startTrans();
        $save = $this->model->save($data,['id'=>$data['id']]);
        if (!$save) {
            $this->model->rollback();
            return msg_error('数据有误，请稍后再试！');
        }
        $this->model->commit();
        return msg_success('重置成功~');

    }





    /**
     * 更改商户状态
     * @return \think\response\Json
     */
    public function status() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Umember.status');
        if (true !== $validate) return __error($validate);

        //判断商户状态
        $status = $this->model->where('id', $get['id'])->value('status');
        $status == 1 ? list($msg, $status) = ['禁用成功', $status = 0] : list($msg, $status) = ['启用成功', $status = 1];


        $data['id'] = $get['id'];
        $data['status'] = $status;
        return   $this->model->__edit($data,$msg);
    }


    /**重置秘钥
     * @return \think\response\Json
     */
    public function secret() {
        $get = $this->request->get('id/d',0);
        $find =  $this->model->quickGet($get);
        if(empty($find['profile']['id'])) return __error('重置失败');

        $data['id'] = $find['profile']['id'];
        $data['secret'] = password($find['uid'].mt_rand(1,200),$key = '1233');

        return (new Uprofile)->__edit($data,'重置秘钥成功');
    }


    /**重置支付密码
     * @return \think\response\Json
     */
    public function paypwd() {
        $get = $this->request->get('id/d',0);
        $find =  $this->model->quickGet($get);

        if(empty($find['profile']['id'])) return __error('重置失败');

        $data['id'] = $find['profile']['id'];
        $data['pay_pwd'] = password(md5(123456));

        return (new Uprofile)->__edit($data,'重置支付密码成功');
    }



    /**Api接口代付
     * @return \think\response\Json
     */
    public function api() {
        $id = $this->request->get('id/d',0);
        $value = $this->request->get('value/d',0);

        $find =  $this->model->quickGet($id);
        if(empty($find['profile']['id'])) return __error('操作失败');
        $data['id'] = $find['profile']['id'];

        if($value == 3){
            $data['id'] = $find['profile']['id'];
            $data['df_secret'] = password($find['uid'].mt_rand(1,200),$key = '1233');
            return (new Uprofile)->__edit($data,'重置代付秘钥成功');
        }

        if($value == $find['profile']['df_api1']) return __success('操作成功');

        $data['df_api1'] = $value;
        if(empty($find['profile']['df_secret']))  $data['df_secret'] = password($find['uid'].mt_rand(1,200),$key = '1233');

        return (new Uprofile)->__edit($data,'操作成功');
    }



    /**
     * 商户单点登入
     * @return \think\response\Json
     */
    public function single() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Umember.single');
        if (true !== $validate) return __error($validate);

        //判断商户状态
        $status = $this->model->where('id', $get['id'])->value('is_single');
        $status == 1 ? list($msg, $status) = ['禁用成功', $status = 0] : list($msg, $status) = ['启用成功', $status = 1];

        $data['id'] = $get['id'];
        $data['is_single'] = $status;
        return   $this->model->__edit($data,$msg);
    }





    /**
     * 重置谷歌
     * @return \think\response\Json
     */
    public function google() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Umember.google');
        if (true !== $validate) return __error($validate);

        //判断商户状态
        $google_token = $this->model->where('id', $get['id'])->value('google_token');

        if(empty($google_token)){
            return __error('错误操作！');
        }else{
            $data['id'] = $get['id'];
            $data['google_token'] = '';
            return   $this->model->__edit($data,'重置成功');
        }

    }


    /**
     * 修改自己的信息
     * @return mixed|string|\think\response\Json
     */
    public function edit_self() {
        if (!$this->request->isPost()) {

            //查找所需修改用户
            $Member =  $this->model->quickGet(['id'=>$this->Member['id']]);
            if (empty($Member)) return msg_error('暂无数据，请重新刷新页面！');

            //基础数据
            $basic_data = [
                'title' => '修改商户信息',
                'Member'  => $Member->hidden(['password']),
            ];
            $this->assign($basic_data);

            return $this->fetch('form_self');
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.editSelf');
            if (true !== $validate) return __error($validate);


            //保存数据,返回结果
            return $this->model->editSelf($post);
        }
    }

    /**
     *  银行卡列表
     */
    public function bank(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 10);
            $search = (array)$this->request->get('search', []);

            return json(model('app\common\model\Bank')->aList($page, $limit, $search));
        }
        $basic_data = [
            'title' => '银行卡列表',
        ];
        return $this->fetch('', $basic_data);
    }
    /**
     *  删除银行卡
     */
    public function delBank(){
        $get = $this->request->only('id');
        if(empty($get)) return __error('数据异常');

        //执行操作
        $del = model('app\common\model\Bank')->__del($get);
        return $del;

    }

    /**
     *  会员IP列表
     */
    public function ip(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page/d', 1);
            $limit = $this->request->get('limit/d', 10);
            $search = (array)$this->request->get('search', []);
            $search['type'] = [0,1,2];
            return json(model('app\common\model\Ip')->aList($page, $limit, $search));
        }
        $basic_data = [
            'title' => '会员IP列表',
        ];
        return $this->fetch('', $basic_data);
    }



}