<?php
namespace app\admin\controller;

use app\common\controller\AdminController;

use app\common\model\ActonRecord;

/** 日志
 * Class Log
 * @package app\admin\controller
 */
class Log  extends AdminController
{

    /**
     * User模型对象
     */
    protected $model = null;


    /**
     * 初始化
     * User constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->model = new ActonRecord();
    }

    /**管理员日志
     * @return mixed
     */
    public function index()
    {

        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->get('search', []);
            $search['type'] = 1;
            return json($this->model->logList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '管理员行为日志',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }

    /**商户日志
     * @return mixed
     */
    public function user()
    {
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->get('search', []);
            $search['type'] = 2;
            return json($this->model->userList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '商户行为日志',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }



    /**
     * 删除后半个月的日志
     */
    public function delete(){
        if ($this->request->get('type1') == 'delete') {
            $res = ActonRecord::destroy(function($query){
                $date = timeToDate(0,0,0,-14);
                $type =  $this->request->get('type',1);
                $query->where([
                    ['create_at','<',$date],
                    ['type','=',$type]
                ]);
            });

            if($res){
                __log('删除后台操作日志记录！');

                return __success('删除成功！');
            }else{
                return __error('删除失败!');
            }
        }

    }



}
