<?php
namespace app\admin\controller;

use app\common\model\Umember;
use app\common\model\Umoney;
use think\Controller;
use redis\StringModel;
use think\Queue;
use tool\Curl;


class Test  extends Controller
{
    public function index()
    {

        $value='http://admin_xyf987abc.xinyufu.com/Pay_Shen_callbackurl.html';
         $str="/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/";
        if( preg_match($str,$value)) return true;

        $urlarr = parse_url($value);
        if(filter_var($urlarr['host'], FILTER_VALIDATE_IP)) return true;
        return false;


       dump($urlarr['scheme'].'://'.$urlarr['host']);
        if(filter_var($urlarr['host'], FILTER_VALIDATE_IP) || preg_match($str,$url_name)){
            dump(1);
        }
        dump(2);

        halt($urlarr);




        return $this->fetch('', []);
    }

}
