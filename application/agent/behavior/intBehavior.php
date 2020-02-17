<?php

namespace app\agent\behavior;
use think\Controller;

/**
 * 访问前的操作
 * Class intBehavior
 * @package app\user\behavior
 */
class intBehavior extends Controller
{
    public function run()
    {

        Policy();

      $this->check_param();
    }


    /**
     * 校验参数
     */
    protected function check_param(){
       $param =   $this->request->param();
        $this->check_param1($param);
    }
    protected function check_param1(&$param){
        foreach ($param as $k => $v){
            if(is_array($v)){
                $this->check_param1($v);
            }elseif(is_string($v)){

                if($k == 'create_at' || $k == 'update_at'){
                    $v = str_replace(" - ","",$v);
                }
                if($k == 'day'){
                    $v = str_replace(" ","",$v);
                }

                //极验
                if($k == 'geetest_seccode'){
                    $v = str_replace("|","",$v);
                }

                //数组
                if($k == 'filterRules' || $k == 'sort' ){
                    $v = str_replace("[","",$v);
                    $v = str_replace("]","",$v);
                }

                $data['param'] = $v;
                $data['param_k'] = $k;

                //验证数据
                $validate1 = $this->validate($data, 'app\common\validate\Common.check_param'); //必须为数组或者字母
                if (true !== $validate1){
                    logs(json_encode(array($k=>$v),320),'error');
                    exceptions($validate1);
                }
            }else{
                logs(json_encode(array($k=>$v),320)."|未知参数！",'error');
                exceptions('未知参数！');
            }
        }
    }





}
