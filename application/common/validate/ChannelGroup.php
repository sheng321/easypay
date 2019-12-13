<?php

// +----------------------------------------------------------------------
// | Think.Admin
// +----------------------------------------------------------------------
// | 版权所有 2014~2017 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/Think.Admin
// +----------------------------------------------------------------------

namespace app\common\validate;


use think\Validate;

class ChannelGroup extends Validate {

    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id'      => 'require|number|checkProductId|checkVer',
        'title'   => 'require|max:30|token',
        'sort'    => 'member',
        'remark'  => 'max:250',
        'field'   => 'require',
        'value'   => 'require|max:30',
        'p_id'   => 'require',

    ];

    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require'    => '编号必须',
        'title.require' => '支付产品必须',
        'id.number'     => '编号为数字',
        'field.require' => '字段必须',
        'value.require' => '修改值必须',
        'value.max'     => '修改值最多不能超过30个字符',
        'title.max'     => '支付产品名称最多不能超过30个字符',
        'p_id.require'     => '请选择支付产品',

    ];

    /**
     * 应用场景
     * @var array
     */
    protected $scene = [
        //添加支付产品
        'add'        => ['title','remark','p_id'],
        
        //修改支付产品字段值
        'edit_field' => ['id', 'field', 'value'],

        //删除角色
        'del'        => ['id'],

        //更改角色状态
        'status'     => ['id'],
        'istrue'     => ['id'],
        'cli'     => ['id'],
    ];

    /**
     * 自定义验证场景
     * @return Node
     */
    public function sceneEdit() {
        return $this->only(['id','title','remark','code','p_rate','min_amount','max_amount','f_amount','ex_amount','f_multiple','f_num'])
            ->remove('id', 'checkProductId');
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
    protected function checkProductId($value, $rule, $data = []) {
        $user = \app\common\model\ChannelGroup::where(['id' => $value])->find();
        if (empty($user)) return '暂无支付产品数据，请稍后再试！';
        return true;
    }

    /**
     * 检测ID版本号
     * 乐观锁
     */
    protected function checkVer($value, $rule, $data = []) {
       if(!isset($data['verson'])) return true;
        $verson = \app\common\model\ChannelGroup::where(['id' => $value])->value('verson');
        if ($verson !== $data['verson']-1) return '多人同时操作，请刷新再试！';
        return true;
    }
}