<?php
namespace app\pay\controller\api;
use app\common\controller\PayController;
use tool\Curl;


/**api 下单模板
 * Class Index
 * @package app\pay\controller\api
 */
class Index extends PayController
{
    //通道信息
    protected $channel;

    //通道配置信息
    protected $config;

    public function __construct()
    {
        parent::__construct();
    }

    //下单
    public function pay($create,$channel_id){

    // return msg_post('https://www.baidu.com',[]);
    // return msg_get('https://www.baidu.com');
    }

    //查询
    public function query($sn){

    }
    //回调
    public function notify(){

    }

    public function callback(){
        echo '处理成功'; exit;
    }

}