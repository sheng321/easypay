<?php

namespace app\admin\behavior;
use think\Controller;

use think\facade\Request;


/**
 * 访问前的操作
 * Class intBehavior
 * @package app\admin\behavior
 */
class intBehavior extends Controller
{
    public function run(){

        Policy();
      $this->check_param();
      $this->command();
    }

    /**
     * 校验口令
     */
    protected function command(){
        if( $this->request->isPost() ||$this->request->isAjax()){
            //判断是否需要口令
            if(!check_word()){

                $num = session('word_num')?session('word_num'):0;

                $word = Request::param('word','');
                if(empty($word)){
                    exceptions('请输入口令重试');
                }
                $data['id'] = session('admin_info.id');
                $data['word'] = $word;

                //验证数据
                $validate = $this->validate($data, 'app\common\validate\Common.check_word');

                if (true !== $validate){
                    session('word_num',$num + 1);
                    exceptions($validate);
                }
                session('word_num',null);

                //超过一定次数，锁定
                if($num < 5){

                }

            }
        }
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
                $data['param'] = $v;

                if($k == 'email' || $k == 'mailUsername'){
                    $validate2 = $this->validate($data, 'app\common\validate\Common.email');
                    if (true !== $validate2) exceptions($validate2);
                }

                if($k == 'create_at' || $k == 'update_at'){
                    $v = str_replace(" - ","",$v);
                    $v = str_replace("-","",$v);
                }

                //url
                if($k == 'href' ){
                    $v = str_replace("/","",$v);
                    $v = str_replace("_","",$v);
                    $v = str_replace("#","",$v);
                }

                if($k == 'icon' ){
                    $v = str_replace("-","",$v);
                }


                //数组
                if($k == 'filterRules' || $k == 'sort'  ){
                    $v = str_replace("[","",$v);
                    $v = str_replace("]","",$v);
                }

                //编辑字段的情况
                if($k == 'value'  ){
                    $v = str_replace("@","",$v);
                    $v = str_replace("http://","",$v);
                }


                $v = str_replace("\n","",$v);//换行的情况
                $v = str_replace(".","",$v);//ip
                $v = str_replace("|","",$v);//固定金额
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
