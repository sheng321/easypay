<?php

namespace app\common\model;


use app\common\service\ModelService;

class Nav extends ModelService {

    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_system_nav';

    /**
     * 获取快捷导航
     * @param int $limit
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getQuickNav($limit = 6) {
        $list = self::where(['status' => 1])->order(['sort' => 'desc', 'create_at' => 'desc'])
            ->limit($limit)->select()->each(function ($item, $key) {
                $item['href'] = url($item['href']);
            });
        return $list;
    }

}