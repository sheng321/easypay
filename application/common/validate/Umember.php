<?php
// +----------------------------------------------------------------------
// | 99PHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018~2020 https://www.99php.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Mr.Chung <chung@99php.cn >
// +----------------------------------------------------------------------

namespace app\common\validate;

use think\Validate;


/**
 * 后台商户验证类
 * Class User
 * @package app\admin\validate
 */
class Umember extends Validate {

    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id'           => 'require|number',
        'username'     => 'require|alphaNum|min:4|max:20',
        'nickname'     => 'chsDash|min:2|max:20',
        'old_password' => 'require|alphaNum|length:32|checkOldPassword',
        'password'     => 'require|alphaNum|length:32|checkPassword',
        'password1'    => 'require|alphaNum|length:32',
        'phone'        => 'number|mobile|checkPhone',
        'mail'         => 'email|max:20|checkMail',
        'auth_id'      => 'require',
        'qq'           => 'number',
        'remark'       => 'max:250',
        'google_token'       => 'require|length:17|token|checkGoogle',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require'        => '编号必须',
        'username.min'      => '名称不能少于5个字符',
        'username.max'      => '名称最多不能超过15个字符',
        'password.require'  => '密码必须',
        'password.length'      => '密码格式不正确',
        'password1.require' => '第二次密码必须',
        'password1.length'     => '密码格式不正确',
        'old_password.length'  => '密码格式不正确',
        'auth_id.number'    => '角色编号必须是数字',
        'mail.email'        => '邮箱格式错误',
        'phone.mobile'       => '手机号格式错误',
        'qq.number'         => 'QQ必须为数字',
        'remark.max'        => '备注最多不能超过250个字符',

        'google_token.require'        => '绑定谷歌失败，请联系商户',
        'google_token.length'        => '绑定谷歌失败，请联系商户',
    ];

    /**
     * 应用场景
     * @var array
     */
    protected $scene = [
        //添加商户
        'add'           => ['username', 'password', 'password1', 'phone', 'mail', 'auth_id', 'qq', 'remark'],

        //修改商户
        'edit'          => ['username', 'phone', 'mail', 'auth_id', 'qq', 'remark'],

        //修改登录密码
        'edit_password' => ['id', 'password', 'password1'],

        //删除商户
        'del'           => ['id'],

        //更改商户状态
        'status'        => ['id'],
        'single'        => ['id'],
        //重置谷歌
        'google'        => ['id'],

        //绑定谷歌
        'save_google'  => ['id','google_token']
    ];


    /**
     * 修改信息登入场景
     * @return User
     */
    public function sceneAdd() {
        return $this->only([ 'phone', 'qq'])
            ->remove('phone', 'require')
            ->remove('qq', 'require');
    }


    /**
     * 修改信息验证场景
     * @return User
     */
    public function sceneEdit() {
        return $this->only([ 'phone', 'qq','mail', 'auth_id'])
            ->remove('phone', 'require')
            ->remove('phone', 'checkPhone')
            ->remove('qq', 'require');
    }


    /**
     * 修改自己的信息
     * @return User
     */
    public function sceneEditSelf() {
        return $this->only(['qq','phone', 'mail', 'remark', 'username', 'id','nickname'])
            ->append('username', 'token')
            ->append('id', 'checkOperateId')
            ->remove('phone', 'checkPhone')
            ->remove('mail', 'checkMail');
    }

    /**
     * 修改自己的登录密码
     * @return User
     */
    public function sceneEditPassword1() {
        return $this->only(['password', 'password1','id', 'old_password'])
            ->append('password', 'token')
            ->append('id', 'checkOperateId');
    }



    /**
     * 检测删除时用户ID
     * @param       $value
     * @param       $rule
     * @param array $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function checkDelId($value, $rule, $data = []) {
        $user = \app\common\model\Umember::where(['id' => $value])->find();
        if (empty($user)) return '暂无账户数据，请稍后再试！';
        if ($user['status'] == 3) return '该账户已被删除，不可操作！';

        return true;
    }

    /**
     * 检测启用或者禁用时的用户ID
     * @param       $value
     * @param       $rule
     * @param array $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function checkOperateId($value, $rule, $data = []) {
        $user = \app\common\model\Umember::quickGet($data['id']);
        if (empty($user)) return '暂无账户数据，请稍后再试！';
        if ($user['status'] == 0) return '该账户已被冻结，请联系客服！';
        if ($user['status'] == 3) return '该账户已被删除，不可操作！';

        return true;
    }

    /**
     * 检查用户名是否已存在
     * @param       $value
     * @param       $rule
     * @param array $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function checkUsername($value, $rule, $data = []) {
        $where_user = [
            'username'   => $value,
            'status'     => 1,
                   ];
        $user = \app\common\model\Umember::where($where_user)->find();
        return empty($user) ? true : '已有相同登录账号，请更换进行注册！';
    }

    /**
     * 判断两个输入的密码是否一致
     * @param       $value
     * @param       $rule
     * @param array $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function checkPassword($value, $rule, $data = []) {
        return md5($data['password']) === md5($data['password1']) ? true : '两次输入的密码不一致，请重新输入！';
    }

    protected function checkOldPassword($value, $rule, $data = []) {
        $user = \app\common\model\Umember::quickGet($data['id']);
        if (empty($user)) return '暂无改商户信息，请刷新重试！';
        return $user['password'] == password($value) ? true : '旧密码不正确，请重新输入！';
    }

    /**
     * 判断是否存在相同的手机号
     * @param       $value
     * @param       $rule
     * @param array $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function checkPhone($value, $rule, $data = []) {
        $where_user = [
            'phone'      => $value,
            'status'     => 1,
        ];
        $user = \app\common\model\Umember::where($where_user)->find();
        return empty($user) ? true : '已有相同手机号码，请更换进行注册！';
    }

    /**
     * 判断是否存在相同的邮箱
     * @param       $value
     * @param       $rule
     * @param array $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function checkMail($value, $rule, $data = []) {
        $where_user = [
            'mail'       => $value,
            'status'     => 1,
        ];
        $user = \app\common\model\Umember::where($where_user)->find();
        return empty($user) ? true : '已有相同邮箱，请更换进行注册！';
    }



    /**
     * 判断是否已经绑定谷歌了
     */
    protected function checkGoogle($value, $rule, $data = []) {
        $user = \app\common\model\Umember::quickGet($data['id']);
        return empty($user['google_token']) ? true : '已绑定谷歌，不需要重复绑定！';
    }


}