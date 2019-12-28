<?php


namespace app\agent\controller;
use app\common\controller\AgentController;
use think\Db;
class Withdrawal extends AgentController {
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
        $this->model = model('app\common\model\Withdrawal');
    }
    /**
     * Undocumented 银行卡
     *
     * @return void
     */
    public function bank(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->get('search', []);
            $count = Db::name("bank_card")->where(array_filter($search))->count();
            $list = Db::name("bank_card")->where(array_filter($search))->limit(($page-1),$limit)->select();
            empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';
            $data = [
                'code'  => 0,
                'msg'   => $msg,
                'count' => $count,
                'info'  => ['limit'=>$limit,'page_current'=>$page,'page_sum'=>ceil($count / $limit)],
                'data'  => $list,
            ];
            return json($data);
        }
        return view("withdrawal/bank");
    }
    /**
     * Undocumented 添加/编辑银行卡
     *
     * @return void
     */
    public function saveBark(){
        $info = array();
        if($this->request->isPost()){
            $data = $this->request->param();
            unset($data['bank_id']);unset($data['__token__']);
            if(!empty($this->request->param('bank_id'))){//编辑
                $result = Db::name("bank_card")->where("id",$this->request->param('bank_id'))->update($data);
            }else{//新增
                $data['mch_id'] = $this->user['uid'];
                $result = Db::name("bank_card")->insert($data);
            }
            if($result){
                return __success('操作成功');
            }
            return __error('操作失败');
        }else{//查看
            $info = Db::name("bank_card")->where("id",$this->request->param('id'))->find();
        }
        $this->assign("info",$info);
        return view("withdrawal/save_bark");
    }
    /**
     * Undocumented 删除银行卡
     *
     * @return void
     */
    public function delBank(){
        $id = $this->request->param('id');
       if(!is_array($id)){
            $info = Db::name("bank_card")->where("id",$id)->find();
            if(empty($info)) return __error('数据不存在');
       }
        $result = Db::name("bank_card")->where("id",'in',$id)->delete();
        if($result){
            return __success('删除成功');
        }
        return __error('删除失败');
    }
    /**
     * Undocumented 提现记录
     *
     * @return void
     */
    public function index(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->get('search', []);
            return json($this->model->list($page, $limit, $search,$this->user['uid']));
        }
        return view('index');
    }
    /**
     * Undocumented 申请提现
     *
     * @return void
     */
    public function addWithdrawal(){
        if($this->request->isPost()){//提现申请
            $data = $this->request->param();
            //获取提现配置
            $withdrawal = config("custom.withdrawal");
            //判断金额
            if(!empty(abs($withdrawal['min_amount'])) && abs($data['total_amount']) < abs($withdrawal['min_amount'])){
                return __error('单笔提现金额最低'.abs($withdrawal['min_amount']).'元起');
            }
            if(!empty(abs($withdrawal['max_amount'])) && abs($data['total_amount']) > abs($withdrawal['max_amount'])){
                return __error('单笔提现金额最高'.abs($withdrawal['max_amount']).'元');
            }
            //判断时间
            if($withdrawal['time']){
                $period_time = explode("|",$withdrawal['time']);
                $time = strtotime(date('H:i',time()));//当前时间
                if($time > strtotime($period_time[0]) && $time > strtotime($period_time[1])){
                    return __error('请在 '.$period_time[0].' - '.$period_time[1].' 内进行提现申请');
                }
            }
            $result = $this->model->add($this->user['uid'],$withdrawal['total_fee'],$data);
            if($result === true){
                return __success('申请成功');
            }
            return __error($result);
        }
        $bank = Db::name("bank_card")->where("mch_id",$this->user['uid'])->field("id,card_number,bank_name,account_name")->select();
        $this->assign("bank",$bank);
        return view('add_withdrawal');
    }


}