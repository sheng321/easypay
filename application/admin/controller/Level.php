<?php


namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\service\RateService;


/**用户分组
 * Class Level
 * @package app\admin\controller
 */
class Level extends AdminController {

    /**
     * Level模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * node constructor.
     */
    public function __construct() {
        parent::__construct();
        
        $this->model = model('app\common\model\Ulevel');
    }

    /**
     * 分组列表
     */
    public function index() {
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = 0;
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '系统分组列表',
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


    /**
     * 商户分组费率设置
     * @return mixed|\think\response\Json
     */
    public function rate() {

        $group_id = $this->request->get('id/d',0);
        if (!$this->request->isPost()) {
            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 100);
                $search = (array)$this->request->get('search', []);
                $result = model('app\common\model\PayProduct')->aList($page, $limit, $search);


                foreach ($result['data'] as $k => $v){
                    $result['data'][$k]['status1'] = 1;

                   $rate = RateService::getGroupStatus($group_id,$v['id']);

                   if(!empty($rate)){
                       $result['data'][$k]['p_rate'] = $rate['rate'];
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
                'status' => [['id' => 1, 'title' => '启用'], ['id' => 0, 'title' => '禁用']],
            ];

            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only('id,field,value');
            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_rate');
            if (true !== $validate) return __error($validate);

            $level = $this->model->quickGet($group_id);

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
     * 代理分组费率设置
     * @return mixed|\think\response\Json
     */
    public function agent_rate() {

        $group_id = (int)$this->request->get('id',0);
        $model = model('app\common\model\SysRate');
        if (!$this->request->isPost()) {
            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 100);
                $search = (array)$this->request->get('search', []);
                $result = model('app\common\model\ChannelGroup')->bList($page, $limit, $search);

                foreach ($result['data'] as $k => $v){
                    $result['data'][$k]['status1'] = 1;

                    $rate = RateService::getGroupStatus($group_id,$v['id']);//当前费率情况

                    if(!empty($rate)){
                        $result['data'][$k]['c_rate'] = $rate['rate'];
                        $result['data'][$k]['status'] = $rate['status'];
                        // 上级状态优先级高
                        if($rate['type'] > 1){
                            $result['data'][$k]['status1'] = $rate['status'];
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
            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_rate');
            if (true !== $validate) return __error($validate);

            $level = $this->model->quickGet($group_id);
            if(empty($level)) return __error("数据错误");

            if($level['uid']>0){
                $group_id1 = \app\common\model\Uprofile::where(['uid'=>$level['uid']])->value('group_id');
                $max = 0;
                if(!empty($group_id1)){
                    $GroupStatus =  \app\common\service\RateService::getGroupStatus($group_id1,$post['id']); //上级代理分组费率
                    $max = isset($GroupStatus['rate'])?$GroupStatus['rate']:0;
                }
            }else{
                $GroupStatus =  \app\common\service\RateService::getGroupStatus(0,$post['id']); //平台代理分组费率
                $max = isset($GroupStatus['rate'])?$GroupStatus['rate']:0;
            }
            if($max > $post['value']) return __error('费率小于上级用户分组默认费率：'.$max);

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
     * 更改分组费率状态
     * @return \think\response\Json
     */
    public function status(){
        $get = $this->request->only('id,group_id');
        if(empty($get['group_id'])) exceptions('数据错误，请重试');

        $find =  model('app\common\model\Ulevel')->where(['id'=>$get['group_id']])->field('type,type1,uid')->find();
        //0 商户分组 1 代理分组
        if($find['type1'] == 1){
          $data['channel_id'] = $get['id'];
        }elseif($find['type1'] == 0){
            $data['p_id'] = $get['id'];
        }else{
            exceptions('数据错误，请重试');
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
            $update =  $model->__edit(['status' => $status,'id' => $SysRate['id']],$msg);
        }

        return $update;
    }




    /**
     * 代理分组列表
     */
    public function agent() {
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = 1;
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '系统分组列表',
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


    /**
     * 添加分组
     * @return mixed|\think\response\Json
     */
    public function add() {
        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '添加分组',
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->only('title,remark,type1');

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Level.add');
            if (true !== $validate) return __error($validate);

           $res = $this->model->__add($post);
           return $res;
        }


    }


   //添加代理分组
    public function add_agent() {
        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '添加分组',
            ];
            $this->assign($basic_data);

            return $this->agent_form();
        } else {
            $post = $this->request->only('title,remark,type1,uid');

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Level.add_agent');
            if (true !== $validate) return __error($validate);

            $post['type'] = 1;
            $res = $this->model->__add($post);
            return $res;
        }


    }


    /**
     * 修改分组
     * @return mixed|string|\think\response\Json
     */
    public function edit(){
        if (!$this->request->isPost()) {

            //查找所需修改分组
            $auth = $this->model->where('id', $this->request->get('id'))->find();
            if (empty($auth)) return msg_error('暂无数据，请重新刷新页面！');

            //基础数据
            $basic_data = [
                'title' => '修改分组信息',
                'auth'  => $auth,
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->only('id,title,remark,type1');

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Level.edit');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            $result = $this->model->__edit($post);

            return $result;
        }
    }




    /**
     * 表单模板
     * @return mixed
     */
    protected function form() {
        return $this->fetch('form');
    }
    protected function agent_form() {
        return $this->fetch('agent_form');
    }

    /**
     * 删除分组
     * @return \think\response\Json
     * @throws \Exception
     */
    public function del(){
        $get = $this->request->only('id');

        //验证数据
        if (!is_array($get['id'])) {
            $validate = $this->validate($get, 'app\common\validate\Level.del');
            if (true !== $validate) return __error($validate);
        }else{
            foreach ($get['id'] as $k => $val){
                $data['id'] = $val;
                $validate = $this->validate($data, 'app\common\validate\Level.del');
                if (true !== $validate) unset($get['id'][$k]);
            }
        }

        if(empty($get)) return __error('数据异常');

        //执行操作
        $del = $this->model->__del($get);

        $res1 = json_decode($del->getContent(),true);
        if($res1['code'] == 1){
            //删除通道分组费率
            model('app\common\model\SysRate')->where([
                ['group_id','in',$get['id']],
            ])->delete();
        }
        return $del;
    }



    //选择通道
    public function mode()
    {
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 1000);
                $search = (array)$this->request->get('search', []);

               $id =  $this->request->get('id', 0);
                $channel =  $this->model->where('id', $id)->value('channel_id');
                $search['channel'] = json_decode($channel,true);

                return json(model('app\common\model\ChannelGroup')->uList($page, $limit, $search));
            }


            //基础数据
            $basic_data = [
                'title'  => '选择通道列表',
            ];

            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_field');
            if (true !== $validate) return __error($validate);

            //权重 和 并发 编辑
            if($post['field'] == 'weight' || $post['field'] == 'concurrent'){

                if(!is_numeric($post['value'])){
                    return __error('请输入数字！');
                }


                $data[$post['field']] = json_decode($this->model->where('id', $this->request->get('g_id'))->value($post['field']),true);
                if(empty($data[$post['field']])) $data[$post['field']] = [];
                $data[$post['field']][$post['id']] = $post['value'];

                $data2['id'] = $this->request->get('g_id');
                $data2['field'] = $post['field'];
                $data2['value'] = json_encode($data[$post['field']]);


                //保存数据,返回结果
                return $this->model->editField($data2);
            }else{

                //保存数据,返回结果
                return model('app\common\model\Channel')->editField($post);
            }

        }


    }


    /**
     * 确认保存通道分组
     * @return \think\response\Json
     * @throws \Exception
     */
    public function confirm() {
        $get = $this->request->get();

        $mode = [];
        //验证数据
        if (isset($get['id'])) {
            if (!is_array($get['id'])) {
                $validate = $this->validate($get, 'app\common\validate\ChannelGroup.channel');
                if (true !== $validate) return __error($validate);
                $mode[] = $get['id'];
            }else{
                foreach ($get['id'] as $k => $val){
                    $data['id'] = $val;
                    $validate = $this->validate($data, 'app\common\validate\ChannelGroup.channel');
                    if (true !== $validate){
                        unset($get['id'][$k]);
                        continue;
                    }
                    $mode[] = $val;
                }
            }

        }

        if(empty($get['pid'])) return __error('请选择通道分组！');
        $mode1 = [];
       if(!empty($mode)){
           $arr =  model('app\common\model\ChannelGroup')->where('id','in',$mode)->column('id,p_id','id');
           foreach ($arr as $k => $v){
               $mode1[$v][] = $k;
           }
       }

        $data['id'] = $get['pid'];
        $data['channel_id'] = json_encode($mode1);

        //执行更新操作操作
        $update = $this->model->__edit($data,'保存成功');
        return $update;
    }






    /**
     * 顶置
     * @return \think\response\Json
     */
    public function top() {
        $get = $this->request->get();

        $data = [];
        //没有数据就把所有选中的前置
        if(empty($get['search']['title']) && !empty($get['id'])){
            $mode = json_decode($this->model->where(['id'=>$get['id']])->value('channel_id'),true);
            $mode1 = [];
            foreach ($mode as $k=>$v){
                $mode1[] = $k;
            }
            if(!empty($mode)) $data = $mode1;
        }


        $model = model('app\common\model\ChannelGroup');
        if(!empty($get['search']['title'])){
            $title = trim($get['search']['title']);
            $id =  $model->where([
                ['title','like',"{$title}%"],
                ['status','=',1],
            ])->column('p_id');

            if(!empty($id))  $data = $id;
        }

        $update = [];

        $ids = $model->where([['p_id','in',$data]])->column('id');
        foreach ($ids as $k1 => $v2){
            $update[$k1]['id'] = $v2;
            $update[$k1]['sort'] = 1;
        }


        //使用事物保存数据
        $model->startTrans();
        $save = $model->saveAll($update);
        if (!$save) {
            $model->rollback();
            empty($msg) && $msg = '数据有误，请稍后再试！';
            return __error($msg);
        }
        $model->commit();

        empty($msg) && $msg = '顶置成功！';
        return __success($msg);

    }



}