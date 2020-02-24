<?php
namespace app\admin\controller\api;
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

}
