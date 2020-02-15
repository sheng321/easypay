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
            $value = \app\common\model\SysMenu::getAgentMenuApi();
            \think\facade\Cache::tag('menu')->set($name,$value,86400);
            return $value;
        },86400);

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





