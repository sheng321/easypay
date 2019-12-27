<?php

namespace app\common\validate;

use think\Validate;


/**
 * 金额验证类
 * Class User
 * @package app\admin\validate
 */
class Money extends Validate {

    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id'           => 'require|number',
        'uid'     => 'require|number|token|checkUid',
        'change'     => 'require|float|checkChange',//变动金额
        'type'     => 'require|in:1,2,3,4,5,6,7,8,9,10',//变动金额操作类型
        'remark' => 'chsDash|max:250',
    ];


    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require'        => '编号必须',
        'status.require' => '操作类型必须',
         'type.in' => '操作类型错误',
    ];

    /**
     * 应用场景
     * @var array
     */
    protected $scene = [
        'edit'=>['uid','change','remark']
    ];

    /**
     * 检测Uid
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool
     */
    function checkUid($value, $rule, $data = []){
        $user = \app\common\model\Uprofile::where(['uid' => $value])->find();
        if (empty($user)) return '暂无账户数据，请稍后再试！';
        if ($user['who'] != 0 && $user['who'] != 2) return '该商户号不是会员，不可操作！';
        return true;
    }

    function checkChange($value, $rule, $data = []){
        if ($value <= 0) return '请输入正确变动金额';
        return true;
    }

















}