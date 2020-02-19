<?php

namespace app\common\exception;
use think\exception\Handle;
use think\facade\Log;
use Exception;
use think\facade\Request;

class ExceptionHandle extends Handle
{
    public $code = 500;
    public $msg = 'sorry，we make a mistake. (^o^)Y';

    public function render(Exception $e)
    {
            // 如果是服务器未处理的异常，将http状态码设置为500，并记录日志
            // 调试状态下需要显示TP默认的异常页面，因为TP的默认页面
            // 关闭调试状态下，开启单个页面调试

        if(config('app_debugs')  || config('debug_token') == md5(Request::get('debug_token',''))){
            return parent::render($e);
        }

       $this->recordErrorLog($e);

       return json($this->msg,$this->code);

    }

    /**
     * 记录异常日志
     * @param Exception $e
     */
    private function recordErrorLog(Exception $e)
    {
        Log::init([
            // 日志保存目录
            'path'        => \think\facade\Env::get('root_path') .'runtime/data/errLog/',
            // 日志记录级别
            'level'       => ['error'],
            // 最大日志文件数量
            'max_files'   => 20,
            'close'   => false,
        ]);

        $trace = (array) $e->getTrace()[7]["args"][0];
        $newStr = json_decode(str_ireplace("\\u0000*\\u0000","",json_encode($trace,JSON_UNESCAPED_SLASHES)),true);


        $traceArr['module'] = $newStr['module'];
        $traceArr['controller'] = $newStr['controller'];
        $traceArr['action'] = $newStr['action'];
        if(!empty($newStr['param'])) $traceArr['param'] = $newStr['param'];
        if(!empty($newStr['get'])) $traceArr['get'] = $newStr['get'];
        if(!empty($newStr['post'])) $traceArr['post'] = $newStr['post'];
        if(!empty($newStr['request'])) $traceArr['request'] = $newStr['request'];
        if(!empty($newStr['route'])) $traceArr['route'] = $newStr['route'];
        if(!empty($newStr['put'])) $traceArr['put'] = $newStr['put'];
        if(!empty($newStr['session'])) $traceArr['session'] = $newStr['session'];
        if(!empty($newStr['file'])) $traceArr['file'] = $newStr['file'];
        if(!empty($newStr['cookie'])) $traceArr['cookie'] = $newStr['cookie'];
        $traceArr['server'] = $newStr['server'];

        $data['Message'] =  $e->getMessage();
        $data['Line'] =  $e->getLine();
        $data['Code'] =  $e->getCode();
        $data['Trace'] = $traceArr;

        Log::write($data,'error');

    }
}
