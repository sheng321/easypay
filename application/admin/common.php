<?php


//后台公共文件


if (!function_exists('replace_menu_title')) {

    /**
     * 格式化菜单名称进行输出
     * @param $var 变量名
     * @param int $number 循环次数
     * @return string
     */
    function replace_menu_title($var, $number = 1) {
        $prefix = '';
        for ($i = 1; $i < $number; $i++) {
            //$prefix .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;├ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $prefix .= '&nbsp;├ &nbsp;';
        }
        return $prefix . $var;
    }
}

if (!function_exists('auth')) {

    /**
     * 权限节点判断
     * @param $node 节点
     * @return bool （true：有权限，false：无权限）
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function auth($node)
    {
        return \app\common\service\AuthService::checkNode($node);
    }
}