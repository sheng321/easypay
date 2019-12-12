<?php


namespace app\admin\controller\api;


use app\common\controller\AdminController;

class Node extends AdminController {

    /**
     * 获取对应角色的节点数据
     * @param $id
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNodeTree($id) {

        $type = model('\app\common\model\SysAuth')->where(['id' => $id])->value('type');

        $list = [];
        $module = model('\app\common\model\SysNode')->where(['type' => 1, 'is_auth' => 1])->order(['node' => 'asc'])->select();

        $k = 0;
        foreach ($module as $k1 => $val) {

            if($type == 0  && $val['node'] === 'user' ){
                unset($module[$k1]);
                continue;
            }
            if($type == 1  && $val['node'] === 'admin' ){
                unset($module[$k1]);
                continue;
            }

            $list[$k] = [
                'title' => $this->__biuldGetNodeTree($val['node'], $val['title']),
                'value' => $val['id'],
                'data'  => [],
            ];
            $is_checked = model('app\common\model\SysAuthNode')->where(['auth' => $id, 'node' => $val['id']])->find();
            !empty($is_checked) && $list[$k]['checked'] = true;
            $data_1 = model('\app\common\model\SysNode')->where([['type', '=', 2], ['is_auth', '=', 1], ['node', 'LIKE', "{$val['node']}/%"]])->select();
            foreach ($data_1 as $k_1 => $val_1) {
                $list[$k]['data'][$k_1] = [
                    'title' => $this->__biuldGetNodeTree($val_1['node'], $val_1['title']),
                    'value' => $val_1['id'],
                    'data'  => [],
                ];
                $is_checked_1 = model('app\common\model\SysAuthNode')->where(['auth' => $id, 'node' => $val_1['id']])->find();
                !empty($is_checked_1) && $list[$k]['data'][$k_1]['checked'] = true;
                $data_2 = model('\app\common\model\SysNode')->where([['type', '=', 3], ['is_auth', '=', 1], ['node', 'LIKE', "{$val_1['node']}/%"]])->select();
                foreach ($data_2 as $k_2 => $val_2) {
                    $list[$k]['data'][$k_1]['data'][$k_2] = [
                        'title' => $this->__biuldGetNodeTree($val_2['node'], $val_2['title']),
                        'value' => $val_2['id'],
                        'data'  => [],
                    ];
                    $is_checked_2 = model('app\common\model\SysAuthNode')->where(['auth' => $id, 'node' => $val_2['id']])->find();
                    !empty($is_checked_2) && $list[$k]['data'][$k_1]['data'][$k_2]['checked'] = true;
                }
            }

            ++$k;
        }

        return json($list);
    }

    /**
     * 组合数据
     * @param $node
     * @param $title
     * @return string
     */
    protected function __biuldGetNodeTree($node, $title) {
        if (empty($title)) return $node;
        else return $title . '【' . $node . '】';
    }
}