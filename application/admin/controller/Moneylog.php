<?php
namespace app\admin\controller;

use app\common\controller\AdminController;

use app\common\model\Umoney;
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
            'type'  => config('money.'),
        ];

        return $this->fetch('', $basic_data);
    }

    /**
     * 下载
     * @return void
     */
    public function export_index(){

        $field = [
            'id',
            'uid',
            'before_balance',
            'change',
            'balance',
            'remark',
            'relate',
            'create_at',
            'type2',
            'type',
            'create_by'
        ];

        $title = [
            'id'=>'ID',
            'uid'=>'商户号',
            'auth_title'=>'权限组',
            'nickname'=>'操作人',
            'title'=>'操作类型',
            'before_balance'=>'变动前金额',
            'change'=>'变动金额',
            'balance'=>'变动后金额',
            'remark'=>'备注',
            'relate'=>'关联',
            'create_at'=>'创建时间',
        ];

        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 3000);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 0;
            $search['field'] = $field;
            return json($this->model->aList($page, $limit, $search));
        }
        $field[] =  'title';
        $field[] = 'nickname';
        $field[] = 'auth_title';


        //基础数据
        $basic_data = [
            'title'  => '会员流水列表',
            'url'  =>request() -> url(),
            'data'   => ['field'=>json_encode($field),'title'=>json_encode($title)],
        ];

        return $this->fetch('common@export/index', $basic_data);
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
            'type'  => config('money.'),
        ];

        return $this->fetch('', $basic_data);
    }

    /**
     * 下载
     * @return void
     */
    public function export_channel(){


        $field = [
            'id',
            'channel_id',
            'before_balance',
            'change',
            'balance',
            'remark',
            'relate',
            'create_at',
            'type2',
            'type',
            'create_by'
        ];

        $title = [
            'id'=>'ID',
            'channel_id'=>'通道ID',
            'auth_title'=>'权限组',
            'nickname'=>'操作人',
            'title'=>'操作类型',
            'before_balance'=>'变动前金额',
            'change'=>'变动金额',
            'balance'=>'变动后金额',
            'remark'=>'备注',
            'relate'=>'关联',
            'create_at'=>'创建时间',
        ];

        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 3000);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 1;
            $search['field'] = $field;
            return json($this->model->aList($page, $limit, $search));
        }
        $field[] =  'title';
        $field[] = 'nickname';
        $field[] = 'auth_title';


        //基础数据
        $basic_data = [
            'title'  => '通道流水列表',
            'url'  =>request() -> url(),
            'data'   => ['field'=>json_encode($field),'title'=>json_encode($title)],
        ];

        return $this->fetch('common@export/index', $basic_data);
    }

    /**
     * 代付通道流水
     * @return mixed
     */
    public function df()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 3;
            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '代付通道流水列表',
            'type'  => config('money.'),
        ];

        return $this->fetch('', $basic_data);
    }


    /**
     * 下载
     * @return void
     */
    public function export_df(){


        $field = [
            'id',
            'df_id',
            'before_balance',
            'change',
            'balance',
            'remark',
            'relate',
            'create_at',
            'type2',
            'type',
            'create_by'
        ];

        $title = [
            'id'=>'ID',
            'df_id'=>'代付通道ID',
            'auth_title'=>'权限组',
            'nickname'=>'操作人',
            'title'=>'操作类型',
            'before_balance'=>'变动前金额',
            'change'=>'变动金额',
            'balance'=>'变动后金额',
            'remark'=>'备注',
            'relate'=>'关联',
            'create_at'=>'创建时间',
        ];

        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 3000);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 3;
            $search['field'] = $field;
            return json($this->model->aList($page, $limit, $search));
        }
        $field[] =  'title';
        $field[] = 'nickname';
        $field[] = 'auth_title';


        //基础数据
        $basic_data = [
            'title'  => '代付通道流水列表',
            'url'  =>request() -> url(),
            'data'   => ['field'=>json_encode($field),'title'=>json_encode($title)],
        ];

        return $this->fetch('common@export/index', $basic_data);
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
            'type'  => config('money.'),
        ];

        return $this->fetch('', $basic_data);
    }

    /**
     * 下载
     * @return void
     */
    public function export_sys(){


        $field = [
            'id',
            'before_balance',
            'change',
            'balance',
            'remark',
            'relate',
            'create_at',
            'type2',
            'type',
            'create_by'
        ];

        $title = [
            'id'=>'ID',
            'auth_title'=>'权限组',
            'nickname'=>'操作人',
            'title'=>'操作类型',
            'before_balance'=>'变动前金额',
            'change'=>'变动金额',
            'balance'=>'变动后金额',
            'remark'=>'备注',
            'relate'=>'关联',
            'create_at'=>'创建时间',
        ];

        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 3000);
            $search = (array)$this->request->get('search', []);
            $search['type1'] = 2;
            $search['field'] = $field;
            return json($this->model->aList($page, $limit, $search));
        }
        $field[] =  'title';
        $field[] = 'nickname';
        $field[] = 'auth_title';


        //基础数据
        $basic_data = [
            'title'  => '平台流水列表',
            'url'  =>request() -> url(),
            'data'   => ['field'=>json_encode($field),'title'=>json_encode($title)],
        ];

        return $this->fetch('common@export/index', $basic_data);
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


    /**
     * 下载
     * @return void
     */
    public function export_index_money(){

        $field = [
                'id',
               'uid',
               'total_money',
               'balance',
               'df',
               'artificial',
               'frozen_amount',
               'frozen_amount_t1',
               'update_at',
        ];

        $title = [
                 'id'=>'ID',
                   'uid'=>'商户号',
                   'total_money'=>'总金额',
                   'balance'=>'可用余额',
                   'df'=>'代付金额',
                   'artificial'=>'人工冻结金额',
                   'frozen_amount'=>'冻结金额',
                   'frozen_amount_t1'=>'T1冻结金额',
                   'update_at'=>'最近更新时间',
        ];


        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 3000);
            $search = (array)$this->request->get('search', []);
            $search['field'] = $field;
            return json(model('app\common\model\Umoney')->aList($page, $limit, $search,0));
        }


        //基础数据
        $basic_data = [
            'title'  => '商户资金',
            'url'  =>request() -> url(),
            'data'   => ['field'=>json_encode($field),'title'=>json_encode($title)],
        ];

        return $this->fetch('common@export/index', $basic_data);
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

    //代付通道资金
    public function df_money()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            return json(model('app\common\model\Umoney')->aList($page, $limit, $search,2));
        }

        //基础数据
        $basic_data = [
            'title' => '代付通道资金',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }

    public function sys_money()
    {
        $Umoney = (new Umoney());
        $user =$Umoney->where(['uid'=>0,'channel_id'=>0,'id'=>1])->field('id,uid,balance,total_money,frozen_amount,frozen_amount_t1,artificial,channel_id,df')->find();
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
            $add = (new UmoneyLog())->saveAll($res['change']);

            if (!$save || !$add) {
                $Umoney->rollback();
                __log($res['log'].'失败');
                return __error('数据有误，请稍后再试！');
            }
            $Umoney->commit();

            __log($res['log'].'成功');
            return __success('操作成功');
        }
    }

}
