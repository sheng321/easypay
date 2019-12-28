<?php
// 代理端

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
        return \app\common\service\AuthService::checkUserNode($node);
    }
}