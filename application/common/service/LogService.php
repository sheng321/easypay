<?php


namespace app\common\service;

use app\common\model\ActonRecord;
use app\common\model\SysNode;
use think\facade\Request;

/**
 * 日志服务
 * Class LogService
 * @package app\admin\service
 */
class LogService {


    /**
     * 行为日志记录(数据库)
     * @param string $remark
     */
    public static function record($remark = '' ,$type) {

        $RequestUrl = Request::url();
        empty($remark)?:$data['remark'] = $remark;

        $param = Request::param();

        $exprot = [
            'password',
            'type',
            'page',
            'limit',
            '__token__'
        ];
        foreach ($param as $k =>$val){
            if(in_array($k,$exprot)){
                unset($param[$k]);
            }
        }
        $url = Request::module().'/'.Request::path();


        //获取所有的节点数组
        $nodeArry = SysNode::NodeArr();
        $title = empty($nodeArry[$url])?'':'|'.$nodeArry[$url];

        $data['url']  = $RequestUrl;
        $data['type']  = $type;
        $data['param']  = json_encode($param,320);
        $data['method']  = Request::method().$title;

        ActonRecord::create($data);

    }


}