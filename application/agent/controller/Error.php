<?php

namespace app\agent\controller;
use think\Controller;
class Error extends Controller{
    public function _empty(){
        $this->redirect('@agent/index');
    }
}