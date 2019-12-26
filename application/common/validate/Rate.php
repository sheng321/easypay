<?php

namespace app\common\validate;

use think\Validate;


/**
 * 费率验证类
 * Class User
 * @package app\admin\validate
 */
class Rate extends Validate {

    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id'           => 'require|number',
        'uid'     => 'require|number|token|checkUid',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require'        => '编号必须',
    ];

    /**
     * 应用场景
     * @var array
     */
    protected $scene = [
        //添加费率
        'add'           => ['uid'],

        //删除费率
        'del'           => ['id'],

        //更改费率状态
        'status'        => ['id'],
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
        if ($user['who'] != 0) return '该商户号不是会员，不可操作！';
        return true;
    }













}