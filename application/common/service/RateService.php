<?php

namespace app\common\service;

/**
 * Class RateService
 * 获取费率服务
 *费率有几种
 * 1.支付产品有个默认运营费率（对商户）
 * 2.支付通道有成本费率，指定运营对外费率（指定运营对外费率只对应平台商户，对平台商户优先级最高）
 * 3.支付通道分组有个默认运营费率（对代理）
 * 4.商户有个指定的费率，优先级大于用户分组费率
 * 5.每个用户分组都可以设置费率，其中商户用户分组对应支付产品，代理用户分组对应支付通道分组
 * 6.代理名下可以给下级设置不同的商户用户分组，代理用户分组的费率
 *
 */
use app\common\model\ChannelGroup;
use app\common\model\PayProduct;
use app\common\model\Channel;
use app\common\model\SysRate;
use app\common\model\Uprofile;
use app\common\model\Ulevel;

class RateService
{

    public $product;
    public $channel;
    public $channelGroup;

    //支付产品的默认费率
    public static function product(){
       return  PayProduct::idRate();
    }
    //支付通道分组默认费率
    public static function channelGroup(){
        return  ChannelGroup::idRate();

    }

    //支付通道成本费率和运营费率   c_rate 成本费率  s_rate 对外费率
    public static function channel(){
        return  Channel::idRate();
    }

    //用户分组对应费率   ['id','group_id','uid','p_id','channel_id'],
    public static function groupRate($id){
        return  SysRate::idRate($id);
    }


    /**
     * 获取商户费率
     * @param int $uid 商户号
     * @param $p_id  支付产品ID
     * @channel $channel 支付通道产品ID
     */
    public static function getMemRate($uid,$p_id,$channel_id = null){
        $rate = 0;

        $data['uid'] = $uid;
        $profile = Uprofile::quickGet($data);

        //是否 存在  为商户 设定了分组
        if(empty($profile) || $profile['who'] != 0 ||$profile['group_id'] == 0  ) return $rate;

        //是否给用户单独设置了费率
        $data['type'] = 2;
        $data['p_id'] = $p_id;
        $alone = SysRate::quickGet($data);
        //给商户单独设置费率优先级高。
        if(!empty($alone) )  $rate = $alone['rate'];

        if(empty($rate)){
            //用户分组费率
                $data['type'] = empty($profile['pid'])?0:1; //是否代理商户分组 平台商户分组
                $data['uid'] = empty($profile['pid'])?0:$profile['pid'];
                $data['group_id'] = $profile['group_id'];
                $data['channel_id'] = 0;
                $SysRate = SysRate::quickGet($data); //查询是否有该分组下的支付产品费率
                if(!empty($SysRate['rate'])  && $SysRate['rate'] != '0.0000' )  $rate = $SysRate['rate'];
        }

        if(empty($rate)) $rate = self::product()[$p_id]['p_rate']; // 没有记录，统一使用支付产品的默认费率

        //是否指定了 支付通道产品对外费率
        if(!empty($channel_id)){
            $s_rate = self::channel()[$channel_id]['s_rate'];
            $rate = max($s_rate,$rate); //取最大值
        }

        return $rate;
    }

    /**
     * 获取商户支付产品状态和费率
     * @param $uid
     * @param $p_id
     * @return mixed
     */
    public static function getMemStatus($uid,$p_id){
        $res['code'] = 0;
        $res['id'] = $p_id;//产品
        $res['status'] = 0;//对应费率表的状态
        $res['rate'] = 0;//费率
        $res['type'] = 0;//哪种类型的费率

        $data['uid'] = $uid;
        $profile = Uprofile::quickGet($data);

        //是否 存在  为商户 设定了分组
        if(empty($profile) || $profile['who'] != 0 ||$profile['group_id'] == 0  ) return $res;

        //支付产品默认类型
        $product = self::product();
        //支付产品是关闭的情况
        if(empty($product[$p_id]) || $product[$p_id]['status'] == 0){
            $res['code'] = 1;
            $res['type'] = 3;
            return $res;
        }

        //是否给用户单独设置了费率
        $data['type'] = 2;
        $data['p_id'] = $p_id;
        $alone = SysRate::quickGet($data);

        //1.给商户单独设置费率优先级高。
        if(!empty($alone)){
            $res['status'] = $alone['status'];//对应费率表的状态
            $res['code'] = 1;
            $res['rate'] = $alone['rate'];//费率
            $res['type'] = 4;//个人费率类型
            return $res;
        }

            //2.用户分组费率
            $data['type'] = empty($profile['pid'])?0:1; //是否代理商户分组 平台商户分组
            $data['uid'] = empty($profile['pid'])?0:$profile['pid'];
            $data['group_id'] = $profile['group_id'];
            $data['channel_id'] = 0;
            $SysRate = SysRate::quickGet($data); //查询是否有该分组下的支付产品费率

            //如果存在用户分组费率
            if(!empty($SysRate)){
                $res['type'] = 2;//用户分组费率类型
                $res['code'] = 1;
                $res['status'] = $SysRate['status'];
                $res['rate'] = $SysRate['rate'];//费率
               if($res['status'] == 0)  return $res;
            }

       //3.最后默认统一使用支付产品的默认费率
        if(empty($res['rate']) || $res['rate'] == '0.0000'  ){
            $res['code'] = 1;
            $res['status'] = $product[$p_id]['status'];
            $res['type'] = 3;//系统费率类型
            $res['rate'] =  $product[$p_id]['p_rate'];//没有记录，统一使用支付产品的默认费率
        }

        return $res;
    }



    /**
     * 获取代理支付通道分组状态和费率
     * @param $uid
     * @param $channel_group_id
     * @return mixed
     */
    public static function getAgentStatus($uid,$channel_group_id){
        $res['code'] = 0;
        $res['id'] = $channel_group_id;//支付通道分组
        $res['status'] = 0;//对应费率表的状态
        $res['rate'] = 0;//费率
        $res['type'] = 0;//哪种类型的费率

        $data['uid'] = $uid;
        $profile = Uprofile::quickGet($data);

        //是否 存在  为代理 设定了分组
        if(empty($profile) || $profile['who'] != 2 ||$profile['group_id'] == 0  ) return $res;

        //2.上级的状态
        if($profile['pid'] != 0){
            $res1 = self::getAgentStatus($profile['pid'],$channel_group_id);
            if(!empty($res1)){
                if(!empty($res1)){
                    $res['code'] = 1;
                    $res['rate'] = $res1['rate'];
                    $res['type'] = 2;//上级通道分组费率类型
                    $res['status'] = $res1['status'];
                    if($res['status'] == 0) return $res;
                }
            }
        }else{
            //平台支付通道分组
            $channelGroup = self::channelGroup();
            if(!empty($channelGroup[$channel_group_id])){
                $res['code'] = 1;
                $res['status'] =  $channelGroup[$channel_group_id]['status'];
                $res['type'] = 3;//系统通道分组默认费率类型
                $res['rate'] =  $channelGroup[$channel_group_id]['c_rate'];//默认费率
            }
        }

        if($res['status'] == 0) return $res;

        //代理用户分组费率
        $data['type'] = empty($profile['pid'])?0:1; //是否代理分组 平台分组
        $data['uid'] = empty($profile['pid'])?0:$profile['pid'];
        $data['group_id'] = $profile['group_id'];
        $data['channel_id'] = $channel_group_id;
        $data['p_id'] = 0;
        $SysRate = SysRate::quickGet($data); //查询是否有该分组下的通道分组费率

        if(!empty($SysRate['rate']) ){

            $res['code'] = 1;
            $res['status'] = min($SysRate['status'],$res['status']);//对应费率表的状态
            $res['type'] = 1;//代理分组费率类型
            $res['rate'] = max($SysRate['rate'],$res['rate']);//代理分组费率
        }


        return $res;
    }

    /**
     * 获取代理费率
     * @param int $uid 商户号
     * @param $channel_group_id  支付通道分组ID
     */
    public static function getAgentRate($uid,$channel_group_id){
        $rate = 0;

        $data['uid'] = $uid;
        $profile = Uprofile::quickGet($data);

        //是否 存在  为代理 设定了分组
        if(empty($profile) || $profile['who'] != 2 ||$profile['group_id'] == 0  ) return false;

        $channelGroup = self::channelGroup();

        //1.代理用户分组费率
        $data['type'] = empty($profile['pid'])?0:1; //是否代理分组 平台分组
        $data['uid'] = empty($profile['pid'])?0:$profile['pid'];
        $data['group_id'] = $profile['group_id'];
        $data['channel_id'] = $channel_group_id;
        $data['p_id'] = 0;
        $SysRate = SysRate::quickGet($data); //查询是否有该分组下的通道分组费率

        if(!empty($SysRate['rate'])  && $SysRate['rate'] != '0.0000'  )  $rate = $SysRate['rate'];

        //2.上级的状态
        if($profile['pid'] != 0){
            $res1 = self::getAgentRate($profile['pid'],$channel_group_id);
            if(!empty($res1) ) $rate = max($res1,$rate);  //通道分组没有设置费率的情况
        }

        //3.获取平台支付通道分组费率
        if(empty($rate))  $rate = $channelGroup[$channel_group_id]['c_rate'];//没有记录，统一使用支付产品的默认费率

        return $rate;
    }


    /**获取用户分组的状态和费率
     * @param $group_id  用户分组ID
     * @param $aid 支付通道分组ID 或者支付产品ID
     * @return bool|int|mixed
     */
    public static function getGroupStatus($group_id,$aid){
        $res['code'] = 0;
        $res['id'] = $aid;
        $res['status'] = 0;//对应费率表的状态
        $res['rate'] = 0;//费率
        $res['type'] = 0;//哪种类型的费率

        //没有上级的时候
        if($group_id == 0){
            //系统通道分组
            $channelGroup = self::channelGroup();
            if(!empty($channelGroup[$aid])){
                $res['code'] = 1;
                $res['status'] =  $channelGroup[$aid]['status'];
                $res['type'] = 3;//系统通道分组默认费率类型
                $res['rate'] =  $channelGroup[$aid]['c_rate'];//默认费率
            }
            return $res;
        }


         $Ulevel =   Ulevel::quickGet($group_id); //用户分组
        if(empty($Ulevel)) return $res;


        $data['type'] = $Ulevel['type']; //是否代理商户分组 平台商户分组
        $data['uid'] =  $Ulevel['uid'];
        $data['group_id'] = $group_id;


        if($Ulevel['type1'] == 0){
            //商户分组
            $product = self::product();
            $status = $product[$aid]['status'];

            $data['p_id'] = $aid;
            $data['channel_id'] = 0;

            $SysRate = SysRate::quickGet($data); //查询是否有该分组下的支付产品费率
            if(!empty($SysRate['rate'])  && $SysRate['rate'] != '0.0000' ){
                $res['code'] = 1;
                $res['status'] = $SysRate['status'];//对应费率表的状态
                $res['rate'] = $SysRate['rate'];//费率
                $res['type'] = 1;//用户分组费率类型
            }

            //3.获取支付产品费率
            if(empty($res['rate']) || $res['rate'] == '0.0000' ){
                $res['code'] = 1;
                $res['status'] = $status;
                $res['type'] = 2;//系统费率类型
                $res['rate'] =  $product[$aid]['p_rate'];//没有记录，统一使用支付产品的默认费率
            }

            //已存在用户分组费率 并且 支付产品关闭
            if($status == 0 ){
                $res['type'] = 2;//系统费率类型
                $res['code'] = 1;
                $res['status'] = $status;
            }


        }else{
           //代理分组

            //上级的通道分组
            if($Ulevel['uid'] != 0){
                $group_id1 = \app\common\model\Uprofile::where(['uid'=>$Ulevel['uid']])->value('group_id');
                if(!empty($group_id1)){
                    $res1 = self::getGroupStatus($group_id1,$aid);
                    if(!empty($res1)){
                        $res['code'] = 1;
                        $res['rate'] = $res1['rate'];
                        $res['type'] = 2;//上级通道分组费率类型
                        $res['status'] = $res1['status'];

                    }
                }
            }else{
                //系统通道分组
                $channelGroup = self::channelGroup();
                if(!empty($channelGroup[$aid])){
                    $res['code'] = 1;
                    $res['status'] =  $channelGroup[$aid]['status'];
                    $res['type'] = 3;//系统通道分组默认费率类型
                    $res['rate'] =  $channelGroup[$aid]['c_rate'];//默认费率
                }
            }
            if($res['status'] == 0) return $res;


            $data['channel_id'] = $aid;
            $data['p_id'] = 0;
            $SysRate = SysRate::quickGet($data); //查询是否有该分组下的通道分组费率

            if(!empty($SysRate['rate'])){
                $res['code'] = 1;
                $res['status'] = min($SysRate['status'],$res['status']);//对应费率表的状态
                $res['type'] = 1;//代理分组费率类型
                $res['rate'] = max($SysRate['rate'],$res['rate']);//代理分组费率
            }

            return $res;
        }


      return $res;
    }



}