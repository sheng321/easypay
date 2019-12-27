<?php

namespace app\user\controller\api;
use app\common\controller\AgentController;
use think\facade\Cache;

class Menu extends AgentController
{

    /**
     * 根据权限规则生成菜单栏数据
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMenu()
    {
        if (!empty(Cache::tag('menu')->get(session('user_info.id') . '_UserMenu'))) {
            return json(Cache::get(session('user_info.id') . '_UserMenu'));
        } else {
            $menu_list = \app\common\model\SysMenu::getUserMenuApi();
            Cache::tag('menu')->set(session('user_info.id') . '_UserMenu', $menu_list, 86400);
            return json($menu_list);
        }
    }

    /**
     * 获取顶部菜单栏
     * @return mixed
     */
    public function getNav()
    {
        if (!empty(Cache::tag('menu')->get(session('user_info.id') . '_UserMenu'))) {
            $menu_list = Cache::get(session('user_info.id') . '_UserMenu');
        } else {
            $menu_list = \app\common\model\SysMenu::getMenuApi();
            Cache::tag('menu')->set(session('user_info.id') . '_UserMenu', $menu_list, 86400);
        }
        return $menu_list;
    }

}





