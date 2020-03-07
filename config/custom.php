<?php

return [
    //提现设置
    'withdrawal' => [
        'fee'=>'5',//手续费 不填默认为0
        'min_amount'=>'1',//单笔最低 不填表示不限制
        'max_amount'=>'49900',//单笔最高 不填表示不限制
        'time'       => ''//格式：02:00|11:00 提现时间 不填表示任何时间都可以提现
    ],
    //代付设置
    'df' => [
        'status'=>'1',//  是否开启 0 关闭 1 开启
        'rate'=>'0.01',//  充值费率
        'fee'=>'5',//手续费 不填默认为0
        'min_pay'=>'1',//单笔最低 不填表示不限制
        'max_pay'=>'49900',//单笔最高 不填表示不限制
        'limit_times'=>'100',//单卡单日次数
        'limit_money'=>'20000',//单卡单日限额
        'visit'=>'对私',//付款方式
        'inner'=>'外扣',//下发方式
        'total_money'=>'50000',//默认会员单日提现额度
        'excpet_uid'=>'20100008',//排除会员单日提现额度
        'time'=>'',//格式：02:00|11:00 提现时间 不填表示任何时间都可以提现
         'api'=>'1',//是否开启API代付接口 0 关闭 1 开启
    ],

    'status' => [
        1  => '未处理',
        2 => '处理中',
        3 => '已完成',
        4 => '失败退款'
    ],
];