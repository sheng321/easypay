<?php

namespace app\common\model;

use app\common\service\ModelService;

/** Message
 */
class Message extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_message';

    /**
     * 用户分组列表
     * @param int $page
     * @param int $limit
     * @param array $search
     * @param array $where
     * @return array
     */
    public function aList($page = 1, $limit = 10, $search = [], $where = []) {
        //搜索条件
        $searchField['eq'] = ['type'];

        $where = search($search,$searchField,$where);
        $field = '*';
        $count = $this->where($where)->count(1);

        $order = ['id'=>'desc'];
        $data = $this->where($where)->field($field)->page($page, $limit)->order($order)->select()->toArray();

        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';
        $info = [
            'limit'        => $limit,
            'page_current' => $page,
            'page_sum'     => ceil($count / $limit),
        ];
        $list = [
            'code'  => 0,
            'msg'   => $msg,
            'count' => $count,
            'info'  => $info,
            'data'  => $data,
        ];
        return $list;
    }


    /**添加任务
     * @param $title
     * @param $msg
     * @param $type  5客服任务 6财务任务 7 技术任务
     * @param int $time
     * @return bool
     */
    public static function add_task($title,$msg,$type,$time = 1)
    {
        $data['title'] = $title;
        $data['data'] = $msg;
        $data['type'] = $type;
        $find =  self::where($data)->find();
        if(empty($find)){
            $data['time'] = $time;
            return  self::create($data);
        }
        $Time  =  strtotime($find['create_at'])  - time() - 30*60;//30分钟
        if($Time > 0){
            $data['time'] = $time;
            return  self::create($data);
        }

        return true;
    }

}