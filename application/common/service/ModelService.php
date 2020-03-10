<?php

namespace app\common\service;

use Lock\Lock;
use think\Model;

use redis\StringModel;

use think\Exception;

use think\Db;


/**
 * 模型基础数据服务
 * Class ModelService
 * @package service
 */
class ModelService extends Model {


    protected $createTime = 'create_at';
    protected $updateTime = 'update_at';
    protected $autoWriteTimestamp = 'datetime';

    protected $auto = [];
    protected $insert = [ 'create_by'];
    protected $update = ['update_by','update_at'];


    protected function setCreateByAttr()
    {
        if(app('request')->module() === 'user'){
            $userId = session('user_info.id');
        }elseif(app('request')->module() === 'agent'){
            $userId = session('agent_info.id');
        }elseif(app('request')->module() === 'admin'){
            $userId = session('admin_info.id');
        }else{
            $userId = 0;
        }
        return $userId;
    }


    protected function setUpdateByAttr()
    {
        if(app('request')->module() === 'user'){
            $userId = session('user_info.id');
        }elseif(app('request')->module() === 'agent'){
            $userId = session('agent_info.id');
        }elseif(app('request')->module() === 'admin'){
            $userId = session('admin_info.id');
        }else{
            $userId = 0;
        }
        return $userId;

    }

    protected function setUpdateAtAttr()
    {
        return date('Y-m-d H:i:s');
    }

    protected function setLocationAttr()
    {
        $location = get_location();
        return $location['country'].$location['area'];
    }

    protected function setIpAttr($v)
    {
        if(empty($v))  return get_client_ip();
        return $v;
    }


    /**
     * 主键定义
     * @var string
     */
    protected $pk = 'id';

    static public $redisModel;


    public function __construct($data = [])
    {
        parent::__construct($data);
        self::$redisModel = new StringModel();
    }



    //模型事件
    static protected function init()
    {
        parent::init();

        //一定要用模型方法
        self::event('after_insert', function (ModelService $model) {
            $model->clearCachebyModel($model);
        });

        self::event('before_update', function (ModelService $model) {
            $model->checkVer($model);//数据库反应慢的时候防止多次操作
        });


        // 一定要用save 和 saveAll 模型方法  必须要加 更新数据主键id参数  否则不会及时更新Redis
        self::event('after_update', function (ModelService $model) {

            $model->clearCachebyModel($model);
            $model->deleteRedis($model);

        });

        //User::destroy(1); User::destroy('1,2,3'); User::destroy([1,2,3]); 否则不会及时更新Redis
        self::event('after_delete', function (ModelService $model) {

            $model->clearCachebyModel($model);
            $model->deleteRedis($model);
        });


     }

    /**
     * 检测版本号
     * 乐观锁
     * @return bool
     */
    static protected function checkVer(ModelService $model){
        $getData =  $model->getData();
        if(!isset($getData['verson']) || !isset($getData['id'])) return true;
        $verson = $model::where(['id' => $getData['id']])->value('verson');
        if ($verson !== $getData['verson']-1) exceptions(['msg'=>'多人同时操作，请刷新再试！','url'=>'','wait'=>10000]);
        return true;
    }


    /**
     * 自动根据标签清除缓存
     * @param ModelService $model
     * @return bool
     */
    static protected function clearCachebyModel(ModelService $model){
        $obj = get_object_vars($model);
        clear_cache($obj['name']);
    }




    static protected function deleteRedis(ModelService $model){

        $obj = get_object_vars($model);
        $getData =  $model->getData();
        switch (true){
            case (!empty($model->id)):
                $id = $model->id;
                break;
            case (!empty($getData['id'])):
                $id = $getData['id'];
                break;
            default:
                return false;
                break;
        }

        //判断
        if(!isset($obj['redis']) || empty($obj['redis']['key']) || empty($id)) return false;

        $redisModel = self::$redisModel;
        $redisModel->key = $obj['redis']['key'];//设置key
        $res =  $redisModel->newQuery()->where('id',$id)->delete();
        return $res;
    }


    /**
     * 获取单条redis记录
     * @param $where  单个ID 或者数组
     * @return bool|mixed|null
     */
    static public function quickGet($where){
        $data = new static();
        $obj = get_object_vars($data);

        if(!empty($obj['redis']['key']) && is_array($obj['redis']['keyArr'])){
            $redisModel = self::$redisModel;
            $redisModel->key = $obj['redis']['key'];//设置key
            $quickGet =  $redisModel->newQuery();

            if(is_array($where)){

                foreach ($obj['redis']['keyArr'] as $k =>$v){
                    if(isset($where[$v]))  $quickGet =  $quickGet->where($v,$where[$v]);
                }
                $search = $where;
                $lock_val = 'model:'.$obj['table'];
            }else{
                //传入为ID的时候
                $quickGet =  $quickGet->where('id',$where);
                $search['id'] = $where;
                $lock_val = 'model:'.$obj['table'].$search['id'];
            }
            $res =  $quickGet->first();

            //调试模式 先关闭
            if(!empty($res))  return json_decode($res,true);

            //查询数据库 防止缓存穿透
/*            try{
                $res = Lock::queueLock(function ($redis)  use ($data,$search){
                    $res = $data::where($search)->order(['id'=>'desc'])->find();
                    if(empty($res))  return false;
                    return $res->toArray();
                },$lock_val, 200, 20);
            }catch (\Exception $e){
                return false;
            }
            if(!!$res) self::saveRedis($obj,$res);*/
            $res = $data::where($search)->order(['id'=>'desc'])->find();
            if(empty($res))  return false;
            $res = $res->toArray();
            self::saveRedis($obj,$res);
        }else{
            $res = Db::table($obj['table'])->where($where)->order(['id'=>'desc'])->find();
        }
        return $res;
    }

    /**
     * 添加或者更新redis
     * @param $obj  当前模型的属性
     * @param $data  要更新的数据
     * @return bool
     */
    static public function saveRedis($obj,$data){

        if(!isset($obj['redis']['keyArr'])) return false;
        if(empty($obj['redis']['key'])) return false;
        if(!isset($data['id'])) return false;

        $redisModel = self::$redisModel;

        $redisModel->key = $obj['redis']['key'];//设置key
        if(!empty($obj['redis']['ttl'])) $redisModel->ttl = $obj['redis']['ttl'];//设置过期时间

        $keyArr = [];
        foreach ($obj['redis']['keyArr'] as $k => $v){
            $keyArr[$v] = '';
            if(isset($data[$v])) $keyArr[$v] = empty($data[$v])?0:$data[$v];
        }
        //先删除
        $redisModel->newQuery()->where('id',$data['id'])->delete();
        //再插入
        $redisModel->insert($keyArr,json_encode($data));

        return true;
    }




    //删除 redis
    static public function delRedis($id){
        $data = new static();
        $obj = get_object_vars($data);
        //判断
        if(!isset($obj['redis']) || empty($obj['redis']['key']) || empty($id)) return false;
        $redisModel = self::$redisModel;
        $redisModel->key = $obj['redis']['key'];//设置key

        $quick = $redisModel->newQuery();
        if(is_array($id)){
            foreach ($id as $k => $v){
                $quick = $quick->where($k,$v);
            }
        }elseif(is_string($id)){
            $quick = $quick->where('id', $id);
        }else{
            return false;
        }
        $res = $quick->delete();
        return $res;
    }


    /**
     * 修改字段值
     * @param $update
     * @return \think\response\Json
     */
    public static function editField($update) {

        $model = new static();

        //使用事物保存数据
        $model->startTrans();
        $save = $model->save([$update['field'] => $update['value'],'id'=>$update['id']],['id'=>$update['id']]);
        if (!$save) {
            $model->rollback();
            return __error('数据有误，请稍后再试！');
        }
        $model->commit();
        return __success('信息修改成功！');
    }


    /**
     * 添加
     * @param $insert 需要插入的数据
     */
    public function __add($insert,$msg = '') {

        $model = new static();

        //使用事物保存数据
        $model->startTrans();
        $save = $model->save($insert);
        if (!$save) {
            $model->rollback();
            $msg = '数据有误，请稍后再试！!';
            return __error($msg);
        }
        $model->commit();
        empty($msg) && $msg = '添加成功!';
        return __success($msg);
    }


    /**
     * 修改信息
     * @param $update 需要修改的数据
     * @return \think\response\Json
     */
    public function __edit($update,$msg = '') {

        $model = new static();

        //使用事物保存数据
        $model->startTrans();
        $save = $model->save($update,['id'=>$update['id']]);
        if (!$save) {
            $model->rollback();
            $msg = '数据有误，请稍后再试！';
            return __error($msg);
        }
        $model->commit();

        empty($msg) && $msg = '修改成功';
        return __success($msg);
    }


    /**
     * 删除信息
     * @param $$get 需要删除的数据
     * @return \think\response\Json
     */
    public function __del($get,$msg = '') {

        $model = new static();

        //使用事物保存数据
        $model->startTrans();
        $del = $model::destroy($get['id']);
        if (!($del >= 1) ) {
            $model->rollback();
            $msg = '数据有误，请稍后再试！';
            return __error($msg);
        }
        $model->commit();

        empty($msg) && $msg = '删除成功';
        return __success($msg);

    }




}