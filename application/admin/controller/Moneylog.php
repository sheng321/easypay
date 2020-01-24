<?php
namespace app\admin\controller;

use app\common\controller\AdminController;

use app\common\model\UmoneyLog;

/**  金额流水
 * Class Log
 * @package app\admin\controller
 */
class Moneylog  extends AdminController
{

    /**
     * User模型对象
     */
    protected $model = null;


    /**
     * 初始化
     * User constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->model = new UmoneyLog();
    }

    /**
     * 会员流水
     * @return mixed
     */
    public function index()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 0;
            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '会员流水列表',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }


    /**
     * 通道流水
     * @return mixed
     */
    public function channel()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 1;
            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '通道流水列表',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }


    /**
     * 平台流水
     * @return mixed
     */
    public function sys()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 2;
            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '平台流水列表',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }


   //会员资金
    public function index_money()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            return json(model('app\common\model\Umoney')->aList($page, $limit, $search,0));
        }

        //基础数据
        $basic_data = [
            'title' => '商户资金',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }

    //通道资金
    public function channel_money()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            return json(model('app\common\model\Umoney')->aList($page, $limit, $search,1));
        }

        //基础数据
        $basic_data = [
            'title' => '通道资金',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }

    public function sys_money()
    {
        $Umoney =  model('app\common\model\Umoney');
        $user =$Umoney->where(['uid'=>0,'channel_id'=>0,'id'=>1])->field('id,uid,balance,total_money,frozen_amount,frozen_amount_t1,artificial,channel_id')->find();
        if(empty($user)) return msg_error('系统异常，请通知技术');
        $user = $user->toArray();

        if (!$this->request->isPost()){
            //基础数据
            $basic_data = [
                'title' => '平台资金',
                'status' => [3=>'添加',4=>'扣除',9=>'人工冻结',10=>'人工解冻'],
                'user'  => $user,//平台资金
            ];
            return $this->fetch('', $basic_data);
        } else {
            $money = $this->request->only('remark,change,type,__token__','post');

            //验证数据
            $validate = $this->validate($money, 'app\common\validate\Money.edit');
            if (true !== $validate) return __error($validate);
            unset($money['__token__']);

            //处理金额
            $res =  $Umoney->dispose($user,$money);

            if (true !== $res['msg']) return __error($res['msg']);

            //使用事物保存数据
            $Umoney->startTrans();

            $save = $Umoney->saveAll($res['data']);
            $add = model('app\common\model\UmoneyLog')->saveAll($res['change']);

            if (!$save || !$add) {
                $Umoney->rollback();
                $msg = '数据有误，请稍后再试！';
                __log($res['log'].'失败');
                return __error($msg);
            }
            $Umoney->commit();

            __log($res['log'].'成功');
            empty($msg) && $msg = '操作成功';
            return __success($msg);
        }




        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 0;
            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '平台资金',
            'status' => [9=>'人工冻结',10=>'人工解冻',3=>'添加',4=>'扣除'],
            'data'  => '',
        ];
        return $this->fetch('', $basic_data);
    }

}
