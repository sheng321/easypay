<?php
namespace app\pay\controller;
use app\common\controller\PayController;

/**
 * 二维码
 * Class Qr
 * @package app\pay\controller
 */
class Qr extends PayController{


    public function index(){

        $type =  $this->request->get('type/d',0);
        $label = $this->request->get('label/s','');
        $data = $this->request->get('data/s','');
        if($type != 0) $data = base64_decode($data);

        ob_end_clean(); //清除缓存
        $qrCode = new \Endroid\QrCode\QrCode();
        $qrCode->setText($data)
            ->setSize(300)
            ->setPadding(10)
            ->setErrorCorrection('high')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r'=>255,'g'=>255,'b'=>255,'a'=>0))
            ->setLabel($label)
            ->setLabelFontSize(16)
            ->setImageType(\Endroid\QrCode\QrCode::IMAGE_TYPE_PNG);

        header('Content-Type: '.$qrCode->getContentType());
        $qrCode->render();
    }


}
