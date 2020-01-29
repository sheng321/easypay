<?php

namespace app\pay\behavior;
use think\Controller;


/**
 * 访问前的操作
 * Class intBehavior
 * @package app\admin\behavior
 */
class intBehavior extends Controller
{
    public function run(){

        halt($this->request->controller());

        if($this->request->controller() == 'Api' && $this->request->action() == 'index'){
            PolicyApi();
            $this->check_api();
        }elseif($this->request->controller() == 'Query' && $this->request->action() == 'index'){
            $this->check_query();
        }elseif($this->request->controller() == 'Notify' && $this->request->action() == 'index'){
            PolicyApi();
        }else{
            Policy(); //同源协议
            $this->check_param();
        }
    }

    /**
     * 验证下单接口
     */
    protected function check_api(){
        $param =   $this->request->only(["pay_memberid" ,"pay_orderid","pay_amount","pay_applydate","pay_bankcode" ,"pay_notifyurl","pay_callbackurl","pay_md5sign","pay_productname","pay_attach"],'post');
        if(empty($param))  __jerror('提交方式错误！');

        //验证数据
        $validate = $this->validate($param, 'app\common\validate\Pay.check_api');
        if (true !== $validate)   __jerror($validate);

        return true;
    }

    /**
     * 验证查询订单接口
     */
    protected function check_query(){
        $param =   $this->request->only(["pay_memberid" ,"pay_orderid","pay_md5sign"],'post');
        if(empty($param))  __jerror('提交方式错误！');

        //验证数据
        $validate = $this->validate($param, 'app\common\validate\Pay.check_query');
        if (true !== $validate)   __jerror($validate);

        return true;
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
                $data['param_k'] = $k;

                //验证数据
                $validate = $this->validate($data, 'app\common\validate\Common.check_param'); //必须为数组或者字母
                if (true !== $validate){
                    logs(json_encode(array($k=>$v),320),'error');
                    __jerror($validate);
                }
            }else{
                logs(json_encode(array($k=>$v),320)."|未知参数！",'error');
                __jerror('未知参数！');
            }
        }
    }





}
