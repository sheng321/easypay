<?php

namespace app\common\validate;

use think\Validate;

/**
 * 公共验证规则
 * Class Common
 * @package app\admin\validate
 */
class Common extends Validate {
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id'    => 'require|number',
        'field' => 'require',
        'value' => 'require|max:100',
        'email' => 'email',
        'ip' => 'require|ip',
        'word' => 'require|checkWord',//口令

        'param' => 'checkParam|max:255',//验证某个字段的值只能是汉字、字母、数字和下划线_及破折号-
        'param_k' => 'require|alphaDash|max:20',

        'google'=>'require|length:6|number|verifyGoogle',

    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require'    => '编号必须',
        'field.require' => '字段必须',
        'value.require' => '修改值必须',

        'email.email'   => '邮箱格式不正确',

        'ip.require'  => '请输入IP',
        'word.require'  => '请输入口令重试~',
        'param.chsAlphaNum' => '字段必须字母或者数字',

        'google.require' => '请输入谷歌验证码',
        'google.length' => '谷歌验证码为6位',
        'google.number' => '谷歌验证码为数字',

        'field.in' => 'field不再范围内',

    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        //修改字段值
        'edit_field' => ['id', 'field', 'value'],
        'edit_rate' => ['id', 'field', 'value'],
        'check_ip' => ['ip'],
        'check_word' => ['word'],
        'check_param' => ['param','param_k'],
        'email' => ['email'],
        'google' => ['google'],
    ];

    /**
     * 自定义验证场景
     * @return Node
     */
    public function sceneEdit_field()
    {
        return $this->only(['id','field','value'])
            ->append('field', 'in:title,remark,sort,value')
            ->remove('value', 'require')
            ->append('value', 'checkValue');

    }

    public function checkParam($value, $rule, $data = [])
    {
        return $this->checkVal($value);
    }

    public function checkValue($value, $rule, $data = [])
    {
        return $this->checkVal($value);
    }
    public function checkVal($value){
        if(empty($value))  return true;

        $msg = true;
        $value = str_replace("@","",$value);
        $value = str_replace("http://","",$value);
        $value = str_replace("/","",$value);
        $value = str_replace(".","",$value);

        $chsDash = \think\facade\Validate::checkRule($value,"chsDash"); //只能是汉字、字母、数字和下划线_及破折号-
        if(!$chsDash) $msg = '输入值只能是汉字、字母、数字和下划线_及破折号-';

        return $msg;
    }



    public function sceneEdit_rate()
    {
        return $this->only(['id','field','value'])
            ->remove('value', 'require')
            ->append('value', 'float')
            ->append('field', 'in:p_rate,rate,c_rate,s_rate')
            ->append('value', 'checkRate');
    }


    /** 验证费率
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    public function checkRate($value, $rule, $data = [])
    {
        if($value > 0.7)   return '费率输入异常：'.$value;
        return true;
    }


    /**验证口令
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    public function checkWord($value, $rule, $data = [])
    {
        $word = \think\facade\Config::get('word.');

        if(empty($word[$data['id']])) return '暂无权限口令，请稍后再试！';
        $w1 = md5($word[$data['id']]);
        $w2 = md5($data['word']);
        if ($w1 !== $w2) return '口令错误，请稍后再试！';
        return true;
    }


    /**
     * 验证谷歌动态验证码
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    public function verifyGoogle($value, $rule, $data = [])
    {
        if(empty($data['google_token'])){
             $code = password($data['google']);
             $default = password(\think\facade\Config::get('google.default'));
            if($default !== $code){
                return '谷歌初始密码错误，请联系客服获取~';
            }
            return true;
        }

        $verify = (new \tool\Goole())->verifyCode($data['google_token'], $data['google'],3);
        if($verify !== true)  return '谷歌密码错误';
        return true;
    }




}