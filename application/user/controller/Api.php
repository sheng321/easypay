<?php
namespace app\user\controller;

use app\common\controller\UserController;

/**
 * Api 管理
 * Class Api
 * @package app\user\controller
 */
class Api extends UserController
{

    /**通道费率
     * @return mixed
     */
    public function index()
    {
        $basic_data = [
            'title'=> '通道费率',
        ];
        return $this->fetch('', $basic_data);
    }

    /**
     * 开发文档
     * @return mixed
     */
    public function api() {
        return $this->fetch('');
    }

    public function secret(){
        if ($this->request->isPost()) {

            $post = $this->request->only('paypwd', 'post');

            $post['paypwd1'] =  $this->user['profile']['pay_pwd'];

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.paypwd');
            if (true !== $validate) return __error($validate);

            //修改密码数据
            return __success('',$this->user['profile']['secret']);
        }
    }


}
