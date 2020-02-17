<?php
namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\model\Uprofile;

/**
 * 支付通道分组
 * Class Cgroup
 * @package app\admin\controller
 */
class Cgroup  extends AdminController
{

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
        $this->model = model('app\common\model\ChannelGroup');
    }

    public function index()
    {

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
                'title'  => '通道分组列表',
                'data'   => '',
                'status' => [['id' => 1, 'title' => '启用'], ['id' => 0, 'title' => '禁用']],
            ];

            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only("id,field,value");

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_field');

            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            return $this->model->editField($post);
        }

    }

    /**
     * 是否隐藏客服端
     * @return \think\response\Json
     */
    public function cli() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\ChannelGroup.cli');
        if (true !== $validate) return __error($validate);

        //判断菜单状态
        $status = $this->model->where('id', $get['id'])->value('cli');
        $status == 1 ? list($msg, $status) = ['客户端隐藏成功', $status = 0] : list($msg, $status) = ['客户端显示成功', $status = 1];

        //执行更新操作操作
        $update =  $this->model->__edit(['cli' => $status,'id' => $get['id']],$msg);

        return $update;
    }


    //接口模式
    public function mode()
    {
      $g_id = $this->request->get('id', 0);
      $status =  $this->model->where(['id'=>$g_id])->value('status');

        if (!$this->request->isPost()) {
            if($status != 1) return msg_error('请先开启通道分组');

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 1000);
                $search = (array)$this->request->get('search', []);
                $search['p_id'] = $this->request->get('p_id', '');
                $search['g_id'] = $g_id;
                return json(model('app\common\model\Channel')->pList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '接口列表',
            ];

            return $this->fetch('', $basic_data);
        } else {
            if($status != 1) return __error('请先开启通道分组');

            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_field');
            if (true !== $validate) return __error($validate);

            //权重 和 并发 编辑
            if($post['field'] == 'weight' || $post['field'] == 'concurrent'){

                $ChannelProduct = model('app\common\model\ChannelProduct');

                $id = $ChannelProduct->where(['group_id'=>$g_id,'channel_id'=>$post['id']])->value('id');
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
     * 更改通道分组状态
     * @return \think\response\Json
     */
    public function status(){
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\ChannelGroup.status');
        if (true !== $validate) return __error($validate);

        //判断菜单状态
        $data = $this->model->quickGet($get['id']);
        $status = $data['status'];
        $status == 1 ? list($msg, $status) = ['通道分组禁用成功', $status = 0] : list($msg, $status) = ['通道分组启用成功', $status = 1];

        if($status == 0){
            //使用事物保存数据
            $this->model->startTrans();
            $save = $this->model->save(['status' => $status,'id' => $get['id']],['id'=>$get['id']]);

            //权重和并发表
            $del = model('app\common\model\ChannelProduct')->destroy(function($query) use ($get){
                $query->where(['group_id'=>$get['id']]);
            });

            //删除代理下商户分组选中的通道
            $res = \app\common\model\Ulevel::delChennelGroupID(0,$data['p_id'],$get['id']);

            if (!$save || !$del || !$res) {
                $this->model->rollback();
                $msg = '数据有误，请稍后再试！';
                return __error($msg);
            }
            $this->model->commit();

            return __success($msg);

        }else{
            //执行更新操作操作
            $update =  $this->model->__edit(['status' => $status,'id' => $get['id']],$msg);
            return $update;
        }


    }



    /**
     * 添加通道分组
     * @return mixed|\think\response\Json
     */
    public function add() {
        if (!$this->request->isPost()) {

            //产品列表
            $product = \app\common\model\PayProduct::idArr();

            //基础数据
            $basic_data = [
                'title' => '添加通道分组',
                'auth' => [],
                'product' => $product,
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\ChannelGroup.add');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            return $this->model->__add($post);
        }
    }

    /**
     * 修改
     * @return mixed|string|\think\response\Json
     */
    public function edit() {
        if (!$this->request->isPost()) {

            //查找所需修改通道分组
            $auth = $this->model->where('id', $this->request->get('id'))->find();
            if (empty($auth)) return msg_error('暂无数据，请重新刷新页面！');

            //产品列表
            $product = \app\common\model\PayProduct::idArr();

            //基础数据
            $basic_data = [
                'title' => '修改通道分组信息',
                'auth'  => $auth,
                'product' => $product,
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\ChannelGroup.edit');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            return $this->model->__edit($post);
        }
    }

    /**
     * 表单模板
     * @return mixed
     */
    protected function form() {
        return $this->fetch('form');
    }

    /**
     * 删除通道分组
     * @return \think\response\Json
     * @throws \Exception
     */
    public function del() {
        $get = $this->request->get();


        //验证数据
        if (!is_array($get['id'])) {
            $validate = $this->validate($get, 'app\common\validate\ChannelGroup.del');
            if (true !== $validate) return __error($validate);
        }else{
            foreach ($get['id'] as $k => $val){
                $data['id'] = $val;
                $validate = $this->validate($data, 'app\common\validate\ChannelGroup.del');
                if (true !== $validate) unset($get['id'][$k]);
            }
        }

        //使用事物保存数据
        $this->model->startTrans();
        $del1 = $this->model->destroy($get['id']);

        //删除关联数据
        $del = model('app\common\model\ChannelProduct')->destroy(function($query) use ($get){
            $query->where(['group_id'=>$get['id']]);
        });

        if (!$del1 || !$del) {
            $this->model->rollback();
            $msg = '数据有误，请稍后再试！';
            return __error($msg);
        }
        $this->model->commit();
        return __success('删除成功');
    }

    /**
     * 确认保存通道
     * @return \think\response\Json
     * @throws \Exception
     */
    public function confirm() {
        $get = $this->request->get();

        if(empty($get['pid'])) return __error('请选择通道！');
        $p_id = $this->model->where(['id'=>$get['pid']])->value('p_id');
        if(empty($p_id)) return __error('该通道未选着支付产品！');

        $ChannelProduct = model('app\common\model\ChannelProduct');

        $mode = [];
        $id = [];
        //验证数据
        if (isset($get['id'])) {
            if (!is_array($get['id'])) {
                $validate = $this->validate($get, 'app\common\validate\Channel.del');
                if (true !== $validate) return __error($validate);
                $temp = ['channel_id'=>$get['id'],'group_id'=>$get['pid'],'p_id'=>$p_id];
                $find = $ChannelProduct->where($temp)->find();
                if(!empty($find)){
                    $temp = $find->toArray();
                    $id[] = $temp['id'];
                }


                $mode[] = $temp;
            }else{
                foreach ($get['id'] as $k => $val){
                    $data['id'] = $val;
                    $validate = $this->validate($data, 'app\common\validate\Channel.del');
                    if (true !== $validate){
                        unset($get['id'][$k]);
                        continue;
                    }
                    $temp = ['channel_id'=>$val,'group_id'=>$get['pid'],'p_id'=>$p_id];
                    $find = $ChannelProduct->where($temp)->find();
                    if(!empty($find)){
                        $temp = $find->toArray();
                        $id[] = $temp['id'];
                    }
                    $mode[] = $temp;
                }
            }
        }

        if(empty($mode)){
            //使用事物保存数据
            $ChannelProduct->startTrans();
            $del = $ChannelProduct->destroy(function($query) use ($get){
                $query->where(['group_id'=>$get['pid']]);
            });
            if (!$del) {
                $ChannelProduct->rollback();
                $msg = '数据有误，请稍后再试！';
                return __error($msg);
            }
            $ChannelProduct->commit();

            empty($msg) && $msg = '保存成功';
            return __success($msg);
        }else{

            //使用事物保存数据
            $ChannelProduct->startTrans();

            $del = $ChannelProduct->destroy(function($query) use ($get,$id){
                $query->where(['group_id'=>$get['pid']])->whereNotIn('id',$id);
            });

            $save = $ChannelProduct->saveAll($mode);
            if (!$save || !$del) {
                $ChannelProduct->rollback();
                $msg = '数据有误，请稍后再试！';
                return __error($msg);
            }
            $ChannelProduct->commit();

            empty($msg) && $msg = '保存成功';
            return __success($msg);
        }


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
            $mode = model('app\common\model\ChannelProduct')->where(['group_id'=>$get['id']])->column('channel_id');
           if(!empty($mode)) $data = $mode;
        }

        $model = model('app\common\model\Channel');

        if(!empty($get['search']['title'])){
            $title = trim($get['search']['title']);
            $id =  $model->where([
                ['title','like',"{$title}%"],
                ['pid','>',0],
                ['status','=',1],
            ])->column('id');

          if(!empty($id))  $data = $id;
        }

        $update = [];
        foreach ($data as $k => $v){
            $update[$k]['id'] = $v;
            $update[$k]['sort'] = 1;
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
