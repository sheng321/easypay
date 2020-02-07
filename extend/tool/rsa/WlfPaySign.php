<?php
namespace tool\rsa;

class WlfPaySign
{
    private $user_name;
    private $merchant_id;
    private $private_key_pw;
    private $pfx_path;
    private $pem_path;
    private $url;
    private $send_data;
    private $ret_data;

    private $msg_real_time = array(
        '0000' => array(3, '处理完成'),
        '0001' => array(2, '系统处理失败'),
        '0002' => array(2, '已撤销'),
        '1000' => array(2, '报文内容检查错或者处理错'), //具体内容见返回错误信息
        '1001' => array(2, '报文解释错'),
        '1002' => array(2, '无法查询到该交易，可以重发'),
    );

    private $msg_query_head = array(
        '0000' => array(3, '处理完成'),
        '0001' => array(2, '系统处理失败'),
        '0002' => array(2, '已撤销'),
        '1000' => array(2, '报文内容检查错或者处理错'), //具体内容见返回错误信息
        '1001' => array(2, '报文解释错'),
        '1002' => array(2, '无法查询到该交易，可以重发'),
    );

    private $msg_query_detail = array(
        '0000' => array(1, '交易成功'),
        '0001' => array(2, '交易失败具体原因在ERR_MSG中说明'),
        '0002' => array(2, '商户审核不通过'),
        '0003' => array(2, '不通过受理'),
        '2000' => array(2, '系统正在对数据处理'),
        '2001' => array(2, '等待商户审核'),
        '2002' => array(2, '等待受理'),
        '2003' => array(2, '等待审核'),
        '2004' => array(2, '提交银行处理'),
    );

    private $public_key = '';

    public function __construct( )
    {


 /*       $this->user_name = '000000000102986';             //正式的用户名
        $this->merchant_id = '000000000102986';          //正式的商户号
        $this->url = 'https://dsf.sicpay.com/d/merchant/';   //正式的接口地址
        $this->pfx_path = "Files/Wlf/000000000102986.pfx";  //正式的私钥文件路径
        $this->pem_path = "Files/Wlf/GHT_Root.pem";  //正式的公钥文件路径*/

                $this->user_name = '000000000100641';             //测试的用户名
                $this->merchant_id = '000000000100641';          //测试的商户号
                $this->pfx_path = "Files/Wlf/testKey/TESTUSER.pfx";               //测试的私钥文件路径
                $this->pem_path = "Files/Wlf/testKey/TESTUSER.pem";              //测试的公钥文件路径
                $this->url = 'https://120.31.132.118:8181/d/merchant/';   //测试的接口地址

        $this->private_key_pw = '123456';            //私钥密码
        $this->public_key = file_get_contents($this->pem_path);

    }

//支付
    public function pay($info)
    {
        //error_log("--------------------------分割线---------------------\n".'['.date('Y-m-d H:i:s').']$info:'."\n".var_export($info, true)."\n\n", 3, './pay_request.log');
        $this->set_data($info, 'pay');
        $this->curl_access($this->url);

        return $this->verify_ret('pay');
    }

//查询
    public function query($info)
    {
        $this->set_data($info, 'query');
        $this->curl_access($this->url);
        return $this->verify_ret('query');
    }

//查银行卡信息
    public function bank($info)
    {
        $this->set_data($info, 'bank');
        $this->curl_access($this->url);
        return $this->ret_data;
    }

    public function identity($info){//四要素身份认证
        $this->set_data($info, 'identity');
        $this->curl_access($this->url);
        return $this->verify_ret('identity');
    }

    public function three_identity($info){//三要素身份认证
        $this->set_data($info, 'three_identity');
        $this->curl_access($this->url);
        return $this->verify_ret('identity');
    }

    public function chk_account($info){//检查账户余额
        $this->set_data($info, 'account');
        $this->curl_access($this->url);
        return $this->verify_ret('account');
    }

    /**
     * 返回结果校验：
     */
    private function verify_ret($type)
    {

        if (trim($this->ret_data) == '') {
            return '接口返回为空';
        }

        $xml_obj = @simplexml_load_string($this->ret_data); //最原始的报文
        //var_dump($xml_obj);
        if (empty($xml_obj->INFO)) {
            return '接口返回格式错误';
        }

        //error_log('['.date('Y-m-d H:i:s').']$xml_obj:'."\n".iconv('UTF-8', 'GBK', var_export($xml_obj, true))."\n\n", 3, './pay_request.log');

        //校验签名
        $sign_data = preg_replace('/<SIGNED_MSG>(.+)<\/SIGNED_MSG>/', '', $this->ret_data);
        preg_match('/<SIGNED_MSG>(.+)<\/SIGNED_MSG>/', $this->ret_data, $match);
        $verify_result = $this->verify_sign($sign_data, $match[1]);
        if ($verify_result !== 1) {
            return '签名校验错误';
        }

        return (array) json_decode(json_encode($xml_obj),true);
    }

    private function set_data($info, $type = 'pay')
    {
        $xml = '';
        if ($type == 'pay') {
            $xml = '<GHT>
                    <INFO>
                        <TRX_CODE>100005</TRX_CODE>
                        <VERSION>04</VERSION>
                        <DATA_TYPE>2</DATA_TYPE>
                        <LEVEL>0</LEVEL>
                        <USER_NAME>'.$this->user_name.'</USER_NAME>
                        <REQ_SN>'.$info['order_id'].'</REQ_SN>
                        <SIGNED_MSG></SIGNED_MSG>
                    </INFO>
                    <BODY>
                    <TRANS_SUM>
                        <BUSINESS_CODE>09100</BUSINESS_CODE>
                        <MERCHANT_ID>'.$this->merchant_id.'</MERCHANT_ID>
                        <SUBMIT_TIME>'.date('YmdHis').'</SUBMIT_TIME>
                        <TOTAL_ITEM>1</TOTAL_ITEM>
                        <TOTAL_SUM>'.$info['amount'].'</TOTAL_SUM>
                    </TRANS_SUM>
                    <TRANS_DETAILS>
                        <TRANS_DETAIL>
                            <SN>0001</SN>
                            <BANK_CODE>'.$info['bank_code'].'</BANK_CODE>
                            <ACCOUNT_TYPE>00</ACCOUNT_TYPE>
                            <ACCOUNT_NO>'.$info['account_no'].'</ACCOUNT_NO>
                            <ACCOUNT_NAME>'.$info['account_name'].'</ACCOUNT_NAME>
                            <ACCOUNT_PROP>0</ACCOUNT_PROP>
                            <AMOUNT>'.$info['amount'].'</AMOUNT>
                            <CURRENCY>CNY</CURRENCY>
                        </TRANS_DETAIL>
                    </TRANS_DETAILS>
                    </BODY>
                </GHT>';
        } elseif ($type == 'query') {
            $xml = '<GHT>
                    <INFO>
                        <TRX_CODE>200001</TRX_CODE>
                        <VERSION>03</VERSION>
                        <DATA_TYPE>2</DATA_TYPE>
                        <REQ_SN>'.$info['order_id'].'</REQ_SN>
                        <USER_NAME>'.$this->user_name.'</USER_NAME>
                        <SIGNED_MSG></SIGNED_MSG>
                    </INFO>
                    <BODY>
                        <QUERY_TRANS>
                            <QUERY_SN>'.$info['order_id'].'</QUERY_SN>
                        </QUERY_TRANS>
                    </BODY>
                </GHT>';
        } elseif ($type == 'account') {//查询账户余额
            $xml = '<GHT>
                         <INFO>
                             <TRX_CODE>200004</TRX_CODE>
                             <VERSION>04</VERSION>
                             <DATA_TYPE>2</DATA_TYPE>
                             <LEVEL>0</LEVEL>
                             <USER_NAME>'.$this->user_name.'</USER_NAME>
                             <USER_PASS>1</USER_PASS>
                             <REQ_SN>'.$info['order_id'].'</REQ_SN>
                             <SIGNED_MSG></SIGNED_MSG>
                         </INFO>
                         <BODY>
                             <QUERY_TRANS>
                             <MERCHANT_ID>'.$this->user_name.'</MERCHANT_ID>
                             <CURRENCY>CNY</CURRENCY>
                           
                             </QUERY_TRANS>
                         </BODY>
                    </GHT>';

        }elseif ($type == 'bank') {

            $xml = '<GHT>
                    <INFO>
                        <TRX_CODE>200007</TRX_CODE>
                        <VERSION>04</VERSION>
                        <DATA_TYPE>2</DATA_TYPE>
                        <REQ_SN>'.$info['order_id'].'</REQ_SN>
                        <USER_NAME>'.$this->user_name.'</USER_NAME>
                        <SIGNED_MSG></SIGNED_MSG>
                    </INFO>
                    <BODY>
                        <QUERY_TRANS>
                            <BANKNO>'.$info['bankno'].'</BANKNO>
                        </QUERY_TRANS>
                    </BODY>
                </GHT>';
        } else if($type == 'identity'){ //身份认证
            $xml = '<GHT>
                    <INFO>
                        <TRX_CODE>100003</TRX_CODE>
                        <VERSION>04</VERSION>
                        <DATA_TYPE>2</DATA_TYPE>
                        <LEVEL>0</LEVEL>
                        <USER_NAME>'.$this->user_name.'</USER_NAME>
                        <REQ_SN>'.$info['order_id'].'</REQ_SN>
                        <SIGNED_MSG></SIGNED_MSG>
                    </INFO>
                    <BODY>
                    <TRANS_DETAILS>
                        <TRANS_DETAIL>
                            <SN>0001</SN>
                            <BANK_CODE>'.$info['bank_code'].'</BANK_CODE>
                            <ACCOUNT_TYPE>00</ACCOUNT_TYPE>
                            <ACCOUNT_NO>'.$info['account_no'].'</ACCOUNT_NO>
                            <ACCOUNT_NAME>'.$info['account_name'].'</ACCOUNT_NAME>
                            <ACCOUNT_PROP>0</ACCOUNT_PROP>
                            <ID_TYPE>0</ID_TYPE>
							<ID>'.$info['id_card'].'</ID>
							<TEL>'.$info['mobile'].'</TEL>
                        </TRANS_DETAIL>
                    </TRANS_DETAILS>
                    </BODY>
                </GHT>';
        } else if($type == 'three_identity'){ //三要素身份认证
            $xml = '<GHT>
                    <INFO>
                        <TRX_CODE>100003</TRX_CODE>
                        <VERSION>04</VERSION>
                        <DATA_TYPE>2</DATA_TYPE>
                        <LEVEL>0</LEVEL>
                        <USER_NAME>'.$this->user_name.'</USER_NAME>
                        <REQ_SN>'.$info['order_id'].'</REQ_SN>
                        <SIGNED_MSG></SIGNED_MSG>
                    </INFO>
                    <BODY>
                    <TRANS_DETAILS>
                        <TRANS_DETAIL>
                            <SN>0001</SN>
                            <BANK_CODE>'.$info['bank_code'].'</BANK_CODE>
                            <ACCOUNT_TYPE>00</ACCOUNT_TYPE>
                            <ACCOUNT_NO>'.$info['account_no'].'</ACCOUNT_NO>
                            <ACCOUNT_NAME>'.$info['account_name'].'</ACCOUNT_NAME>
                            <ACCOUNT_PROP>0</ACCOUNT_PROP>
                            <ID_TYPE>0</ID_TYPE>
							<ID>'.$info['id_card'].'</ID>
                        </TRANS_DETAIL>
                    </TRANS_DETAILS>
                    </BODY>
                </GHT>';
        }
        $xml = str_replace(array(' ', "\n", "\r"), '', $xml);
        $xml = '<?xml version="1.0" encoding="GBK"?>'.$xml;
        $sign_data = str_replace('<SIGNED_MSG></SIGNED_MSG>', '', $xml);
        $sign = $this->create_sign($sign_data);
        $xml = str_replace('<SIGNED_MSG></SIGNED_MSG>', '<SIGNED_MSG>'.$sign.'</SIGNED_MSG>', $xml);
        //var_dump('<pre/>', @simplexml_load_string($xml));
        $this->send_data = $xml;
    }

    private function create_sign($data)
    {
        $data = iconv('GBK', 'UTF-8//IGNORE', $data); //高汇通那边计算签名是用UFT-8编码
        $pkey_content = file_get_contents($this->pfx_path); //获取密钥文件内容
        //  var_dump($pkey_content);
        openssl_pkcs12_read($pkey_content, $certs, $this->private_key_pw); //读取公钥、私钥
        $pkey = $certs['pkey']; //私钥

        openssl_sign($data, $signMsg, $pkey, OPENSSL_ALGO_SHA1); //注册生成加密信息
        $signMsg = bin2hex($signMsg);
        return $signMsg;
    }

    private function verify_sign($data, $sign) {
        $data = iconv('GBK', 'UTF-8//IGNORE', $data); //高汇通那边计算签名是用UFT-8编码
        $sign = $this->HexToString($sign);

        $public_key_id = openssl_pkey_get_public($this->public_key);
        $res = openssl_verify($data, $sign, $public_key_id);   //验证结果，1：验证成功，0：验证失败

        //error_log('['.date('Y-m-d H:i:s').']签名验证结果$res:'."\n".$res."\n\n", 3, './pay_request.log');
        return $res;
    }

    private function curl_access($url)
    {
        // $url = 'https://flpay.sicpay.com/card/test/test_notify';
        $ch = curl_init();
        // $data = http_build_query(['str'=>$data]);
        curl_setopt($ch,CURLOPT_TIMEOUT,60);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/plain'));
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$this->send_data);
        // curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        if (strpos($url, 'https') !== false) {

            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            /*curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);    //高汇通那边的版本*/
        }

        $ret_data = trim(curl_exec($ch));

        /* var_dump( curl_error($ch));
         $info = curl_getinfo($ch);
         print_r($info);*/
        // echo "返回来的报文："; echo "</br>";
        //  var_dump($ret_data);
        //  print_r($ret_data);

        //error_log('['.date('Y-m-d H:i:s').']官方返回2:'."\n".$ret_data."\n\n", 3, './pay_request.log');
        //error_log('['.date('Y-m-d H:i:s').']curl_errno:'."\n".curl_errno($ch)."\n\n", 3, './pay_request.log');
        //error_log('['.date('Y-m-d H:i:s').']curl_error:'."\n".curl_error($ch)."\n\n", 3, './pay_request.log');
        //error_log('['.date('Y-m-d H:i:s').']curl_getinfo:'."\n".var_export(curl_getinfo($ch), true)."\n\n", 3, './pay_request.log');

        curl_close($ch);

        $this->ret_data = $ret_data;
    }

    private function HexToString($s){
        $r = "";
        for($i=0; $i<strlen($s); $i+=2){
            $r .= chr(hexdec('0x'.$s{$i}.$s{$i+1}));
        }
        return $r;
    }

}

