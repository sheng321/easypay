<?php

// 应用公共文件

if (!function_exists('addTask')) {

    /** 添加语音播报任务
     * @param $title
     * @param $msg
     * @param $type 5客服任务 6财务任务 7 技术任务
     * @param int $time
     * @return bool
     */
    function addTask($title,$msg,$type,$time = 5)
    {
        \app\common\model\Message::add_task($title,$msg,$type,$time);
        return  true;
    }
}


if (!function_exists('getQrcode')) {
    /**
     * 生成指定的二维码
     * @param string $data
     * @param string $label 标签参数
     * @param string $type 0 $data 为url时
     */
function getQrcode($data,$type = 0,$label = '') {
    if($type != 0) $data = base64_encode($data);
   return  urldecode( url('@pay/qr/index','', true,true).'?data='.$data.'&label='.$label.'&type='.$type);
}
}

if (!function_exists('convertAmountToCn')) {
/**
 * 修改config的函数
 *  @param $name 文件名
 * @param $arr 一维数组
 * @return bool 返回状态
 */
function setconfig($name,$arr)
{
    $pats = array();
    $reps = array();
    foreach ($arr as $k => $v){
        $pats[$k] = '/\'' . $k . '\'(.*?),/';
        $reps[$k] = "'". $k. "'". "=>" . "'".$v ."',";
    }

    $fileurl = Env::get('config_path'). $name.".php";
    $string = file_get_contents($fileurl); //加载配置文件
    $string = preg_replace($pats, $reps, $string); // 正则查找然后替换
    file_put_contents($fileurl, $string); // 写入配置文件
    return true;
}
}




if (!function_exists('convertAmountToCn')) {

/**
 * 将数值金额转换为中文大写金额
 * @param $amount float 金额(支持到分)
 * @param $type   int   补整类型,0:到角补整;1:到元补整
 * @return mixed 中文大写金额
 */
function convertAmountToCn($amount, $type = 1) {
    // 判断输出的金额是否为数字或数字字符串
    if(!is_numeric($amount)){
        return "要转换的金额只能为数字!";
    }

    // 金额为0,则直接输出"零元整"
    if($amount == 0) {
        return "零元整";
    }

    // 金额不能为负数
    if($amount < 0) {
        return "要转换的金额不能为负数!";
    }

    // 金额不能超过万亿,即12位
    if(strlen($amount) > 12) {
        return "要转换的金额不能为万亿及更高金额!";
    }

    // 预定义中文转换的数组
    $digital = array('零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
    // 预定义单位转换的数组
    $position = array('仟', '佰', '拾', '亿', '仟', '佰', '拾', '万', '仟', '佰', '拾', '元');

    // 将金额的数值字符串拆分成数组
    $amountArr = explode('.', $amount);

    // 将整数位的数值字符串拆分成数组
    $integerArr = str_split($amountArr[0], 1);

    // 将整数部分替换成大写汉字
    $result = '';
    $integerArrLength = count($integerArr);     // 整数位数组的长度
    $positionLength = count($position);         // 单位数组的长度
    for($i = 0; $i < $integerArrLength; $i++) {
        // 如果数值不为0,则正常转换
        if($integerArr[$i] != 0){
            $result = $result . $digital[$integerArr[$i]] . $position[$positionLength - $integerArrLength + $i];
        }else{
            // 如果数值为0, 且单位是亿,万,元这三个的时候,则直接显示单位
            if(($positionLength - $integerArrLength + $i + 1)%4 == 0){
                $result = $result . $position[$positionLength - $integerArrLength + $i];
            }
        }
    }

    // 如果小数位也要转换
    if($type == 0) {
        // 将小数位的数值字符串拆分成数组
        $decimalArr = str_split($amountArr[1], 1);
        // 将角替换成大写汉字. 如果为0,则不替换
        if($decimalArr[0] != 0){
            $result = $result . $digital[$decimalArr[0]] . '角';
        }
        // 将分替换成大写汉字. 如果为0,则不替换
        if($decimalArr[1] != 0){
            $result = $result . $digital[$decimalArr[1]] . '分';
        }
    }else{
        $result = $result . '整';
    }
    return $result;
}

}




if (!function_exists('getSessionid')) {

    /**
     * 获取sessionID
     */
    function getSessionid() {
        $num = mt_rand(1,10000);
        session($num,'');
        $session_id  = session_id();
        session($num,null);
        return $session_id;
    }
}


if (!function_exists('timeToDate')) {

    /**
     * 时间戳操作
     * @param int $second
     * @param int $minute
     * @param int $hour
     * @param int $day
     * @param int $week
     * @param int $month
     * @param int $year
     * @return false|string
     */
    function timeToDate($second = 0,$minute = 0,$hour = 0,$day = 0,$week = 0,$month = 0,$year = 0) {
        $t = time();//指定时间戳
        $flag = false;
        if(!empty($second)) $t = strtotime("{$second}second",$t);
        if(!empty($minute)) $t = strtotime("{$minute}minute",$t);
        if(!empty($hour)) $t = strtotime("{$hour}hour",$t);
        if(!empty($day)){
            $t = strtotime("{$day}day",$t);
            $flag = true;
        }
        if(!empty($week)){
            $t = strtotime("{$week}week",$t);
            $flag = true;
        }
        if(!empty($month)){
            $t = strtotime("{$month}month",$t);
            $flag = true;
        }
        if(!empty($year)){
            $t = strtotime("{$year}year",$t);
            $flag = true;
        }

        if($flag){
            $date =  date('Y-m-d ',$t).'00:00:00';
        }else{
            $date =  date('Y-m-d H:i:s',$t);
        }

        return $date;
    }
}

if (!function_exists('getGoogleQr')) {
    /**
     * 获取谷歌验证二维码
     */
    function getGoogleQr($google_token) {
        if(empty($google_token))  return false;

        //第一个参数是"标识",第二个参数为"安全密匙SecretKey" 生成二维码信息
        $qrCodeUrl = (new \tool\Goole())->getQRCodeGoogleUrl(Config('google.domain'), $google_token);
        return $qrCodeUrl;

    }
}

if (!function_exists('getNamebyId')) {

    /**
     * 后台
     * 根据ID获取用户名
     */
    function getNamebyId($id)
    {
        $user = \app\common\model\SysAdmin::idArr();
        if(empty($user[$id])) return '';
        return $user[$id];

    }
}

if (!function_exists('getUnamebyId')) {
    /**
     * 商户端
     * 根据ID获取用户端账号
     */
    function getUnamebyId($id)
    {
        $user = \app\common\model\Umember::id2username();
        if(empty($user[$id])) return '';
        return $user[$id];

    }
}




if (!function_exists('getTitlebyId')) {

    /**
     * 后台
     * 根据ID获取权限组
     */
    function getTitlebyId($id)
    {
        $user = \app\common\model\SysAdmin::titleArr();
        if(empty($user[$id])) return '';
        return $user[$id];

    }
}
if (!function_exists('getUtitlebyId')) {

    /**
     * 商户端
     * 根据ID获取权限组
     */
    function getUtitlebyId($id)
    {
        $user = \app\common\model\Umember::titleArr();
        if(empty($user[$id])) return '';
        return $user[$id];

    }
}

if (!function_exists('getIdbyUtitle')) {

    /**
     * 商户端
     * 根据权限组获取ID
     */
    function getIdbyUtitle($title)
    {
        $user = \app\common\model\Umember::title2id();
        if(empty($user[$title])) return '';
        return $user[$title];

    }
}

if (!function_exists('getIdbyTitle')) {

    /**
     * 后台
     * 根据权限组获取ID
     */
    function getIdbyTitle($title)
    {
        $user = \app\common\model\SysAdmin::title2id();
        if(empty($user[$title])) return '';
        return $user[$title];

    }
}

if (!function_exists('getOrderId')) {

    function getOrderId($prefix = 'H'){

        return $prefix.date('YmdHis', time()) . substr(microtime(), 2, 4) . sprintf('%03d', rand(1000, 9999));
    
    }
}

if (!function_exists('getIdbyName')) {
    /**
     * 后台
     * 根据用户名获取ID
     */
    function getIdbyName($username)
    {
        $user = \app\common\model\SysAdmin::nickArr();
        if(!isset($user[$username])) return '';
        return $user[$username];
    }
}

if (!function_exists('getIdbyUname')) {
    /**
     * 商户端
     * 根据用户名获取ID
     */
    function getIdbyUname($username)
    {
        $user = \app\common\model\Umember::username2id();
        if(!isset($user[$username])) return '';
        return $user[$username];
    }
}


if (!function_exists('getUidbyId')) {
    /**
     * 商户端
     * 根据ID获取Uid
     */
    function getUidbyId($id)
    {
        $user = \app\common\model\Umember::id2uid();
        if(!isset($user[$id])) return '';
        return $user[$id];
    }
}

if (!function_exists('getIdbyUid')) {
    /**
     * 商户端
     * 根据Uid获取id  根据商户号获取ID
     */
    function getIdbyUid($uid)
    {
        $user = \app\common\model\Umember::uid2id();
        if(!isset($user[$uid])) return '';
        return $user[$uid];
    }
}


if (!function_exists('search')) {
    /**
     * 搜索条件
     */
    function search($search,$field,$where = []) {

        foreach ($search as $key => $value) {
            if($value !== 0 && $value == '') continue;

            if(!empty($field['eq']) && in_array($key,$field['eq'])){
                $where[] = [$key, '=', $value];
                continue;
            }
            if(!empty($field['in']) && in_array($key,$field['in'])){
                $where[] = [$key, 'in', $value];
                continue;
            }
            //约等于
            if(!empty($field['like']) && in_array($key,$field['like'])){
                !empty($value) && $where[] = [$key, 'LIKE', '%' . $value . '%'];
                continue;
            }
            //为了节省性能
            if(!empty($field['left_like']) && in_array($key,$field['left_like'])){
                !empty($value) && $where[] = [$key, 'LIKE',  $value . '%'];
                continue;
            }

            //时间
            if(!empty($field['time']) && in_array($key,$field['time'])){
                $value_list = explode(" - ", $value);
                $where[] = [$key, 'BETWEEN', ["{$value_list[0]} 00:00:00", "{$value_list[1]} 23:59:59"]];
                continue;
            }
        }

        return $where;
    }
}


if (!function_exists('getOrder')) {
    /**
     * 获取订单号
     */
    function getOrder($predix = 'o')
    {
        //生成20位唯一订单号码，格式：YYYY-MMDD-HHII-SS-NNNN,NNNN-CC，
        //其中：YY=年份，MM=月份，DD=日期，HH=24格式小时，II=分，SS=秒，NNNNNNNN=随机数，CC=检查码

        @date_default_timezone_set("PRC");
        //订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
        $order_id_main = date('ymdHis') . rand(10000, 99999);
        //订单号码主体长度
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for ($i = 0; $i < $order_id_len; $i++) {
            $order_id_sum += (int)(substr($order_id_main, $i, 1));
        }
        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
        $order_id = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
        return $predix . $order_id;
    }
}



if (!function_exists('get_location')) {
    /**
     * 获取IP地址
     * @param $ip
     * @return mixed|string
     */
    function get_location($ip = null) {
        empty($ip) && $ip = get_client_ip();

        \think\facade\Cache::remember('location_'.$ip, function () use($ip) {
            $Ip = new \tool\IpLocation(); // 实例化类 参数表示IP地址库文件
            $value = $Ip->getlocation($ip);
            \think\facade\Cache::tag('Ip')->set('location_'.$ip,$value,60);
            return $value; // 获取某个IP地址所在的位置
        },60);

        return \think\facade\Cache::get('location_'.$ip);
    }
}

if (!function_exists('is_china')) {
    /**
     * 获取IP地址
     * @param $ip
     * @return mixed|string
     */
    function is_china($ip = null) {
        empty($ip) && $ip = get_client_ip();
        $location =  get_location($ip);
        if(!empty($location['country']) && $location['country'] == '中国') return true;
        return false;
    }
}




if (!function_exists('get_client_ip')) {

    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param boolean $adv  是否进行高级模式获取（有可能被伪装）
     * @return string
     */
    function get_client_ip($type = 0, $adv = false)
    {
        return request()->ip($type, $adv);
    }
}
if (!function_exists('__log')) {

    /**
     * 写入系统日志
     * @param $data 数据
     * @param $type 日志类型 1 后台 2 会员 3 异常日志
     */
    function __log($data,$type = 1){
        if(is_array($data)) $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        \app\common\service\LogService::record($data,$type);
    }
}




if (!function_exists('check_word')) {

    /**
     * 口令节点判断
     * @param $node 节点
     * @return bool （true：有权限，false：无权限）
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function check_word($node = '' )
    {
        return \app\common\service\AuthService::checkCommand($node);
    }
}

if (!function_exists('logs')) {
    /**
     * 自定义记录日志
     */
    function logs($data,$type = 'logs') {
        \think\facade\Log::init([
            // 日志保存目录
            'path'        => \think\facade\Env::get('root_path') ."logs/{$type}/",
            // 日志记录级别
            'level'       => ['info'],
            // 最大日志文件数量
            'max_files'   => 1,
            'close'   => false,
        ]);

        \think\facade\Log::write($data,'info');
    }
}

if (!function_exists('exceptions')) {
    /**
     * 抛出自定义异常
     */
    function exceptions($msg,$data=[]) {

        $result['code'] = 0;
        $result['msg'] = '未知错误~';
        $result['data'] = $data;
        $result['wait'] = 5;

        $url = app('request')->url(true);

        dump($url);
        if(is_array($msg)){
            if(array_key_exists('code',$msg)) $result['code'] = $msg['code'];
            if(array_key_exists('msg',$msg)) $result['msg'] = $msg['msg'];
            if(array_key_exists('url',$msg)) $url = $msg['url'];
            if(array_key_exists('wait',$msg)) $result['wait'] = $msg['wait'];
        }else{
            $result['msg'] = $msg;
        }

        if(Request()->isAjax()){
            throw new \think\exception\HttpResponseException(json($result));
        }else{
            if (is_null($url)) {
                $url = Request()->isAjax() ? '' : 'javascript:history.back(-2);';
            } elseif ('' !== $url) {
                $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : app('request')->build($url);

            }
            $result['url'] = $url;

            $response = \think\facade\Response::create($result, 'jump')->options(['jump_template' => config('app.dispatch_error_tmpl') ]);
            throw new \think\exception\HttpResponseException( $response);
        }
    }
}

if (!function_exists('alert')) {

    /**
     * 弹出层提示
     * @param string $msg 提示信息
     * @param string $url 跳转链接
     * @param int $time 停留时间 默认2秒
     * @param int $icon 提示图标
     * @return string
     */
    function alert($msg = '', $url = '', $time = 3, $icon = 6)
    {
        $success = '<meta name="renderer" content="webkit">';
        $success .= '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">';
        $success .= '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">';
        $success .= '<script type="text/javascript" src="/static/plugs/jquery/jquery-2.2.4.min.js"></script>';
        $success .= '<script type="text/javascript" src="/static/plugs/layui-layer/layer.js"></script>';
        if (empty($url)) {
            $success .= '<script      >$(function(){layer.msg("' . $msg . '", {icon: ' . $icon . ', time: ' . ($time * 1000) . '});})</script>';
        } else {
            if($url == 'reload'){
                //关闭Iframe弹窗并刷新
                $success .= '<script       >$(function(){layer.msg("' . $msg . '",{icon:' . $icon . ',time:' . ($time * 1000) . '});setTimeout(function(){parent.location.reload();},2000)});</script>';
            }else{
                $success .= '<script      >$(function(){layer.msg("' . $msg . '",{icon:' . $icon . ',time:' . ($time * 1000) . '});setTimeout(function(){self.location.href="' . $url . '"},2000)});</script>';

            }
        }
        return $success;
    }
}

if (!function_exists('msg_success')) {

    /**
     * 成功时弹出层提示信息
     * @param string $msg 提示信息
     * @param string $url 跳转链接
     * @param int $time 停留时间 默认2秒
     * @param int $icon 提示图标
     * @return string
     */
    function msg_success($msg = '', $url = '', $time = 3, $icon = 1)
    {
        return alert($msg, $url, $time, $icon);
    }
}

if (!function_exists('msg_error')) {

    /**
     * 失败时弹出层提示信息
     * @param string $msg 提示信息
     * @param string $url 跳转链接
     * @param int $time 停留时间 默认2秒
     * @param int $icon 提示图标
     * @return string
     */
    function msg_error($msg = '', $url = '', $time = 3, $icon = 2)
    {
        return alert($msg, $url, $time, $icon);
    }
}


if (!function_exists('__jsuccess')) {

    /**
     * 接口成功时返回的信息
     * @param $msg 消息
     * @return \think\response\Json
     */
    function __jsuccess($msg, $data = [])
    {
        throw new \think\exception\HttpResponseException(json(['status' => 'success', 'msg' => $msg, 'data' => $data]));
    }
}

if (!function_exists('__jerror')) {

    /**
     * 接口错误时返回的信息
     * @param $msg 消息
     * @return \think\response\Json
     */
    function __jerror($msg, $data = [])
    {
        throw new \think\exception\HttpResponseException(json(['status' => 'error', 'msg' => $msg, 'data' => $data]));
    }
}



if (!function_exists('__success')) {

    /**
     * 成功时返回的信息
     * @param $msg 消息
     * @return \think\response\Json
     */
    function __success($msg, $data = '')
    {
        return json(['code' => 1, 'msg' => $msg, 'data' => $data]);
    }
}

if (!function_exists('__error')) {

    /**
     * 错误时返回的信息
     * @param $msg 消息
     * @return \think\response\Json
     */
    function __error($msg, $data = '')
    {
        return json(['code' => 0, 'msg' => $msg, 'data' => $data]);
    }
}

if (!function_exists('__suc')) {

    /**
     * 成功时返回的信息
     * @param $msg 消息
     */
    function __suc($msg = '成功', $data = '')
    {
        return ['code' => 1, 'msg' => $msg, 'data' => $data];
    }
}

if (!function_exists('__err')) {

    /**
     * 错误时返回的信息
     * @param $msg 消息
     */
    function __err($msg = '失败', $data = '')
    {
        return ['code' => 0, 'msg' => $msg, 'data' => $data];
    }
}





if (!function_exists('password')) {

    /**
     * 密码加密算法
     * @param $value 需要加密的值
     * @param $type  加密类型，默认为md5 （md5, hash）
     * @return mixed
     */
    function password($value,$key = '1233')
    {
        $value = sha1($key) . md5($value) . md5('_encrypt') . sha1($value);
        return sha1($value);
    }

}



if (!function_exists('parseNodeStr')) {

    /**
     * 驼峰转下划线规则
     * @param string $node
     * @return string
     */
    function parseNodeStr($node)
    {
        $tmp = [];
        foreach (explode('/', $node) as $name) {
            $tmp[] = strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
        }
        return str_replace('._', '.', trim(join('/', $tmp), '/'));
    }
}


if (!function_exists('clear_menu')) {

    /**
     * 清空菜单缓存
     */
    function clear_menu()
    {
        Cache::clear('menu');
    }
}



if (!function_exists('clear_cache')){


    /**清空缓存
     * @param null $table 表名或者模型名
     * @return bool
     */
    function clear_cache($table = null)
    {
        if($table == null){
            return  \think\facade\Cache::clear();
        }else{
            $data = \think\facade\Config::get('tableTocache.');

            if(empty($data[$table])) return false;
            foreach ($data[$table] as $tag){
                \think\facade\Cache::clear($tag);
            }
            return true;
        }
    }
}



/**
 * 获取随机字符
 * @param string $length
 * @param $format
 * @return null|string
 */
function getRandChar($length = '4',$format = 'ALL')
{
    switch($format){
        case 'ALL':
            $strPol='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            break;
        case 'CHAR':
            $strPol='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            break;
        case 'NUM':
            $strPol='0123456789';
            break;
        default :
            $strPol='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            break;
    }
    $str = null;
    //$strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;
    for ($i = 0;
         $i < $length;
         $i++) {
        $str .= $strPol[rand(0, $max)];
    }
    return $str;
}



/**
 * 数组 转 对象
 *
 * @param array $arr 数组
 * @return object
 */
function arr2obj($arr) {
    if (gettype($arr) != 'array') {
        return;
    }
    foreach ($arr as $k => $v) {
        if (gettype($v) == 'array' || getType($v) == 'object') {
            $arr[$k] = (object)arr2obj($v);
        }
    }

    return (object)$arr;
}

/**
 * 对象 转 数组
 *
 * @param object $obj 对象
 * @return array
 */
function obj2arr($obj) {
    $obj = (array)$obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array)obj2arr($v);
        }
    }

    return $obj;
}
if (!function_exists('isMobile')){
    //判断是否是手机端还是电脑端
    function isMobile(){
        $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
        $mobile_browser = '0';
        if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
            $mobile_browser++;
        if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))
            $mobile_browser++;
        if(isset($_SERVER['HTTP_X_WAP_PROFILE']))
            $mobile_browser++;
        if(isset($_SERVER['HTTP_PROFILE']))
            $mobile_browser++;
        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));
        $mobile_agents = array(
            'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
            'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
            'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
            'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
            'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
            'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
            'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
            'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
            'wapr','webc','winw','winw','xda','xda-'
        );
        if(in_array($mobile_ua, $mobile_agents))
            $mobile_browser++;
        if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
            $mobile_browser++;
        // Pre-final check to reset everything if the user is on Windows
        if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
            $mobile_browser=0;
        // But WP7 is also Windows, with a slightly different characteristic
        if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)
            $mobile_browser++;
        if($mobile_browser>0){
            return true;
        }else{
            return false;
        }
    }
}



/**
 * @from extend.php
 * 过滤xss攻击
 * @param str $val
 * @return mixed
 */
function remove_xss($val) {
    // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
    // this prevents some character re-spacing such as <java\0script>
    // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
    $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);

    // straight replacements, the user should never need these since they're normal characters
    // this prevents like <IMG SRC=@avascript:alert('XSS')>
    $search = 'abcdefghijklmnopqrstuvwxyz';
    $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $search .= '1234567890!@#$%^&*()';
    $search .= '~`";:?+/={}[]-_|\'\\';
    for ($i = 0; $i < strlen($search); $i++) {
        // ;? matches the ;, which is optional
        // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

        // @ @ search for the hex values
        $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
        // @ @ 0{0,7} matches '0' zero to seven times
        $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
    }

    // now the only remaining whitespace attacks are \t, \n, and \r
    $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script',
        'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound',  'base');
    $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut',
        'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate',
        'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut',
        'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend',
        'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange',
        'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete',
        'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover',
        'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange','onreadystatechange',
        'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted',
        'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
    // 2019/12
    $ra3 = array('conver', 'drop', 'truncate','shell_exec');

    $ra = array_merge($ra1, $ra2,$ra3);

    $found = true; // keep replacing as long as the previous round replaced something
    while ($found == true) {
        $val_before = $val;
        for ($i = 0; $i < sizeof($ra); $i++) {
            $pattern = '/';
            for ($j = 0; $j < strlen($ra[$i]); $j++) {
                if ($j > 0) {
                    $pattern .= '(';
                    $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                    $pattern .= '|';
                    $pattern .= '|(&#0{0,8}([9|10|13]);)';
                    $pattern .= ')*';
                }
                $pattern .= $ra[$i][$j];
            }
            $pattern .= '/i';
            $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
            $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
            if ($val_before == $val) {
                // no replacements were made, so exit the loop
                $found = false;
            }
        }
    }
    return $val;
}


/**
 * 模板输出过滤
 * @param $value
 * @return string
 * ENT_QUOTES - 编码双引号和单引号。
 */
function out_xss($value) {
    return  htmlentities(htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false))  ;
}


/**
 * 预防 xss
 *
 * Content-Security-Policy 内容安全政策
 * report-uri /report  策略指令，并提供至少一个URI地址去递交报告：
 */
function Policy(){
    $url = request()->domain();
    $report = '';
    header('Content-Type: text/javascript; charset=utf-8');
    //设置heard头
    header("Content-Security-Policy:default-src 'self';style-src 'self' $url https://at.alicdn.com http://static.geetest.com http://dn-staticdown.qbox.me 'unsafe-inline'; script-src 'self' $url http://static.geetest.com  http://monitor.geetest.com http://dn-staticdown.qbox.me http://api.geetest.com  http://cdn.bootcss.com  'unsafe-inline' 'unsafe-eval';font-src  'self'  data:  https://at.alicdn.com;worker-src 'self';frame-src 'self';form-action 'self';object-src 'none';img-src 'self' http://static.geetest.com https://chart.googleapis.com  data:;media-src 'self' http://tts.baidu.com ");

    // report-uri $report
}


/**
 * 预防 xss  下单请求
 * Content-Security-Policy 内容安全政策
 * report-uri /report  策略指令，并提供至少一个URI地址去递交报告：
 */
function PolicyApi(){
    $url = request()->domain();
    $report = '';
    header('Content-Type: text/javascript; charset=utf-8');
    //设置heard头
    header("Content-Security-Policy:default-src 'self';style-src 'self' $url https://a.alipayobjects.com https://at.alicdn.com http://static.geetest.com http://dn-staticdown.qbox.me 'unsafe-inline'; script-src 'self' $url  http://static.geetest.com https://a.alipayobjects.com http://monitor.geetest.com http://dn-staticdown.qbox.me http://api.geetest.com http://cdn.bootcss.com 'unsafe-inline' 'unsafe-eval';font-src  'self'  data:  https://at.alicdn.com;worker-src 'self';frame-src 'self';form-action *;object-src 'none';img-src 'self' https://i.alipayobjects.com http://hm.baidu.com;  ");
}
























