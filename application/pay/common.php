<?php

//Pay公共文件

if (!function_exists('check_sign')) {

    function check_sign($data,$secret)
    {
        if(empty($data['pay_md5sign'])) return false;
        $sign  = $data['pay_md5sign'];
        unset($data['pay_md5sign']);

        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign2 = strtoupper(md5($md5str . "key=" . $secret));

        if($sign2 != $sign) return false;

        return true;
    }
}

if (!function_exists('msg_post')) {

    function msg_post($url = '', $data = [], $method = "post")
    {
        $sHtml = '<meta charset="utf-8">';
       $sHtml .=  \tool\Curl::buildRequestForm($url,$data,$method);
       return $sHtml;
    }
}
if (!function_exists('msg_get')) {

    function msg_get($url = '')
    {
        $success = '<meta charset="utf-8">';
        $success .= '<script type="text/javascript">';
        $success .= "window.location.href='{$url}';";
        $success .= '</script>';

        return $success;
    }
}

if (!function_exists('msg_url_qr')) {

    /**
     * 生成展示二维码页面
     * @param string $url
     * @param array $create
     * @param int $type
     * @return \think\response\View
     */
    function msg_url_qr($url = '',$create = [],$type = 0)
    {

        if($type > 0){
            //传来二维码图片的情况
            $qrUrl = $url;
        }else{
            //支付链接的情况
            $qrUrl = getQrcode($url);
        }

        //支付方式
        switch ($create['pay_code']){
            case 'aliH5':
            case 'wxH5':
            case 'qqH5':
                $temple = 'pay@common/paywap';
                break;
            default:
                $temple = 'pay@common/pay';
                break;
        }

        if(in_array($create['pay_code'],['aliH5','alipay'])){
            $name = '支付宝';
        }elseif(in_array($create['pay_code'],['wxH5','wx'])){
            $name = '微信';
        }else{
            $PayProduct = \app\common\model\PayProduct::quickGet(['code'=>$create['pay_code']]);
            $name = $PayProduct['title'];
        }
        $return = [
            'amount' => $create['amount'],
            'out_trade_id'=>$create['out_trade_no'],
            'orderid'=>$create['system_no'],
            'pay_code'=>$create['pay_code'],
            'wap_url'=>$url,
        ];

        $vars = [
            'isMobile'=>isMobile(),
            'name'=>$name,
            'wap_url'=>$url,
            'qrUrl'=>$qrUrl,
            'return'=>$return,
            'money'=>$create['amount']
        ];


        return view($temple,$vars);
    }
}













