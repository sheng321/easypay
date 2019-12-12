<?php

namespace app\common\validate;

use think\Validate;
class Login extends Validate {

    // 验证规则
    protected $rule =   [
        'username'      => 'require|token|alphaNum',
        'nickname'      => 'require|chsAlphaNum',
        'password'      => 'require|length:32|alphaNum',
        'vercode'  => 'require|captcha',

    ];

    // 验证提示
    protected $message  =   [
        'username.require'      => '用户名不能为空',
        'nickname.require'      => '昵称不能为空',
        'password.require'      => '密码不能为空',
        'password.length'       => '密码长度为不正确',
        'vercode.require'  => '验证码不能为空',
        'vercode.captcha'  => '验证码不正确，请重新输入',
    ];
    // 应用场景
    protected $scene = [
        'add'       =>  ['username','nickname','password','email'],
        'edit'      =>  ['nickname','email'],
        //开启验证码登录
        'index_on'  => ['username', 'password', 'vercode'],
        //关闭验证码登录
        'index_off' => ['username', 'password'],

    ];


    /**
     * 自定义验证场景
     * @return Node
     */
    public function sceneUser_login()
    {
        return $this->only(['username','password'])
            ->remove('username', 'token');
    }





}