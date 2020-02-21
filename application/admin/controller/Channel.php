<?php
namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\model\ChannelGroup;
use app\common\model\PayProduct;
use app\common\model\Ulevel;
use app\common\model\Umoney;
use app\common\model\Uprofile;

/**
 * 通道管理
 * Class Channel
 * @package app\admin\controller
 */
class Channel  extends AdminController
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
        $this->model = model('app\common\model\Channel');
    }

    //通道列表
    public function index()
    {

        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                return json($this->model->cList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '支付通道列表',
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
            $result = $this->model->editField($post);

            return $result;

        }
    }

    /**
     * 更改支付通道状态
     * @return \think\response\Json
     */
    public function status() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Channel.status');
        if (true !== $validate) return __error($validate);

        //判断菜单状态
        $status = $this->model->where('id', $get['id'])->value('status');
        $status == 1 ? list($msg, $status) = ['支付通道禁用成功', $status = 0] : list($msg, $status) = ['支付通道启用成功', $status = 1];

        //是通道 还是通道本身
        $pid = $this->model->where(['id'=>$get['id']])->value('pid');


        $data = [];
        $channel_id = [];

        $data[] = ['id'=>$get['id'],'status'=>$status];
        if($pid == 0 ){
            //是通道
            if($status == 0){
                //关闭
                $child = $this->model->where(['pid'=>$get['id'],'status'=>1])->column('id');
                foreach ($child as $k=>$v){
                    $data[] = ['id'=>$v,'status'=>0];
                    $channel_id[] = $v;
                }
            }
        }else{
            //是通道产品
            if($status == 0)  $channel_id[] = $get['id'];
        }

        //使用事物保存数据
        $this->model->startTrans();
        $save = $this->model->saveAll($data);

        $del = true;
        if($status == 0 && !empty($channel_id) ){
            $del = model('app\common\model\ChannelProduct')->destroy(function($query) use ($channel_id){
                $query->where([['channel_id','in',$channel_id]]);
            });
        }
        if (!$save || !$del) {
            $this->model->rollback();
            $msg = '数据有误，请稍后再试！';
            return __error($msg);
        }
        $this->model->commit();

        return __success($msg);
    }




    public function top() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Channel.sort');
        if (true !== $validate) return __error($validate);

        //判断菜单状态

        $get['sort'] == 2 && $msg = '置顶成功';
        $get['sort'] == 0 && $msg = '置后成功';

        //执行更新操作操作
        $update =  $this->model->__edit(['sort' => $get['sort'],'id' => $get['id']],$msg);

        return $update;
    }

    
    /**
     * 添加支付通道
     * @return mixed|\think\response\Json
     */
    public function add() {
        if (!$this->request->isPost()) {

            //产品列表
            $product = \app\common\model\PayProduct::idArr();

            //基础数据
            $basic_data = [
                'title' => '添加支付通道',
                'auth'  => [],
                'product' => $product
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->post();

            if(!empty($post['secretkey']))  $post['secretkey'] = $this->check_secretkey($post['secretkey']);

            if(!empty($post['back_ip'])){
             $res =   $this->check_ip($post['back_ip']);
             if ($res['code'] == 0) return __error($res['msg']);
                $post['back_ip'] = $res['data'];
            }

            $p_id =  $this->request->post('p_id',[]);
            $post['p_id'] = json_encode($p_id);


            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Channel.add');
            if (true !== $validate) return __error($validate);
            unset($post['__token__']);

            //保存数据,返回结果
            //使用事物保存数据
            $this->model->startTrans();
            $channel = $this->model->create($post);

            if(empty($channel) || !isset($channel['id'])){
                $this->model->rollback();
                return __error('数据有误，请稍后再试！!');
            }

            //创建金额账户
            $Umoney =  Umoney::create(['channel_id'=>$channel['id'],'uid'=>0]);

            $data = [];
            foreach ($p_id as $k => $val){
                $data[$k]['p_id'] = json_encode([$val]);
                $data[$k]['pid'] = $this->model->id;
                $data[$k]['title'] = $post['title'];
            }
            //添加支付产品
           if(!empty($data)) $res = $this->model->isUpdate(false)->saveAll($data);

            if(empty($res) || empty($Umoney)){
                $this->model->rollback();
                return __error('数据有误，请稍后再试！!');
            }

            $this->model->commit();
            return __success('添加成功!');

        }
    }


    /**
     * 修改通道
     * @return mixed|string|\think\response\Json
     */
    public function edit() {
        if (!$this->request->isPost()) {

            //查找所需修改支付通道
            $auth = $this->model->where('id', $this->request->get('id'))->find();
            if (empty($auth)) return msg_error('暂无数据，请重新刷新页面！');

            if(!empty($auth['secretkey'])){
                $data = json_decode($auth['secretkey']);
                $str = '';
                foreach ($data as $k => $v){
                    $str.= $k.'|'.$v."\n";
                }

                $auth['secretkey'] = $str;
            }

            !empty($auth['back_ip']) && $auth['back_ip'] = implode("\n",json_decode($auth['back_ip']));


            //获取所有已创建的支付产品
            $auth['p_id'] = $this->get_subpro($auth['id']);


            //产品列表
            $product = \app\common\model\PayProduct::idArr();

            //基础数据
            $basic_data = [
                'title' => '修改支付通道信息',
                'auth'  => $auth,
                'product' => $product
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->post();
            //自定义密钥
            if(!empty($post['secretkey']))  $post['secretkey'] = $this->check_secretkey($post['secretkey']);
            //回调IP
            if(!empty($post['back_ip'])){
                $res =   $this->check_ip($post['back_ip']);
                if ($res['code'] == 0) return __error($res['msg']);
                $post['back_ip'] = $res['data'];
            }
            //支付产品
            $p_id =  $post['p_id'];
            if(empty($post['p_id']))  $p_id = [];
            $post['p_id'] = json_encode($p_id);

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Channel.edit');
            if (true !== $validate) return __error($validate);


            //保存数据,返回结果
            $result =  $this->model->__edit($post);

            $res1 = json_decode($result->getContent(),true);
            if($res1['code'] == 1){

                $data1 = $this->get_subpro($post['id']);
                $intersection = array_diff($p_id,$data1);
                $data = [];
                foreach ($intersection as $k => $val){
                    $data1['p_id'] = json_encode([$val]);
                    $data1['pid'] =   $post['id'];
                    $data1['title'] = $post['title'];
                    $data[] = $data1;
                }
                //添加支付产品
               if(!empty($data)) $this->model->saveAll($data);
            }


            return $result;
        }
    }

    /**
     * 检验回调IP
     * @param $ips
     * @return array
     */
    public function check_ip($back_ip) {
        $ips =  explode("\n",trim($back_ip));
        $data = [];
        foreach ($ips as $k => $val){
            $ip = trim($val);

            if($ip == '*'){
                unset($data);
                $data[0] = $ip;
                break;
            }

            //验证数据ip
            $validate = $this->validate(array('ip'=>$ip), 'app\common\validate\Common.check_ip');
            if (true !== $validate) return ['code' => 0, 'msg' => "$ip ".$validate, 'data' => array()];
            $data[$k] = $ip;

        }
        $res = json_encode(array_unique($data));

        return ['code' => 1, 'msg' => '', 'data' => $res];
    }

    /**
     * 处理自定义密钥
     * @param $secretkey
     * @return array
     */
    public function check_secretkey($secretkey) {
        $sec =  explode("\n",trim($secretkey));
        $data = [];
        foreach ($sec as $k => $v){
            $v1 = trim($v);
            if(empty($v1)) continue;

            list($key,$val) = explode('|', $v1);
            $data[$key] = $val;
        }

        $res = json_encode(array_unique($data));

        return $res;
    }



    /**
     * 添加支付产品
     * @return mixed|\think\response\Json
     */
    public function product() {
        if (!$this->request->isPost()) {
            //查找所需支付通道
            $auth = $this->model->where(['id'=>$this->request->get('pid'),'pid' => 0])->field('id,title')->find();
            if (empty($auth)) return msg_error('暂无数据，请重新刷新页面！');

            //产品列表
            $product = \app\common\model\PayProduct::idArr();

            //基础数据
            $basic_data = [
                'title' => '添加支付产品',
                'product' => $product,
            ];
            $this->assign($basic_data);

            return $this->pform();
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Channel.padd');
            if (true !== $validate) return __error($validate);
            $post['p_id'] = json_encode([$post['p_id']]);

                //保存数据,返回结果
            $result = model('app\common\model\Channel')->__add($post);

            $res1 = json_decode($result->getContent(),true);
            if($res1['code'] == 1){

                $data['id'] =  $post['pid'];
                $p_id = $this->get_subpro($post['pid']);
                $data['p_id'] = json_encode($p_id);
                //更新通道数据
                $this->model->save($data,['id'=> $data['id']]);

            }

            return $result;
        }
    }

    /**
     * 获取所有已创建的支付产品
     * @param $pid
     * @return array
     */
    public function get_subpro($pid) {
        //获取所有已创建的支付产品
        $p_id = $this->model->where('pid', $pid)->field('p_id')->select();
        $data1 = [];
        foreach ($p_id as  $val){
            $data1[] = json_decode($val['p_id'])[0];
        }
        $data = array_unique($data1);
        return $data;
    }




    /**
     * 修改支付产品
     * @return mixed|string|\think\response\Json
     */
    public function product_edit() {
        if (!$this->request->isPost()) {

            //查找所需修改支付通道
            $auth = $this->model->where('id', $this->request->get('id'))->find();
            if (empty($auth)) return msg_error('暂无数据，请重新刷新页面！');

            //产品列表
            $product = \app\common\model\PayProduct::idArr();

            $auth['p_id'] = json_decode($auth['p_id'])[0];

            //基础数据
            $basic_data = [
                'title' => '修改支付通道信息',
                'data' => $auth,
                'product' => $product,
            ];


            $this->assign($basic_data);

            return $this->pform();
        } else {
            $post = $this->request->post();

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Channel.edit');
            if (true !== $validate) return __error($validate);
            $post['p_id'] = json_encode([$post['p_id']]);

            //保存数据,返回结果
            $result =  $this->model->__edit($post);

            $res1 = json_decode($result->getContent(),true);
            if($res1['code'] == 1){
                $data['id'] =  $post['pid'];
                $p_id = $this->get_subpro($post['pid']);
                $data['p_id'] = json_encode($p_id);
                //更新通道数据
                $this->model->save($data,['id'=> $data['id']]);
            }

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

    /**
     * 表单模板2
     * @return mixed
     */
    protected function pform() {
        return $this->fetch('pform');
    }

    /**
     * 删除支付通道
     * @return \think\response\Json
     * @throws \Exception
     */
    public function del() {
        $get = $this->request->get();
        if(is_array($get['id'])) return msg_error('数据错误，请重试');

        $validate = $this->validate($get, 'app\common\validate\Channel.del');
        if (true !== $validate) return __error($validate);


        //是通道产品 还是通道本身
        $pid = $this->model->where(['id'=>$get['id']])->value('pid');

        $data = [];
        $channel_id = [];

        $data[] =(int) $get['id'];
        if($pid == 0 ){
            $Umoney =  Umoney::quickGet(['channel_id'=>$get['id'],'uid'=>0]);
            if(!empty($Umoney) && ($Umoney['total_money'] > 0 || $Umoney['balance'] > 0 )) return __error('该通道有余额未清，不可删除。');

            //是通道
            $channel_id = $this->model->where(['pid'=>$get['id']])->column('id');
            $data = array_merge($data,$channel_id);
        }else{
            //是通道产品
            $channel_id[] = $get['id'];
        }

        //使用事物保存数据
        $this->model->startTrans();
        $save = $this->model->destroy($data);

        $del = true;
        if(!empty($channel_id) ){
            $del = model('app\common\model\ChannelProduct')->destroy(function($query) use ($channel_id){
                $query->where([['channel_id','in',$channel_id]]);
            });
        }
        $del1 = true;
       if(!empty($Umoney['id'])) $del1 =  model('app\common\model\Umoney')->destroy($Umoney['id']);//删除账户金额
        if (!$save || !$del || !$del1) {
            $this->model->rollback();
            return __error('数据有误，请稍后再试！');
        }
        $this->model->commit();
        return __success('删除成功');

    }



    /**
     * 更改通道手续费状态
     * @return \think\response\Json
     */
    public function inner() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Channel.inner');
        if (true !== $validate) return __error($validate);

        //判断菜单状态
        $status = $this->model->where('id', $get['id'])->value('inner');
        $status == 1 ? list($msg, $status) = ['内扣开启成功', $status = 0] : list($msg, $status) = ['外扣启用成功', $status = 1];

        //执行更新操作操作
        $update =  $this->model->__edit(['inner' => $status,'id' => $get['id']],$msg);
        return $update;
    }

    //通道访问产品方式
    public function visit() {
        $get = $this->request->get();

        $data = ['visit'=>(int)$get['value'],'id' => (int)$get['id']];
        if(!empty($get['verson'])) $data['verson'] = (int)$get['verson'];//锁

        //执行更新操作操作
        $update =  $this->model->__edit($data);

        return $update;
    }

    //测试链接
    public function test() {
        $get = $this->request->get();

         //第一步
        $pay_memberid = config('set.memberid');   //商户ID
        if(empty($pay_memberid)) __error('测试商户号不存在');
        $Uprofile =  Uprofile::quickGet(['uid'=>$pay_memberid]);
        if(empty($Uprofile)) __error('测试商户号不存在');

        $UlevelId = Ulevel::where(['title'=>'商户测试分组','type'=>0,'type1'=>0])->value('id');
        if(empty($UlevelId)) __error('商户测试分组不存在');

        dump($UlevelId);


        //支付通道分组
        $ChannelGroupId =  ChannelGroup::where(['title'=>'测试分组'])->value("id");
        if(empty($ChannelGroupId)) __error('测试分组不存在');

        halt($Uprofile['id']);
        //给商户分配到商户测试分组
        if($UlevelId != $Uprofile['group_id']){
            $Uprofile1['group_id'] = $UlevelId;
            $Uprofile1['id'] = $Uprofile['id'];
            model('app\common\model\Uprofile')->save($Uprofile1,['id'=>$Uprofile1['id']]);
        }

        $Channel = \app\common\model\Channel::quickGet($get['id']);

        $p_id = json_decode($Channel['p_id'])[0];

        //更新用户分组分配通道分组
        $Ulevel['id'] =  $UlevelId;
        $Ulevel['channel_id'] =  json_encode([
            $p_id=>[$ChannelGroupId],
        ]);
        model('app\common\model\Ulevel')->save($Ulevel,['id'=>$Ulevel['id']]);

        //更新通道分组属于哪个支付产品信息
        $ChannelGroup['id'] = $ChannelGroupId;
        $ChannelGroup['p_id'] = $p_id;
        $ChannelGroup['status'] = 1;
        model('app\common\model\ChannelGroup')->save($ChannelGroup,['id'=>$ChannelGroup['id']]);

        //删除通道分组和通道关联的数据
        model('app\common\model\ChannelProduct')->destroy(function($query) use ($ChannelGroupId){
            $query->where(['group_id'=>$ChannelGroupId]);
        });
        $ChannelProduct['p_id'] = $p_id;
        $ChannelProduct['group_id'] = $ChannelGroupId;
        $ChannelProduct['channel_id'] = $Channel['id'];
        model('app\common\model\ChannelProduct')->create($ChannelProduct);

        $token = mt_rand(1000,999999999).$this->user['username'];
        cache('pay_token'.$token,$token,60*30);//缓存token
        //支付编码
        $code = PayProduct::where(['id'=>$p_id])->value('code');

        //生成支付链接
        $url = $this->request->domain()."/pay.php/test/index.html?code={$code}&amount={$get['money']}&token={$token}";

       return __success($url);
    }



}
