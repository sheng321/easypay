<?php


namespace app\common\validate;


use think\Validate;

class Channel extends Validate {

    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'id'      => 'require|number',
        'pid'      => 'require|number|checkPid',
        'title'   => 'require|max:30',
        'sort'    => 'member',
        'remark'  => 'max:250',
        'field'   => 'require',
        'value'   => 'require|max:30',

        'code'  => 'require|max:20|alphaNum|checkCode',
        'c_rate'=> 'float',//运营费率
        'min_amount'=> 'number',//最低限额
        'max_amount'=> 'number',//最高限额
        'f_amount'=> 'number',//固定值
        'ex_amount'=> 'number',//排除固定值
        'f_multiple'=> 'number',//固定倍数
        'f_num'=> 'number',//固定尾数
        'sort'=> 'in:0,2',//固定尾数

        'limit_money'=> 'number',//限额
        'limit_time'=> 'number',//单笔限时

        'p_id'   => 'require',
        'pid'   => 'require|number',

        'charge'   => 'in:0,1',//话费

        'fee'   => 'number',
        'min_pay'   => 'number',
        'max_pay'   => 'number',
        'limit_times'   => 'number',

        'gateway'   => 'activeUrl',
        'queryway'   => 'activeUrl',
        'balanceway'   => 'activeUrl',

    ];



    /**
     * 错误提示
     * @var array
     */
    protected $message = [
        'id.require'    => '编号必须',
        'title.require' => '支付通道必须',
        'id.number'     => '编号为数字',
        'field.require' => '字段必须',
        'value.require' => '修改值必须',
        'value.max'     => '修改值最多不能超过30个字符',
        'title.max'     => '支付通道名称最多不能超过30个字符',

        'code.alphaNum'     => '支付编码必须为字母和数字',
        'code.require'     => '支付编码必须',
        'code.max'     => '支付编码最多不能超过20个字符',
        'c_rate.float'     => '费率必须为小数',
        'min_amount.number'     => '最低限额必须为整数',
        'max_amount.number'     => '最高限额必须为整数',
        'f_amount.number'     => '固定值必须为整数',
        'ex_amount.number'     => '排除固定金额必须为整数',
        'f_multiple.number'     => '固定倍数必须为整数',
        'f_num.number'     => '固定尾数必须为整数',
        'sort.in'     => '置顶必须在0,2之间',

        'gateway.activeUrl'     => '下单网关或者IP不正确',
        'queryway.activeUrl'     => '查询网关或者IP不正确',
        'balanceway.activeUrl'     => '余额查询网关或者IP不正确',

        'limit_money.number'     => '限额必须为纯数字',
        'limit_time.number'     => '限额必须为纯数字',

        'p_id.require'     => '请选择支付类型',

    ];




    /**
     * 应用场景
     * @var array
     */
    protected $scene = [
        //添加支付通道
        'add'        => ['title','limit_money','code'],

        'padd'        => ['title','pid','p_id','remark','c_rate','min_amount','max_amount','f_amount','ex_amount','f_multiple','f_num'],
        
        //修改支付通道字段值
        'edit_field' => ['id', 'field', 'value'],

        //删除角色
        'del'        => ['id'],

        //更改角色状态
        'status'     => ['id'],
        'inner'     => ['id'],
        'visit'     => ['id'],

    ];

    /**
     * 自定义验证场景
     * @return Node
     */
    public function sceneEdit() {
        return $this->only(['id','title','remark','code','c_rate','min_amount','max_amount','f_amount','ex_amount','f_multiple','f_num','charge','limit_time','limit_money']);
    }

    public function sceneSort() {
        return $this->only(['sort','id']);
    }

    /**
     * 代付通道
     * @return $this
     */
    public function sceneDf() {
        return $this->only(['title','remark','code','c_rate','min_pay','max_pay','limit_times','limit_money','gateway','queryway','balanceway','fee','min_pay' ,'max_pay','limit_times'])
            ->remove('code','checkCode')
            ->append('title','token');
    }


    /**
     * 自定义验证场景
     * @return Node
     */
    public function scenePedit() {
        return $this->only(['id','title','remark','code','c_rate','min_amount','max_amount','f_amount','ex_amount','f_multiple','f_num']);
    }




    /**
     * 检测PID是否存在
     */
    protected function checkPid($value, $rule, $data = []) {
        $user = \app\common\model\Channel::where(['id' => $value,'pid' => 0])->value('id');
        if (empty($user)) return '暂无支付上级通道数据，请稍后再试！';
        return true;
    }


    protected function checkCode($value, $rule, $data = []) {
        $code = \app\common\model\Channel::where(['code'=>$value,'pid'=>0])->field('id,code')->find();
        if(!empty($data['id']) && !empty($code) && $code['id'] != $data['id'])  return '编辑状态通道编码重复';
        if (empty($data['id']) && !empty($code)) return '通道编码重复';
        return true;
    }

}