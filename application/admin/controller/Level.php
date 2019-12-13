<?php


namespace app\admin\controller;

use app\common\controller\AdminController;


/**用户等级
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
     * 等级列表
     */
    public function index() {
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '系统等级列表',
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
     * 添加等级
     * @return mixed|\think\response\Json
     */
    public function add() {
        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '添加等级',
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->only('title,remark');

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Level.add');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            //使用事物保存数据
            $this->model->startTrans();
            $channel = $this->model->save($post);

            if (!$channel || !$this->model->id ) {
                $this->model->rollback();

                empty($msg) && $msg = '数据有误，请稍后再试！!';
                return __error($msg);
            }

            $p_id = \app\common\model\PayProduct::column('id');
            foreach ($p_id as $k => $val){
                //添加用户等级费率
                $data[$k]['type'] = 0;
                $data[$k]['p_id'] = $val;
                $data[$k]['uid'] = $this->model->id;
            }
           if(!empty($data)) model('app\common\model\SysRate')->saveAll($data);

            $this->model->commit();
            empty($msg) && $msg = '添加成功!';
            return __success($msg);

        }


    }

    /**
     * 修改等级
     * @return mixed|string|\think\response\Json
     */
    public function edit() {
        if (!$this->request->isPost()) {

            //查找所需修改等级
            $auth = $this->model->where('id', $this->request->get('id'))->find();
            if (empty($auth)) return msg_error('暂无数据，请重新刷新页面！');

            //基础数据
            $basic_data = [
                'title' => '修改等级信息',
                'auth'  => $auth,
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->only('id,title,remark');

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Level.edit');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            $result = $this->model->__edit($post);

            $res1 = json_decode($result->getContent(),true);
            if($res1['code'] == 1){
                $p_id = \app\common\model\PayProduct::column('id');
                $p_id1 = \app\common\model\SysRate::where('uid', $post['id'])->column('p_id');

                $intersection = array_diff($p_id,$p_id1);
                $data = [];
                foreach ($intersection as $k => $val){
                    $data1['p_id'] = $val;
                    $data1['uid'] =   $post['id'];
                    $data[] = $data1;
                }

                //添加支付产品
                if(!empty($data)) model('app\common\model\SysRate')->saveAll($data);
            }


            return $result;

        }
    }



    /**
     * 获取所有已创建的通道分组费率
     * @param $uid
     * @return array
     */
    public function get_subpro($uid) {
        //获取所有已创建的支付产品
        $p_id = md5('app\common\model\SysRate')->where('uid', $uid)->column('p_id');
        return $p_id;
    }



    /**
     * 表单模板
     * @return mixed
     */
    protected function form() {
        return $this->fetch('form');
    }

    /**
     * 删除等级
     * @return \think\response\Json
     * @throws \Exception
     */
    public function del() {
        $get = $this->request->get();

        //验证数据
        if (!is_array($get['id'])) {
            $validate = $this->validate($get, 'app\common\validate\Auth.del');
            if (true !== $validate) return __error($validate);
        }

        //执行更新操作操作
        if (!is_array($get['id'])) {
            $del = $this->model->where('id', $get['id'])->delete();
            model('app\common\model\UlevelNode')->where('auth', $get['id'])->delete();
        } else {
            $del = $this->model->whereIn('id', $get['id'])->delete();
            model('app\common\model\UlevelNode')->whereIn('auth', $get['id'])->delete();
        }

        if ($del >= 1) {

            return __success('删除成功！');
        } else {
            return __error('数据有误，请刷新重试！');
        }
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
     * 更改等级状态
     * @return \think\response\Json
     */
    public function status() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Auth.status');
        if (true !== $validate) return __error($validate);

        //判断菜单状态
        $status = $this->model->where('id', $get['id'])->value('status');
        $status == 1 ? list($msg, $status) = ['等级禁用成功', $status = 0] : list($msg, $status) = ['等级启用成功', $status = 1];

        //执行更新操作操作
        $update =  $this->model->__edit(['status' => $status,'id' => $get['id']],$msg);

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