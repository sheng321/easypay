<?php

namespace app\common\model;

use app\common\service\ModelService;

class SysMenu extends ModelService {

    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_system_menu';



    /**
     * 获取首页信息
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getHome() {
        $where_home = [
            ['id', '=', 1],
            ['status', '=', 1],
        ];
        $home = $this->field('id, title, icon, href')->where($where_home)->find();
        !empty($home) && $home['href'] = url($home['href']);
        return $home;
    }

    /**
     * 获取菜单栏头部
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getNav($type = 0) {
        $where_nav = [
            ['id', '<>', 1],
            ['pid', '=', 0],
            ['status', '=', 1],
            ['type', '=', $type],
        ];
        $order_nav = [
            'sort'      => 'desc',
            'create_at' => 'asc',
        ];

        //查询顶级菜单栏数据
        $nav = self::field('id,pid,title, icon,href')->where($where_nav)->order($order_nav)->select();

        //去除空菜单
        foreach ($nav as $k => $val) {
            if(empty($val['href']) || $val['href'] == '#'){
                $menu = self::where(['pid' => $nav[$k]['id'], 'status' => 1])->select()->toArray();
                if (empty($menu)) unset($nav[$k]);
            }
        }

        return $nav;
    }

    /**
     * 获取菜单栏数据
     * @param array $search 搜索条件
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function menuList($search = [], $where = []) {

            //搜索条件
            $searchField['eq'] = ['type','status'];
            $searchField['like'] = ['title','href'];
            $searchField['time'] = ['create_at'];

            $where = search($search,$searchField,$where);

            $field = 'id, pid, title, icon, href,  sort, status, create_at, create_by,type';

            $menu_list = $this->field($field)->where($where)->order(['pid' => 'asc', 'sort' => 'asc', 'create_at' => 'desc'])->select();
            empty($menu_list) ? $msg = '暂无数据！' : $msg = '查询成功！';

            //修改菜单栏数据格式
            $this->__buildMenu($menu_list);
            return [
                'code'  => 0,
                'msg'   => $msg,
                'count' => count($menu_list),
                'data'  => $menu_list,
            ];

    }

    /**
     * 修改搜索菜单栏数据格式
     * @param     $list
     * @param int $i
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function __buildMenu(&$list) {
        foreach ($list as &$vo) {
            $i = 1;
            if ($vo['pid'] != 0) {
                $i++;
                $nav = $this->where('id', $vo['pid'])->find();
                !empty($nav) && ($nav['pid'] != 0 && $i++);
            }
            $vo['title'] = replace_menu_title($vo['title'], $i);
            ($vo['id'] == 1 || $i >= 3) ? $vo['is_add'] = false : $vo['is_add'] = true;
        }
    }

    /**
     * 使用回调获取菜单栏数据
     * @param int   $pid  上级id
     * @param array $menu 菜单数据
     * @param int   $i    序号
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMenu($pid = 0, &$menu = [], $i = 0,$type = 0) {
        $i++;
        $field = 'id, pid, title, icon, href,  sort, status, create_at, create_by,type';
        $where_nav = [
            ['pid', '=', $pid],
            ['type', '=', $type],
        ];
        $order = [
            'sort'      => 'asc',
            'create_at' => 'desc',
        ];
        $nav = $this->field($field)->where($where_nav)->order($order)->select();
        foreach ($nav as $vo) {
            ($vo['id'] == 1 || $i >= 3) ? $vo['is_add'] = false : $vo['is_add'] = true;
            $vo['title'] = replace_menu_title($vo['title'], $i);
            $menu[] = $vo;
            $this->getMenu($type,$vo['id'], $menu, $i);
        }
        return $menu;
    }

    /**
     * 创建菜单时获取上级的菜单
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUpMenu($type = 0) {
        $field = 'id, pid, title';
        $where_nav = [
            ['id', '<>', 1],
            ['pid', '=', 0],
            ['type', '=', $type],
        ];
        $order = [
            'sort'      => 'asc',
            'create_at' => 'desc',
        ];

        //最顶级
        $first_list = $this->where($where_nav)->field($field)->order($order)->select();

        //组合菜单数据
        $menu_list[] = [
            'id'    => 0,
            'pid'   => 0,
            'title' => '顶级菜单',
        ];
        foreach ($first_list as &$vo) {
            $vo['title'] = replace_menu_title($vo['title'], 1);
            $menu_list[] = $vo;
            $where_second = [['pid', '=', $vo['id']]];
            $second_list = $this->where($where_second)->field($field)->order($order)->select();
            foreach ($second_list as &$vl) {
                $vl['title'] = replace_menu_title($vl['title'], 2);
                $menu_list[] = $vl;
            }
        }
        return $menu_list;
    }

    /**
     * 获取系统导航菜单
     * @param array $menu_list
     * @return array
     */
    public static function getMenuApi($type = 0) {
        $field = 'id, pid, title, icon, href';
        $order = ['sort' => 'desc', 'create_at' => 'desc'];

        $nav = self::getNav($type)->toArray();

        foreach ($nav as $k =>  $vo) {

            $i = 0;
            $where_first_menu = [['pid', '=', $vo['id']], ['status', '=', 1],['type', '=', $type]];
            $first_menu = self::field($field)->where($where_first_menu)->order($order)->select()->toArray();

            foreach ($first_menu as $vo_1) {
                if (auth($vo_1['href'])) {
                    if (!empty($vo_1['href']) && $vo_1['href'] != "#") {
                        $vo_1['href'] = url($vo_1['href']);
                    }

                    $nav[$k]['children'][$i] = $vo_1;
                    $where_second_menu = [['pid', '=', $vo_1['id']], ['status', '=', 1],['type', '=', $type]];
                    $second_menu = self::field($field)->where($where_second_menu)->order($order)->select()->toArray();
                    foreach ($second_menu as $vo_2) {
                        if (auth($vo_2['href'])) {
                            if (!empty($vo_2['href']) && $vo_2['href'] != "#") {
                                $vo_2['href'] = url($vo_2['href']);
                            }

                            $nav[$k]['children'][$i]['children'][] = $vo_2;
                        }
                    }
                    //去除空菜单
                    if (!isset($nav[$k]['children'][$i]['children'])) {
                        if ($nav[$k]['children'][$i]['href'] == '#' || $nav[$k]['children'][$i]['href'] == '') {
                            unset($nav[$k]['children'][$i]);
                        } else {
                            $i++;
                        }
                    } else {
                        $i++;
                    }
                }
            }
        }
        return $nav;
    }

    public static function getUserMenuApi() {
        $data = self::getMenuApi(1);

        foreach ($data as $k => $v){
            if(empty($v['children']) && $v['href'] == '#') continue;
            $data[$k]['icon'] = "layui-icon layui-icon-".$v['icon'];

            $data[$k]['url'] = $v['href'];
            unset($data[$k]['href']);
            $data[$k]['name'] = $v['title'];
            unset($data[$k]['title']);

            if(empty($v['children'])) continue;
            foreach ($v['children'] as $k1 => $v1){
                if( empty($v1['children']) &&  (empty($v1['href']) || $v1['href'] == '#') ) continue;

                $data[$k]['children'][$k1]['icon'] = "layui-icon layui-icon-".$v1['icon'];

                $data[$k]['children'][$k1]['url'] = $v1['href'];
                unset($data[$k]['children'][$k1]['href']);
                $data[$k]['children'][$k1]['name'] = $v1['title'];
                unset($data[$k]['children'][$k1]['title']);

                if(empty($v1['children'])) continue;
                foreach ($v1['children'] as $k2 => $v2){
                    if(empty($v2['href']) || $v2['href'] == '#') continue;
                     $data[$k]['children'][$k1]['children'][$k2]['icon'] = "layui-icon layui-icon-".$v2['icon'];

                    $data[$k]['children'][$k1]['children'][$k2]['url'] = $v2['href'];
                    unset($data[$k]['children'][$k1]['children'][$k2]['href']);
                    $data[$k]['children'][$k1]['children'][$k2]['name'] = $v2['title'];
                    unset($data[$k]['children'][$k1]['children'][$k2]['title']);
                }
            }
        }


        return ['status'=>0,'msg'=>"ok",'data'=>$data];
    }

}