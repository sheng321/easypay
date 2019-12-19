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

namespace app\common\model;

use app\common\service\ModelService;

/**
 * 商户金额模型
 * Class Auth
 * @package app\common\model
 */
class Umoney extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_member_money';
    /**
     * Undocumented 获取余额
     *
     * @param [type] $mch
     * @return void
     */
    public static function get_amount($mch){

        return Umoney::where("uid",$mch)->value("balance");
        
    }

}