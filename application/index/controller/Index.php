<?php
namespace app\index\controller;

use app\common\controller\IndexController;


class Index extends IndexController
{
    /**
     * 首页
     *
     *
     * @return mixed
     */
    public function index(){


        return $this->fetch('');
    }

    /**
     * 产品介绍
     *

     *
     * @return mixed
     */
    public function products(){
        return $this->fetch('');
    }

    /**
     * 开发文档
     *

     *
     * @return mixed
     */
    public function document(){
        return $this->fetch('');
    }

    /**
     * Demo演示
     *

     *
     * @return mixed
     */
    public function demo(){
        return $this->fetch('');
    }

    /**
     * 开发指南
     *

     *
     * @return mixed
     */
    public function introduce(){
        return $this->fetch('');
    }

    /**
     * SDK下载
     *

     *
     * @return mixed
     */
    public function sdk(){
        return $this->fetch('');
    }

    /**
     * 关于我们
     *

     *
     * @return mixed
     */
    public function about(){
        return $this->fetch('');
    }


}
