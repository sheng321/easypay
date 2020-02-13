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

    /**弹窗信息
     * @return \think\response\Json
     */
    public function message()
    {
        $data =  model('app\common\model\Message')->where([
            ['title','=','首页弹窗显示'],
            ['type','=',0]
        ])->value('data');

        if(empty($data)) return __error($data);
        return __success(htmlspecialchars_decode($data));
    }

}





