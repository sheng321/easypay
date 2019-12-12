<?php

namespace app\common\model;


use app\common\service\ModelService;

class SysAuthNode extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_system_auth_node';

    /**
     * 保存授权信息
     * @param $insertAll
     * @return \think\response\Json
     * @throws \Exception
     */
    public function authorize($insertAll) {
        $save = $this->saveAll($insertAll);
        if (!empty($save)) return __success('保存成功！');
        return __error('保存失败');
    }
}