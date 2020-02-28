<?php

namespace app\common\service;
use think\Db;
use think\facade\Env;

/**
 * 订单表分表模型
 */
class SubTable{

    /**
     * 创建分表订单数据表
     */
    public static function create_table($tableName){
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` LIKE cm_order";
        Db::execute($sql);
    }

    /** 插入数据库
     * @param $tableName
     * @param $begin
     * @param $end
     */
    public static function  insert_table($tableName,$begin,$end){

        //文件排它锁 非阻塞模式
        $fp = fopen(Env::get('root_path')."lock/SubTable.txt", "w+");
        if(flock($fp,LOCK_EX | LOCK_NB))
        {
          $res = Db::table('cm_order')->where([['create_at', 'BETWEEN', [$begin, $end]]])->chunk(500, function($data)use($tableName) {
              // 启动事务
              Db::startTrans();
              try {
                  //避免重复插入
                  Db::table($tableName)->insertAll($data,"IGNORE");
                  // 提交事务
                  Db::commit();
              } catch (\Exception $e) {
                  // 回滚事务
                  Db::rollback();
                  return false;
              }

            },'id', 'asc');

            flock($fp,LOCK_UN);//释放锁
        }else{
            $res = false;
        }
        fclose($fp);
        return $res;
    }


    //同步数据库
    public static function  syn_table(){
        //当天
        $d = date('d');
        if($d < 3){
            //上一个月
            $tableName1 = 'cm_order_'.date('Y_m',strtotime("-1 month"));
            self::create_table($tableName1);
            $create_at1 = Db::table($tableName1)->order(['id'=>'desc'])->value('create_at');
            if(empty($create_at1)){
                $begin1 = date('Y-m',strtotime("-1 month")).'-00 00:00:00';
            }else{
                $begin1  = $create_at1;
            }
            $end1 = date('Y-m-d',strtotime("-1 month")).' 59:59:59';
            $time = strtotime($end1) - strtotime($begin1);

            if($time > 0){
                $res = self::insert_table($tableName1,$begin1,$end1);
                if(!$res) return false;//如果运行失败，下面也不执行了
            }
        }


        //当月
        $tableName = 'cm_order_'.date('Y_m');
        self::create_table($tableName);
        $create_at = Db::table($tableName)->order(['id'=>'desc'])->value('create_at');
        if(empty($create_at)){
            $begin  = date('Y-m').'-00 00:00:00';
        }else{
            $begin  = $create_at;
        }
        $end =  timeToDate( 0,0,0,-2);//只插入三天前的数据

        $time = strtotime($end) - strtotime($begin);

        $res = true;
        if($time > 0) $res = self::insert_table($tableName,$begin,$end);
        return $res;
    }






}