<?php


namespace app\agent\controller;
use app\common\controller\AgentController;
use app\common\model\Bank;
use app\common\model\Umoney;

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
     *  提现记录
     */
    public function index(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->get('search', []);
            $search['mch_id'] = $this->user['uid'];
            return json($this->model->alist($page, $limit, $search));
        }
        $basic_data = [
            'title' => '提现记录列表',
        ];
        return $this->fetch('', $basic_data);
    }

    /**
     *  申请提现
     */
    public function addWithdrawal(){


        //获取提现配置
        $withdrawal = config("custom.withdrawal");
        $uid = $this->user['uid'];
        $Umoney =  Umoney::quickGet(['uid'=>$uid]);
        $bank = Bank::bList($uid);



        if($this->request->isPost()){//提现申请
            //判断时间
            if(!empty($withdrawal['time'])){
                $period_time = explode("|",$withdrawal['time']);
                $time = strtotime(date('H:i',time()));//当前时间
                if($time > strtotime($period_time[0]) && $time > strtotime($period_time[1])){
                    return __error('请在 '.$period_time[0].' - '.$period_time[1].' 内进行提现申请');
                }
            }

            $ip =  \app\common\model\Ip::bList($this->user['uid'],1);
            if(!in_array(get_client_ip(),$ip)) return __error('结算IP白名单不包含此IP:'.get_client_ip());

            //谷歌验证码
            if($this->UserInfo['UserGoole'] == 1){
                $data1['google_token'] =  $this->user['google_token'];
                $data1['google'] = $this->request->post('google/d',0);
                $validate1 = $this->validate($data1, 'app\common\validate\common.google');
                if (true !== $validate1) return __error($validate1);
            }

            //token
            $__token__ = $this->request->param('__token__/s','');
            $__hash__ = Session::pull('__token__');
            if($__token__ !== $__hash__)  return __error("令牌验证无效，请刷新重试");


            //支付密码
            $data2['paypwd1'] =  $this->user['profile']['pay_pwd'];
            $data2['paypwd'] =  $this->request->post('paypwd/s','');
            //验证数据
            $validate2 = $this->validate($data2, 'app\common\validate\Umember.paypwd');
            if (true !== $validate2) return __error($validate2);


            $amount =  $this->request->post('amount/d',0);

            if($withdrawal['min_amount'] > $amount) return __error('不能小于最小提现金额！');
            if($withdrawal['max_amount'] < $amount) return __error('不能大于最高提现金额！');
            if(($Umoney['balance'] - $amount < 0) || ($amount - $withdrawal['fee'] <= 0)) return __error('金额不正确！');

            $bank_card_id =  $this->request->post('bank_card_id/d',0);
            if(empty($bank[$bank_card_id])) return __error('选择银行卡不存在！');

            $data['mch_id'] = $uid;
            $data['system_no'] = getOrder('w');//提现订单号
            $data['amount'] = $amount;
            $data['bank_card_id'] = $bank_card_id;
            $data['bank'] = json_encode($bank[$bank_card_id]);
            $data['fee'] = $withdrawal['fee'];
            $data['lock_id'] = 0;

            $change['change'] = $data['amount'];//变动金额
            $change['relate'] = $data['system_no'];//关联订单号
            $change['type'] = 5;//提现冻结金额类型

            $res = Umoney::dispose($Umoney,$change); //处理
            if (true !== $res['msg']) return __error($res['msg']);

            //使用事物保存数据
            $this->model->startTrans();

            $save1 = $this->model->create($data);
            $save = model('app\common\model\Umoney')->saveAll($res['data']);
            $add = model('app\common\model\UmoneyLog')->saveAll($res['change']);

            if (!$save || !$add  || !$save1) {
                $$this->model->rollback();
                $msg = '数据有误，请稍后再试！';
                return __error($msg);
            }
            $this->model->commit();
            empty($msg) && $msg = '操作成功';
            return __success($msg);
        }

        $this->assign("bank",$bank);
        $basic_data = [
            'title' => '提现记录列表',
            'withdrawal' => $withdrawal,
            'money' => ['balance'=>$Umoney['balance'],'frozen_amount'=>$Umoney['frozen_amount'] + $Umoney['frozen_amount_t1'] + $Umoney['artificial']],
        ];
        return $this->fetch('', $basic_data);
    }



}