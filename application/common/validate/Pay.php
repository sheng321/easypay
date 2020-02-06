<?php

namespace app\common\validate;

use think\Validate;

/**
 * 验证规则
 * Class Pay
 * @package app\admin\validate
 */
class Pay extends Validate {
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        "pay_memberid" => 'require|number',
        "pay_orderid" => 'require|alphaDash|max:25',
        "pay_amount" => 'require|checkAmount',
        "pay_applydate" => 'require|dateFormat:Y-m-d H:i:s|checkDate',
        "pay_bankcode" => 'require|checkBankcode',
        "pay_notifyurl" => 'require|checkUrl|max:250',
        "pay_callbackurl" => 'require|checkUrl|max:250',
        "pay_md5sign" => 'require|alphaDash|length:32',

        "pay_productname" => 'chsDash|max:100',
        "pay_attach" => 'chsDash|max:100',

        "mchid" => 'require|number',
        "money" => 'require|checkAmount',
        "out_trade_no" => 'require|alphaDash|max:25',

        "accountname" => 'require|chsAlphaNum|max:25',//汉字、字母和数字
        "bankname" => 'require|number|max:5|checkBank',
        "cardnumber" => 'require|alphaNum|max:30',//数字和字母
        "city" => 'require|chsAlphaNum|max:20',
        "province" => 'require|chsAlphaNum|max:20',
        "subbranch" => 'require|chsAlphaNum|max:50',
        "extends" => 'max:250|checkExtends',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'pay_memberid.require' => '商户号不存在',
        'pay_memberid.number' => '商户号不存在',

        'pay_orderid.require' => '订单号不存在',
        'pay_orderid.alphaDash' => '订单号格式不正确',
        'pay_orderid.max' => '订单号不能多于25个字符',

        'pay_amount.require' => '支付金额不存在',

        'pay_applydate.require' => '订单时间不存在',
        'pay_applydate.dateFormat' => '订单时间格式不正确',

        'pay_bankcode.require' => '通道编码不存在',

        'pay_notifyurl.require' => '服务器通知地址不存在',
        'pay_callbackurl.require' => '页面返回地址不存在',

        'pay_notifyurl.checkUrl' => '服务器通知地址不是有效的',
        'pay_callbackurl.checkUrl' => '页面返回地址不是有效的',

        'pay_notifyurl.max' => '服务器通知地址不能大于250个字符',
        'pay_callbackurl.max' => '页面返回地址不能大于250个字符',

        'pay_md5sign.require' => '签名不存在',
        'pay_md5sign.alphaDash' => '签名格式不正确',
        'pay_md5sign.length' => '签名格式不正确',

        "pay_productname.chsDash" => '商品名称只能是汉字、字母、数字和下划线_及破折号-',
        "pay_attach.chsDash" => '商品名称只能是汉字、字母、数字和下划线_及破折号-',
        'pay_productname.max' => '服务器通知地址不能大于100个字符',
        'pay_attach.max' => '页面返回地址不能大于100个字符',


        'mchid.require' => '商户号不存在',
        'mchid.number' => '商户号不存在',

        'out_trade_no.require' => '订单号不存在',
        'out_trade_no.alphaDash' => '订单号格式不正确',
        'out_trade_no.max' => '订单号不能多于25个字符',

        'money.require' => '代付金额不存在',

        'accountname.require' => '开户名不存在',
        'accountname.alphaDash' => '开户名格式不正确',
        'accountname.max' => '开户名不能多于25个字符',

        'bankname.require' => '开户行不存在',
        'bankname.number' => '开户行格式不正确',
        'bankname.max' => '开户行不能多于5个字符',

        'cardnumber.require' => '卡号不存在',
        'cardnumber.alphaNum' => '卡号格式不正确',
        'cardnumber.max' => '卡号不能多于30个字符',

        'city.require' => '城市不存在',
        'city.chsAlphaNum' => '城市格式不正确',
        'city.max' => '城市不能多于20个字符',

        'province.require' => '省份不存在',
        'province.chsAlphaNum' => '省份格式不正确',
        'province.max' => '省份不能多于20个字符',

        'subbranch.require' => '支行名称不存在',
        'subbranch.chsAlphaNum' => '支行名称不正确',
        'subbranch.max' => '支行名称不能多于50个字符',

    ];




    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
         'check_api' => ["pay_memberid" ,"pay_orderid","pay_amount","pay_applydate","pay_bankcode" ,"pay_notifyurl","pay_callbackurl","pay_md5sign","pay_productname","pay_attach"],
        'check_query' => ["out_trade_no" ,"mchid","pay_md5sign"],

        //代付
         'check_withdrawal' => ["accountname" ,"bankname","cardnumber","city","extends" ,"mchid","money","out_trade_no","province","subbranch","pay_md5sign"],

    ];

    public function checkExtends($value, $rule, $data = [])
    {
        if(empty($value))  return true;
        if($value !== base64_encode(base64_decode($value)))  return "extends需要base64字符串";
        return true;
    }

    public function checkBank($value, $rule, $data = [])
    {
        $bank = config('bank.');
        if(empty($bank[$value]))  return '不支持此银行卡。';
        return true;
    }


    public function sceneCheck_amount()
    {
        //mtype 0都可以 1整数 2小数  f_num 固定尾数 f_multiple 固定倍数 ex_amount 排除固定金额 f_amount 固定金额
        return $this->only(['amount','min_amount','max_amount','f_amount','ex_amount','f_multiple','f_num','mtype'])
            ->append('amount', 'checkAmount')
            ->append('amount', 'checkAmount1');

    }

    public function checkAmount1($value, $rule, $data = [])
    {
        //小数 整数
        if(!empty($data['mtype'])){
            if($data['mtype'] == 1 && ceil($value) != $value) return "金额必须是正整数";
            if($data['mtype'] == 2 && ceil($value) == $value) return "金额必须是小数";
        }

        //最大最小
        if(!empty($data['max_amount']) && !empty($data['min_amount']) && is_numeric($data['f_multiple']) && is_numeric($data['f_multiple'])){
            if($value < $data['min_amount'] || $value >$data['max_amount']) return "金额不在 {$data['min_amount']}-{$data['max_amount']} 范围内";
        }

        //固定金额
        if(!empty($data['f_amount'])){
            $f_amount = array_filter(explode('|',$data['f_amount']));
            if(!empty($f_amount)){
                if(!in_array($value,$f_amount)) return "金额必须是 {$data['f_amount']} 范围内";
            }
        }

        //固定倍数
        if(!empty($data['f_multiple']) && is_int($data['f_multiple']) && $data['f_multiple'] > 0){
          $f_multiple = $value % $data['f_multiple'];
            if($f_multiple == 0) return "金额必须是 {$data['f_multiple']} 倍数";
        }

        //排除金额
        if(!empty($data['ex_amount'])){
            $ex_amount = array_filter(explode('|',$data['ex_amount']));
            if(!empty($ex_amount)){
                if(in_array($value,$ex_amount)) return "金额必须是 {$data['ex_amount']} 范围外";
            }
        }

        //固定尾数
        if(!empty($data['f_num'])){
            $one = '|'.substr($value, -1).'|';//最后一位
            $two = '|'.substr($value, -2).'|';//最后两位
            if(strpos('|'.$data['f_num'].'|',$one) === false && strpos('|'.$data['f_num'].'|',$two) === false)  return "金额必须是尾数在 {$data['f_num']} 范围内";
        }
        return true;
    }


    public function checkAmount($value, $rule, $data = [])
    {
        if(!is_numeric($value) || $value  < 0) return '请输入正确金额格式';
        $value1 = number_format((int)$value,2,'.','');
        if($value1  < 1) return '金额不能少于1元';
        return true;
    }

    public function checkBankcode($value, $rule, $data = [])
    {
       $idCode =  \app\common\model\PayProduct::idCode1();
       if(!isset($idCode[$value])  || $idCode[$value]['status'] == 0) return '通道不存在，或者已维护';

       return true;
    }

    public function checkDate($value, $rule, $data = [])
    {
        $now = time() - 60*5;
        $date = strtotime($value);
        if($date < $now) return '订单时间与当前时间相差太大';
        return true;
    }


    public function checkUrl($value, $rule, $data = [])
    {
        $str="/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/";
        if( preg_match($str,$value)) return true;

        $urlarr = parse_url($value);
        if(filter_var($urlarr['host'], FILTER_VALIDATE_IP)) return true;
        return false;
    }


}