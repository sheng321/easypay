<?php

namespace app\admin\controller\api;

use app\common\controller\AdminController;
use think\facade\Cache;

class Menu extends AdminController
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

        $name = session('admin_info.id') . '_AdminMenu';
        \think\facade\Cache::remember($name, function ()use ($name) {
            return \app\common\model\SysMenu::getMenuApi();
        },86400);
        \think\facade\Cache::tag('menu',$name);
        return json(Cache::get($name));


    }

    /**
     * 获取顶部菜单栏
     * @return mixed
     */
    public function getNav()
    {
        $name = session('admin_info.id') . '_AdminMenu';
        \think\facade\Cache::remember($name, function ()use ($name) {
            return \app\common\model\SysMenu::getMenuApi();
        },86400);
        \think\facade\Cache::tag('menu',$name);
        return Cache::get($name);
    }

}





