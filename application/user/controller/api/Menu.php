<?php

namespace app\user\controller\api;
use app\common\controller\UserController;
use think\facade\Cache;

class Menu extends UserController
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
        $name = session('user_info.id') . '_UserMenu';
        \think\facade\Cache::remember($name, function ()use ($name) {
            return  \app\common\model\SysMenu::getUserMenuApi();
        },86400);
        \think\facade\Cache::tag('menu',[$name]);

        return json(Cache::get($name));
    }

}





