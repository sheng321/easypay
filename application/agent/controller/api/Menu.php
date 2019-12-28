<?php

namespace app\agent\controller\api;
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
        $name = session('agent_info.id') . '_AgentMenu';
        \think\facade\Cache::remember($name, function ()use ($name) {
            $menu_list = \app\common\model\SysMenu::getAgentMenuApi();
            \think\facade\Cache::tag('menu')->set($name,$menu_list,86400);
            return \think\facade\Cache::get($name);
        });
        return json(Cache::get($name));
    }

}





