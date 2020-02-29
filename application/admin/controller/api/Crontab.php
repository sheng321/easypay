<?php
namespace app\admin\controller\api;
use app\common\service\CountService;
use app\common\service\SubTable;
use think\Controller;

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
    }



}
