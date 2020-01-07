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








