<?php


namespace app\user\controller;
use app\common\controller\UserController;
use app\common\model\Bank;
use app\common\model\Df;
use app\common\model\Umoney;
use app\common\model\UmoneyLog;
use think\Db;
use think\facade\Session;


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
        $this->model = new \app\common\model\Withdrawal();
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
           
            $save = (new Umoney())->saveAll($res['data']);
            $add = (new UmoneyLog())->saveAll($res['change']);

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


    /**
     *  申请代付
     */
    public function add_df(){

        //获取提现配置
        $withdrawal = config("custom.df");
        $uid = $this->user['uid'];
        $Umoney =  Umoney::quickGet(['uid'=>$uid]);
        $bank = Bank::bList($uid);

        if($this->request->isPost()){
            //判断时间
            if(!empty($withdrawal['time'])){
                $period_time = explode("|",$withdrawal['time']);
                $time = strtotime(date('H:i',time()));//当前时间
                if($time > strtotime($period_time[0]) && $time > strtotime($period_time[1])){
                    return __error('请在 '.$period_time[0].' - '.$period_time[1].' 内进行提现申请');
                }
            }

            $ip =  \app\common\model\Ip::bList($this->user['uid'],2);
            if(!in_array(get_client_ip(),$ip)) return __error('代付IP白名单不包含此IP:'.get_client_ip());

            //谷歌验证码
            if($this->UserInfo['UserGoole'] == 1){
                $data1['google_token'] =  $this->user['google_token'];
                $data1['google'] = $this->request->post('google/d',0);
                $validate1 = $this->validate($data1, 'app\common\validate\common.google');
                if (true !== $validate1) return __error($validate1);
            }


            //支付密码
            $data2['paypwd1'] =  $this->user['profile']['pay_pwd'];
            $data2['paypwd'] =  $this->request->post('paypwd/s','');
            //验证数据
            $validate2 = $this->validate($data2, 'app\common\validate\Umember.paypwd');
            if (true !== $validate2) return __error($validate2);


            $amount =  $this->request->post('amount/d',0);

            if($withdrawal['min_pay'] > $amount) return __error('不能小于最小提现金额！');
            if($withdrawal['max_pay'] < $amount) return __error('不能大于最高提现金额！');
            if(($Umoney['df'] - $amount < 0) || ($amount - $withdrawal['fee'] <= 0)) return __error('金额不正确！');

            $bank_card_id =  $this->request->post('bank_card_id/d',0);
            if(empty($bank[$bank_card_id])) return __error('选择银行卡不存在！');


            //单卡单日次数
            $day_card =  Df::where([['status','<',4],['card_number','=',$bank[$bank_card_id]['card_number']]])->whereBetween('create_at',[timeToDate(0,0,-24),date('Y-m-d H:i:s')])->field("COALESCE(sum(amount),0) as amounts,count(1) as num ")->select();
            if(empty($day_card[0])) return __error('数据异常！');
            if(($day_card[0]['amounts'] + $amount) > $withdrawal['limit_money']){
                return __error("此银行卡： {$bank[$bank_card_id]['card_number']} 超过单卡单日限额 {$withdrawal['limit_money']} 元");
            }
            if(($day_card[0]['num'] + 1) > $withdrawal['limit_times']){
                return __error("此银行卡： {$bank[$bank_card_id]['card_number']} 超过单卡单日次数 {$withdrawal['limit_times']} 次");
            }

            //会员单日提现额度
            if(!empty($withdrawal['excpet_uid'])){
                $excpet_uid = explode("|",$withdrawal['excpet_uid']);
                if(in_array($uid,$excpet_uid)){
                    $amounts =  Df::where([['status','<',4],['mch_id','=',$uid]])->whereBetween('create_at',[timeToDate(0,0,-24),date('Y-m-d H:i:s')])->sum('amount');
                    if(($amounts + $amount) > $withdrawal['total_money']){
                        return __error("超过会员单日限额 {$withdrawal['total_money']} 元，请联系客服处理。");
                    }
                }
            }

            //token
            $__token__ = $this->request->param('__token__/s','');
            $__hash__ = Session::pull('__token__');
            if($__token__ !== $__hash__)  return __error("令牌验证无效，请刷新重试");


            $data['mch_id'] = $uid;
            $data['out_trade_no'] = '后台申请';
            $data['system_no'] = getOrder('d');//代付订单号
            $data['amount'] = $amount;
            $data['bank_card_id'] = $bank_card_id;
            $data['card_number'] = $bank[$bank_card_id]['card_number'];
            $data['bank'] = json_encode($bank[$bank_card_id]);
            $data['fee'] = $withdrawal['fee'];

            $change['change'] = $data['amount'];//变动金额
            $change['relate'] = $data['system_no'];//关联订单号
            $change['type'] = 15;//代付冻结金额类型

            $res = Umoney::dispose($Umoney,$change); //处理
            if (true !== $res['msg']) return __error($res['msg']);

            $this->model = new Df();

            //使用事物保存数据
            $this->model->startTrans();

            $save1 = $this->model->create($data);
            $save = (new Umoney())->saveAll($res['data']);
            $add = (new UmoneyLog())->saveAll($res['change']);

            if (!$save || $add  || !$save1 ) {
                $this->model->rollback();
                return __error('数据有误，请稍后再试！');
            }
            $this->model->commit();
            return __success('操作成功');
        }

        $this->assign("bank",$bank);
        $basic_data = [
            'title' => '代付记录列表',
            'withdrawal' => $withdrawal,
            'money' => ['balance'=>$Umoney['balance'],'df'=>$Umoney['df'],'frozen_amount'=>$Umoney['frozen_amount'] + $Umoney['frozen_amount_t1'] + $Umoney['artificial']],
        ];
        return $this->fetch('', $basic_data);
    }



    /**
     *  代付记录
     */
    public function df(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->get('search', []);
            $search['mch_id'] = $this->user['uid'];
            return json(model('app\common\model\Df')->alist($page, $limit, $search));
        }
        $basic_data = [
            'title' => '代付记录列表',
        ];
        return $this->fetch('', $basic_data);
    }


    /**批量添加代付
     * @return mixed|\think\response\Json
     */
    public function add_more()
    {
        //获取提现配置
        $withdrawal = config("custom.df");
        $uid = $this->user['uid'];
        $Umoney = Umoney::quickGet(['uid' => $uid]);

        if ($this->request->isPost()) {
            //判断时间
            if (!empty($withdrawal['time'])) {
                $period_time = explode("|", $withdrawal['time']);
                $time = strtotime(date('H:i', time()));//当前时间
                if ($time > strtotime($period_time[0]) && $time > strtotime($period_time[1])) {
                    return __error('请在 ' . $period_time[0] . ' - ' . $period_time[1] . ' 内进行提现申请');
                }
            }

             $ip =  \app\common\model\Ip::bList($this->user['uid'],2);
            if(!in_array(get_client_ip(),$ip)) return __error('代付IP白名单不包含此IP:'.get_client_ip());


            //谷歌验证码
            if ($this->UserInfo['UserGoole'] == 1) {
                $data1['google_token'] = $this->user['google_token'];
                $data1['google'] = $this->request->post('google/d', 0);
                $validate1 = $this->validate($data1, 'app\common\validate\common.google');
                if (true !== $validate1) return __error($validate1);
            }



            //支付密码
            $data2['paypwd1'] = $this->user['profile']['pay_pwd'];
            $data2['paypwd'] = $this->request->post('paypwd/s', '');
            //验证数据
            $validate2 = $this->validate($data2, 'app\common\validate\Umember.paypwd');
            if (true !== $validate2) return __error($validate2);


            //token
            $__token__ = $this->request->param('__token__/s', '');
            $__hash__ = Session::pull('__token__');
            if ($__token__ !== $__hash__) return __error("令牌验证无效，请刷新重试");

            $account_name = $this->request->post('account_name/a', []);
            $bank_name = $this->request->post('bank_name/a', []);
            $card_number = $this->request->post('card_number/a', []);
            $branch_name = $this->request->post('branch_name/a', []);
            $amount = $this->request->post('amount/a', []);
            if(empty($account_name)) return __error('无数据');


            $bank = config('bank.');
            $bank_id = [];
            //银行卡数据
            foreach ($bank_name as $k => $v){
               if(empty($bank[$v])){
                   $msg = $v;
                   break;
               }
               $bank_name[$k] = $bank[$v];
               $bank_id[$k] = $v;
                $msg = true;
            }
            if($msg !== true) return __error('银行代码'.$msg.'：错误或者不支持此银行！');


            $post = [];
            $change['change'] = 0;//变动金额
            $sum = 0;//手续费之和
            foreach ($account_name as $k => $v){
                $Bank['account_name'] =  $account_name[$k];
                $Bank['bank_name'] =  $bank_name[$k];
                $Bank['card_number'] =  $card_number[$k];
                $Bank['branch_name'] =  $branch_name[$k];
                $Bank['bank_id'] =  $bank_id[$k];


                //验证数据
                $validate3 = $this->validate($Bank, 'app\common\validate\Bank.add_more');
                if (true !== $validate3){
                    return __error($validate3);
                    break;
                }
                $post[$k]['bank'] = json_encode($Bank);

                $post[$k]['amount'] = floatval($amount[$k]);
                if ($withdrawal['min_pay'] > $post[$k]['amount']){
                    return __error('不能小于最小提现金额！');
                    break;
                }
                if ($withdrawal['max_pay'] < $post[$k]['amount']){
                    return __error('不能大于最高提现金额！');
                    break;
                }
                $change['change'] +=  $post[$k]['amount'];
                $sum += $withdrawal['fee'];

                $post[$k]['mch_id'] =  $this->user['uid'];
                $post[$k]['bank_card_id'] = 0;
                $post[$k]['system_no'] = getOrder('d');//代付订单号
                $post[$k]['fee'] = $withdrawal['fee'];
                $post[$k]['out_trade_no'] = '后台申请';

            }

            if (($Umoney['df'] - $change['change'] < 0) || ($change['change'] - $sum <= 0)) return __error('代付金额之和不正确！');

            $change['relate'] = $this->user['uid'];//关联uid
            $change['type'] = 15;//代付冻结金额类型

            $res = Umoney::dispose($Umoney, $change); //处理
            if (true !== $res['msg']) return __error($res['msg']);



           $this->model = new Df();;
            //使用事物保存数据
            $this->model->startTrans();

            $save1 = $this->model->isUpdate(false)->saveAll($post);
            $save = (new Umoney())->isUpdate(true)->saveAll($res['data']);
            $add = (new UmoneyLog())->isUpdate(false)->saveAll($res['change']);

            if (!$save || !$add || !$save1) {
                $$this->model->rollback();

                return __error('数据有误，请稍后再试！');
            }
            $this->model->commit();

            return __success('操作成功');
        }else{

            $basic_data = [
                'title' => '批量添加代付',
                'withdrawal' => $withdrawal,
                'money' => [ 'df' => $Umoney['df'], 'frozen_amount' => $Umoney['frozen_amount'] + $Umoney['frozen_amount_t1'] + $Umoney['artificial']],
            ];
            return $this->fetch('', $basic_data);

        }
    }


}