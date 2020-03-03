<?php
namespace app\admin\controller\api;
use app\common\service\CountService;
use app\common\service\SubTable;
use think\Controller;
use think\Db;

/**
 * 定时任务
 * Class Crontab
 * @package app\admin\controller
 */
class Crontab  extends Controller
{
    public function index()
    {
       $res = SubTable::syn_table();
       if($res){
           echo '更新成功';
       }else{
           echo '更新失败';
       }
    }


    /**
     * 每5分钟统计
     */
    public function count_5()
    {

        //3-10分钟成功率
        CountService::success_rate();
        //提醒下发订单
        $num = Db::table('cm_withdrawal')
            ->where('id', 'IN', function ($query) {
                $query->table('cm_withdrawal')->where(['status', '>',1])->field('id')->order(['id'=>'desc']);
            })->count(1);
        if(!empty($num))  addTask('结算提现通知',"有 $num 提现订单未处理。",6,3);

        $num1 = Db::table('cm_withdrawal_api')
            ->where('id', 'IN', function ($query) {
                $query->table('cm_withdrawal_api')->where(['status', '>',1])->field('id')->order(['id'=>'desc']);
            })->count(1);
        if(!empty($num1))  addTask('结算提现通知',"有 $num1 代付提现订单未处理。",6,3);

        echo '更新成功';
    }


    /**
     * 每10分钟统计
     */
    public function count_10()
    {
        CountService::mem_account();
        CountService::agent_account();
        CountService::channel_account();
        CountService::withdraw_account();
        CountService::df_account();
        CountService::sys_account();
        echo '更新成功';
    }



}
