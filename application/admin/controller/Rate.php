<?php


namespace app\admin\controller;

use app\common\controller\AdminController;


/**用户费率
 * Class Level
 * @package app\admin\controller
 */
class Rate extends AdminController{

    /**
     * Rate模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * node constructor.
     */
    public function __construct() {
        parent::__construct();
        
        $this->model = model('app\common\model\SysRate');
    }

    /**
     * 平台商户分组费率列表
     */
    public function index(){
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = 0;
                $search['uid'] = 0;
                $search['channel_id'] = 0;
                if(!empty($search['title'])){
                    $uid =  model('app\common\model\Ulevel')->where([['title','like','%'.$search['title'].'%']])->value('id');
                    if(!empty($uid)) $search['group_id'] = $uid;
                    unset($search['title']);
                }
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '平台商户分组费率列表',
                'data'   => '',
                'status' => [['id' => 1, 'title' => '启用'], ['id' => 0, 'title' => '禁用']],
            ];

            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only('id,field,value');
            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_rate');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            return $this->model->editField($post);
        }
    }

    //代理的商户分组费率
    public function agent_user(){

        if (!$this->request->isPost()) {
            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = 1;
                $search['channel_id'] = 0;
                if(!empty($search['title'])){
                    $uid =  model('app\common\model\Ulevel')->where([['title','like','%'.$search['title'].'%']])->value('id');
                    if(!empty($uid)) $search['group_id'] = $uid;
                    unset($search['title']);
                }
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '平台商户分组费率列表',
                'data'   => '',
                'status' => [['id' => 1, 'title' => '启用'], ['id' => 0, 'title' => '禁用']],
            ];

            return $this->fetch('', $basic_data);
        }
    }


    /**
     * 平台代理分组费率列表
     */
    public function agent(){
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = 0;
                $search['uid'] = 0;
                $search['p_id'] = 0;
                if(!empty($search['title'])){
                    $uid =  model('app\common\model\Ulevel')->where([['title','like','%'.$search['title'].'%']])->value('id');
                    if(!empty($uid)) $search['group_id'] = $uid;
                    unset($search['title']);
                }
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '平台代理分组费率列表',
                'data'   => '',
                'status' => [['id' => 1, 'title' => '启用'], ['id' => 0, 'title' => '禁用']],
            ];

            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only('id,field,value');
            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_rate');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            return $this->model->editField($post);
        }
    }


    //代理的代理分组
    public function agent_agent(){
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = 1;
                $search['p_id'] = 0;
                if(!empty($search['title'])){
                    $uid =  model('app\common\model\Ulevel')->where([['title','like','%'.$search['title'].'%']])->value('id');
                    if(!empty($uid)) $search['group_id'] = $uid;
                    unset($search['title']);
                }
                return json($this->model->aList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '平台代理分组费率列表',
                'data'   => '',
                'status' => [['id' => 1, 'title' => '启用'], ['id' => 0, 'title' => '禁用']],
            ];

            return $this->fetch('', $basic_data);
        }
    }


    /**
     * 个人用户费率列表
     */
    public function user(){
        if (!$this->request->isPost()) {

            //ajax访问获取数据
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                $search['type'] = 2;
                if(!empty($search['title'])){
                    $search['uid'] = $search['title'];
                    unset($search['title']);
                }

                return json($this->model->uList($page, $limit, $search));
            }

            //基础数据
            $basic_data = [
                'title'  => '个人用户费率列表',
                'data'   => '',
                'status' => [['id' => 1, 'title' => '启用'], ['id' => 0, 'title' => '禁用']],
            ];

            return $this->fetch('', $basic_data);
        } else {
            $post = $this->request->only('id,field,value');
            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Common.edit_rate');
            if (true !== $validate) return __error($validate);

            //保存数据,返回结果
            return $this->model->editField($post);
        }
    }


    /**
     * 添加费率
     * @return mixed|\think\response\Json
     */
    public function add() {
        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '添加费率',
            ];
            $this->assign($basic_data);

            return $this->form();
        } else {
            $post = $this->request->only('uid,__token__');

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Rate.add');
            if (true !== $validate) return __error($validate);


            $p_id = \app\common\model\PayProduct::where(['cli'=>0])->column('id');
            $p_id1 = \app\common\model\SysRate::where([
                [ 'uid','=',$post['uid']],
                [ 'type','=',2]
            ])->column('p_id');

            $intersection = array_diff($p_id,$p_id1);
            if(empty($intersection))  return __error("已添加，请勿重复操作");
            //交集
            if ($intersection !== array_intersect($intersection, $p_id)) return __error("请删除不存在的支付产品数据！");


            $data = [];
            foreach ($intersection as $k => $val){
                $data1['p_id'] = $val;
                $data1['uid'] =  $post['uid'];
                $data1['type'] =  2;
                $data1['rate'] = \app\common\service\RateService::getMemRate($post['uid'],$val);
                $data[] = $data1;
            }

            //使用事物保存数据
            $this->model->startTrans();
            $save = $this->model->saveAll($data);
            if (!$save) {
                $this->model->rollback();
                $msg = '数据有误，请稍后再试！!';
                return __error($msg);
            }
            $this->model->commit();
            empty($msg) && $msg = '添加成功!';
            return __success($msg);
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
     * 删除费率
     * @return \think\response\Json
     * @throws \Exception
     */
    public function del() {
        $get = $this->request->get();

        //验证数据
        if (!is_array($get['id'])) {
            $validate = $this->validate($get, 'app\common\validate\Rate.del');
            if (true !== $validate) return __error($validate);
        }else{
            foreach ($get['id'] as $k => $val){
                $data['id'] = $val;
                $validate = $this->validate($data, 'app\common\validate\Rate.del');
                if (true !== $validate) unset($get['id'][$k]);
            }
        }

        //执行操作
        $del = $this->model->__del($get);
        return $del;
    }


    /**
     * 更改费率状态
     * @return \think\response\Json
     */
    public function status() {
        $get = $this->request->get();

        //验证数据
        $validate = $this->validate($get, 'app\common\validate\Rate.status');
        if (true !== $validate) return __error($validate);

        //判断菜单状态
        $status = $this->model->where('id', $get['id'])->value('status');
        $status == 1 ? list($msg, $status) = ['费率禁用成功', $status = 0] : list($msg, $status) = ['费率启用成功', $status = 1];

        //执行更新操作操作
        $update =  $this->model->__edit(['status' => $status,'id' => $get['id']],$msg);

        return $update;
    }


}