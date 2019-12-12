<?php


namespace app\common\service;
use PHPMailer\PHPMailer;
use PHPMailer\SMTP;

class Mail
{

    /**
     * 静态变量保存全局的实例
     * @var null
     */
    private static $_instance = null;

    /**
     * 配置参数
     * @var null
     */
    private static $config = null;

    /**
     * 私有的构造方法
     */
    private function __construct() {

    }

    /**
     * 静态方法 单例模式统一入口
     */
    public static function getInstance() {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        self::$config = \app\common\model\SysConfig::getMailConfig();;
        return self::$_instance;
    }

    /**
     * 系统邮件发送函数
     * @param $tomail
     * @param $name
     * @param string $subject
     * @param string $body
     * @param null $attachment
     * @return bool|string
     * @throws
     */
    public function send($tomail,  $subject = '', $body = '<h1>Hello World</h1>', $attachment = null) {
        // 实例化PHPMailer核心类
        $mail = new PHPMailer();
    // 是否启用smtp的debug进行调试 开发环境建议开启 生产环境注释掉即可 默认关闭debug调试模式
            $mail->SMTPDebug = self::$config['debug'];
    // 使用smtp鉴权方式发送邮件
            $mail->isSMTP();
    // smtp需要鉴权 这个必须是true
            $mail->SMTPAuth = true;
    // 链接qq域名邮箱的服务器地址
            $mail->Host = self::$config['host'];
    // 设置使用ssl加密方式登录鉴权
            $mail->SMTPSecure = 'ssl';
    // 设置ssl连接smtp服务器的远程服务器端口号
            $mail->Port =  self::$config['port'];
    // 设置发送的邮件的编码
            $mail->CharSet = 'UTF-8';
    // 设置发件人昵称 显示在收件人邮件的发件人邮箱地址前的发件人姓名
            $mail->FromName = self::$config['name'];
    // smtp登录的账号 QQ邮箱即可
            $mail->Username = self::$config['username'];
    // smtp登录的密码 使用生成的授权码
            $mail->Password = self::$config['password'];
    // 设置发件人邮箱地址 同登录账号
            $mail->From = self::$config['address'];
    // 邮件正文是否为html编码 注意此处是一个方法
            $mail->isHTML(true);
    // 设置收件人邮箱地址
            $mail->addAddress($tomail);
    // 添加多个收件人 则多次调用方法即可
           // $mail->addAddress('780309705@qq.com');
    // 添加该邮件的主题
            $mail->Subject = $subject?$subject:"【".self::$config['name']."】 - 开户邮箱激活";
    // 添加邮件正文
            $mail->Body = $body;
    // 为该邮件添加附件
          //  $mail->addAttachment('C:\Users\maolimeng\Desktop\PHPMailer-master.zip');
        if (is_array($attachment)) { // 添加附件
            foreach ($attachment as $file) {
                is_file($file) && $mail->AddAttachment($file);
            }
        }

    // 发送邮件 返回状态
        return $mail->Send() ? true : $mail->ErrorInfo;

    }


}