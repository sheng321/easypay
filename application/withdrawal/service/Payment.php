<?php
namespace app\withdrawal\service;

use think\Exception;

Class Payment {
    public static function factory($class_name){
        if(!empty($class_name)){
            $className  =  '\\app\\withdrawal\\controller\\api\\'.$class_name;
            try{
                //是否可以实例化
                $reflectionClass = new \ReflectionClass($className);
                if(!$reflectionClass->isInstantiable()) {
                    throw new Exception('支付服务不存在1');
                }

                $class = new $className;

                if(!method_exists($class,'pay')){
                    throw new Exception('支付服务不存在2');
                }
                if(!method_exists($class,'query')){
                    throw new Exception('支付服务不存在3');
                }
                if(!method_exists($class,'balance')){
                    throw new Exception('支付服务不存在4');
                }

            }catch (\Exception $exception){
                logs($exception->getMessage().'|'.$exception->getFile(),'withdrawal');
                __jerror($exception->getMessage());
            }

            return $class;
        }
        __jerror('支付服务不存在5');
    }

}


