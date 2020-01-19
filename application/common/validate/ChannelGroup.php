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
        'id'      => 'require|number',
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
        return $this->only(['id','title','remark','code','p_rate','min_amount','max_amount','f_amount','ex_amount','f_multiple','f_num']);

    }

    /**用户添加通道分组
     * @return $this
     */
    public function sceneChannel() {
        return $this->only(['id'])
            ->remove('id', 'checkVer');
    }





}