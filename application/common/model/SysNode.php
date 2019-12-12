<?php

namespace app\common\model;

use app\common\service\ModelService;


class SysNode extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_system_node';

    /**
     * 节点列表
     * @param int   $page   当前页
     * @param int   $limit  每页显示数量
     * @param array $search 搜索条件 （array）
     * @param array $where  组成的条件
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function nodeList($page = 1, $limit = 10, $search = [], $where = []) {

        //搜索条件
        foreach ($search as $key => $value) {
            if ($key == 'is_auth' && $value != '') {
                $where[] = [$key, '=', $value];
            } elseif ($key == 'create_at' && $value != '') {
                $value_list = explode(" - ", $value);
                $where[] = [$key, 'BETWEEN', ["{$value_list[0]} 00:00:00", "{$value_list[1]} 23:59:59"]];
            } else {
                !empty($value) && $where[] = [$key, 'LIKE', '%' . $value . '%'];
            }
        }


        $field = 'id, node, title , is_auth,command,  create_at';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['node asc'])->select();
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


    /**
     * 根据模块名称获取节点
     * @param       $module
     * @param array $node_list
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function nodeModuleList($module, $node_list = []) {
        $modules = $this->where(['type' => 1, 'node' => $module])->order(['node'=>'asc'])->select()->toArray();
        foreach ($modules as &$vo_m) {
            $i = 1;
            $vo_module = $vo_m;
            $vo_module['node'] = replace_menu_title($vo_module['node'], $i);
            $node_list[] = $vo_module;
            $controller = $this->where([['type', '=', 2], ['node', 'LIKE', "{$vo_m['node']}/%"]])->order(['node'=>'asc'])->select()->toArray();
            foreach ($controller as &$vo_c) {
                $i = 2;
                $vo_controller = $vo_c;
                $vo_controller['node'] = replace_menu_title($vo_controller['node'], $i);
                $node_list[] = $vo_controller;
                $action = $this->where([['type', '=', 3], ['node', 'LIKE', "{$vo_c['node']}/%"]])->order(['node'=>'asc'])->select()->toArray();
                foreach ($action as &$vo_a) {
                    $i = 3;
                    $vo_action = $vo_a;
                    $vo_action['node'] = replace_menu_title($vo_action['node'], $i);
                    $node_list[] = $vo_action;
                }
            }
        }
        return [
            'code'  => 0,
            'msg'   => '查询成功',
            'count' => count($node_list),
            'data'  => $node_list,
        ];
    }


    /**
     * 节点与路由数组
     * @param array $modules
     */
    public static function NodeArr() {
        \think\facade\Cache::remember('nodeArr', function () {
            $NodeArr = self::column('node,title');
            \think\facade\Cache::tag('SysNode')->set('nodeArr',$NodeArr);
            return \think\facade\Cache::get('nodeArr');
        });
        return \think\facade\Cache::get('nodeArr');
    }

    /**
     * ID与口令节点数组
     * @param array $modules
     */
    public static function wordArr() {
        \think\facade\Cache::remember('wordArr', function () {
            $wordArr = self::where([['command','=',1]])->column('id,node');
            foreach ($wordArr as $k => $v){
                $wordArr[$k] = url($v);
            }
            \think\facade\Cache::tag('SysNode')->set('wordArr',$wordArr);
            return \think\facade\Cache::get('wordArr');
        });
        return \think\facade\Cache::get('wordArr');
    }



}