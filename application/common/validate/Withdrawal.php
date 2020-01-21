<?php

namespace app\common\validate;
use think\Validate;

/**
 * 出款验证
 * Class Withdrawal
 * @package app\common\validate
 */
class Withdrawal extends Validate {

    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id'      => 'number|checkId',
        'uid'   => 'require',
        'status'   => 'in:1,2,3,4,9',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
          'status'=> '状态值不在范围内'
    ];

    /**
     * 应用场景
     * @var array
     */
    protected $scene = [
        'status' => ['id','status'],
        'add_more' => [],
    ];


    /**
     * 检测ID是否存在
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function checkId($value, $rule, $data = []) {
        $Withdrawal = \app\common\model\Withdrawal::quickGet($value);
        if (empty($Withdrawal)) return '暂无数据，请稍后再试！';

        if (app('request')->module() == 'admin') {
            if (!empty($Withdrawal['lock_id']) && session('admin_info.id') != $Withdrawal['lock_id']) return '该订单已经锁定，无权操作！';
        }
        if (!empty($data['verson']) && $data['verson'] !=  $Withdrawal['verson'] + 1 ) return '重复操作，请刷新重试';
        if ($Withdrawal['status'] == 3 || $Withdrawal['status'] == 4 ) return '该订单已处理，不可更改！';

        return true;
    }
}