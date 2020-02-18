<?php

namespace app\admin\controller\api;

use app\common\controller\AdminController;

/**
 * 后台公共接口
 * Class Common
 * @package app\api\controller\admin
 */
class Common extends AdminController {

    /**
     * 打开图片上传窗口
     * @return \think\response\Json
     */
    public function uploadIamge($type = 'one') {
        $SysInfo = cache('SysInfo');
        if (!isset($SysInfo['AdminModuleName'])) {
            return __error('后台绑定模块名数据有误，请刷新缓存或修改数据库配置！');
        }
        $this->redirect(url("{$SysInfo['AdminModuleName']}.php\\tool.upload\image") . "?type=" . $type);
    }
    /**
     * 后台刷新缓存接口
     * @return \think\response\Json
     */
    public function clearCache() {
        if (clear_cache()) {
            return __success('缓存刷新成功！');
        } else {
            return __error('缓存刷新失败！');
        }
    }

    /**
     * 语音播报任务
     * @return \think\response\Json
     */
    public function task() {
        $time = date('Y-m-d H:i:s',time() - 30*60);
        $where = [
           // ['create_at','>',$time],
            ['type','in',[5,6,7]],
            ['status','=',0],
        ];
       $data = \app\common\model\Message::where($where)->select()->toArray();

       dump($data);
       $msg = '';
       foreach ($data as $k =>$v){
           $msg .= $v['data'];
       }

        if(empty($msg))  return __error($msg);

        return $msg;
    }


}