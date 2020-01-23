<?php


namespace app\common\validate;


use think\Validate;

class Bank extends Validate {

    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id'      => 'number|checkId',
        'uid'   => 'require',
        'account_name'    => 'require|chsDash|max:15|token',
        'card_number'  => 'require|number|max:30',
        'bank_name' => 'require|chsDash|max:50',
        'branch_name' => 'chsDash|max:50',
        'areas'   => 'chsDash|max:50',
        'province'   => 'chsDash|max:20',
        'city'   => 'chsDash|max:20',

    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'account_name'    =>  '开户人 格式不正确或者太长',
        'account_name.token'    =>  '令牌验证失效，请刷新重试',
        'card_number'  => '银行卡号 格式不正确或者太长',
        'bank_name' => '银行名称 格式不正确或者太长',
        'branch_name' =>  '所在支行 格式不正确或者太长',
        'areas'   =>  '地区 格式不正确或者太长',
        'province'   =>  '省份 格式不正确或者太长',
        'city'   =>  '城市 格式不正确或者太长',
    ];

    /**
     * 应用场景
     * @var array
     */
    protected $scene = [
        'edit' => ['id', 'uid', 'account_name', 'card_number', 'bank_name', 'branch_name', 'areas', 'province', 'city'],
        'del' => ['id'],
    ];

    /**
     * 代付批量添加的情况
     * @return $this
     */
    public function sceneAdd_more() {
        return $this->only(['account_name','card_number','bank_name','branch_name'])
            ->remove('account_name', 'token')
            ->remove('branch_name', 'require');

    }


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
        if(!isset($data['uid']))  $data['uid'] = 0;//系统的银行卡
        $Bank = \app\common\model\Bank::where(['id' => $value,'uid' =>$data['uid']])->find();
        if (empty($Bank)) return '暂无数据，请稍后再试！';
        return true;
    }
}