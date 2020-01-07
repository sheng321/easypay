<?php


namespace tool;

/**
 * 请求服务
 * Class Curl
 */
class Curl
{

    /**
     * 建立跳转请求表单
     * @param string $url 数据提交跳转到的URL
     * @param array $data 请求参数数组
     * @param string $method 提交方式：post或get 默认post
     * @return string 提交表单的HTML文本
     */
    static function buildRequestForm($url, $data, $method = 'post')
    {
        $sHtml = "<form id='requestForm' name='requestForm' action='".$url."' method='".$method."'>";
        foreach ($data as $key => $val){
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."' />";
        }
        $sHtml = $sHtml."<input type='submit' value='确定' style='display:none;'></form>";
        $sHtml = $sHtml."<script>document.forms['requestForm'].submit();</script>";
        return $sHtml;
    }



    /**
     * POST 请求
     * @param string $url
     * @param array $param
     * @param boolean $post_file 是否文件上传
     * @return string content
     */
    static public function post($url,$param,$timeout=10){
        $oCurl = curl_init();

        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        //设置curl默认访问为IPv4
        if(defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
            curl_setopt($oCurl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            //curl版本7.10.8及以上版本时，以上设置才生效
        }
        //设置curl请求连接时的最长秒数，如果设置为0，则无限
        curl_setopt ($oCurl, CURLOPT_CONNECTTIMEOUT, $timeout);
        //设置curl总执行动作的最长秒数，如果设置为0，则无限
        curl_setopt ($oCurl, CURLOPT_TIMEOUT,$timeout*3);

        if (is_string($param)) {
            $strPOST = $param;
        } else{
            $aPOST = array();
            foreach($param as $key=>$val){
                $aPOST[] = $key."=".urlencode($val);
            }
            $strPOST =  join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($oCurl, CURLOPT_POST,true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
        $sContent = curl_exec($oCurl);
       // $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        return $sContent;
    }

    static public function post_json($url,$param,$timeout=10){
        $oCurl = curl_init();

        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        //设置curl默认访问为IPv4
        if(defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
            curl_setopt($oCurl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            //curl版本7.10.8及以上版本时，以上设置才生效
        }
        //设置curl请求连接时的最长秒数，如果设置为0，则无限
        curl_setopt ($oCurl, CURLOPT_CONNECTTIMEOUT, $timeout);
        //设置curl总执行动作的最长秒数，如果设置为0，则无限
        curl_setopt ($oCurl, CURLOPT_TIMEOUT,$timeout*3);

        if (is_string($param)) {
            $strPOST = $param;
            curl_setopt($oCurl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length:' . strlen($param),'Expect:'));

        } else{
            $aPOST = array();
            foreach($param as $key=>$val){
                $aPOST[] = $key."=".urlencode($val);
            }
            $strPOST =  join("&", $aPOST);
            curl_setopt($oCurl, CURLOPT_HTTPHEADER, array('Expect:'));
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($oCurl, CURLOPT_POST,true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
        $sContent = curl_exec($oCurl);
        // $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        return $sContent;
    }

    /**
     * @param $url
     * @return bool|mixed
     */
    static public function get($url,$param = false,$timeout = 10){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }

        if (is_string($param)) {
            $url = $url.'?'.$param;
        } elseif(is_array($param)){
            $aPOST = array();
            foreach($param as $key=>$val){
                $aPOST[] = $key."=".urlencode($val);
            }
            $strPOST =  join("&", $aPOST);
            $url = $url.'?'.$strPOST;
        }
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, array('Expect:'));//减少一次不必要的 HTTP 请求

        //设置curl默认访问为IPv4
        if(defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
            curl_setopt($oCurl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        //设置curl请求连接时的最长秒数，如果设置为0，则无限
        curl_setopt ($oCurl, CURLOPT_CONNECTTIMEOUT, $timeout);
        //设置curl总执行动作的最长秒数，如果设置为0，则无限
        curl_setopt ($oCurl, CURLOPT_TIMEOUT,$timeout*3);

        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec($oCurl);
        //$aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        return $sContent;
    }

    /**
     * @param $url
     * @return bool|mixed
     */
    static public function getXml($url){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec($oCurl);
       // $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        $xml = simplexml_load_string($sContent);
        return $xml;


    }

    /**
     * 生成安全JSON数据
     * @param array $array
     * @return string
     */
    static public function jsonEncode($array)
    {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', create_function('$matches', 'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'), json_encode($array));
    }


    static public function  curlDownload($url, $dir)
    {
        $ch = curl_init($url);
        $fp = fopen($dir, "wb");// w--write b--binary. 依2进制数据格式打开,准备写入。
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $res = curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        return $res;
    }
}