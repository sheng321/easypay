<?php


namespace app\user\controller;
use app\common\controller\UserController;
use think\Db;
class Withdrawal extends UserController {
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
     *  提现记录
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
     *  申请提现
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
        $bank = Db::name("bank_card")->where("uid",$this->user['uid'])->field("id,card_number,bank_name,account_name")->select();
        $this->assign("bank",$bank);
        return view('add_withdrawal');
    }


}