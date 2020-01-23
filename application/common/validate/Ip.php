<?php


namespace app\common\validate;

use think\Validate;

class Ip extends Validate {

    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id'      => 'require|number|checkId',
        'uid'   => 'require',
        'ip'    => 'require|ip|token',
        'type'    => 'require|in:0,1,2,3',
    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
    ];

    /**
     * 应用场景
     * @var array
     */
    protected $scene = [
        'edit' => [ 'uid', 'ip','type'],
        'del' => ['id'],
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
        if(!isset($data['uid']))  $data['uid'] = 0;//系统的Ip
        $Bank = \app\common\model\Ip::where(['id' => $value,'uid' =>$data['uid']])->find();
        if (empty($Bank)) return '暂无数据，请稍后再试！';
        return true;
    }
}