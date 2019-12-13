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
        'rate'         => 'float',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require'        => '编号必须',
        'rate.float'        => '费率必须浮点数',
    ];

    /**
     * 应用场景
     * @var array
     */
    protected $scene = [
        //添加商户
        'add'           => ['id'],

        //修改商户
        'edit'          => ['id'],

        //删除商户
        'del'           => ['id'],

        //更改商户状态
        'status'        => ['id'],

    ];




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









}