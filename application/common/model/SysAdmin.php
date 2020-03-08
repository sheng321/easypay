<?php

namespace app\common\model;

use app\common\service\AdminService;

class SysAdmin extends AdminService {

    protected $append = ['auth'];//追加输出字段


    //所属权限名称
    protected function getAuthAttr($val,$data)
    {
        if($data['id'] == 1) return '超级管理员';
        $id = json_decode($data['auth_id'],true);
        if(empty($id)) return '无权限';

        $title = \app\common\model\SysAuth::where('id','in',$id)->value('title');
        if(is_array($title)) return  "【".implode('|',$title)."】";

        return  "【".$title."】";
    }


    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_admin';


    /**
     * redis (复制的时候不要少数组参数)
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [

        'ttl'=> 3360 ,
        'key'=> "String:table:SysAdmin:id:{id}:username:{username}",
        'keyArr'=> ['id','username'],
    ];


    /**
     * 登录验证
     * @param $username 管理员账户
     * @param $password 管理员密码
     * @return array
     */
    public function login($username, $password) {
        $login =  self::quickGet(['username'=>$username]);
        if (empty($login)) return ['code' => 0, 'msg' => '账户不存在，请重新输入！', 'user' => $login];
        if ($login['password'] != password($password)) return ['code' => 0, 'msg' => '密码不正确，请重新输入！', 'user' => $login];
        if ($login['status'] == 0) return ['code' => 0, 'msg' => '该账户已被停用，请联系超级管理员！', 'user' => $login];
        unset($login['password']);
        return ['code' => 1, 'msg' => '登录成功，正在进入后台系统！', 'user' => $login];
    }


    /**
     * 修改管理员自己的信息
     * @param $update
     * @return \think\response\Json
     */
    public function editSelf($data) {

        $data['id'] = $data['id'];
        $this->save($data,['id'=> $data['id']]);

        //重新刷新session
        $user = self::quickGet(['id'=>$data['id']]);
        unset($user['password']);
        $user['login_at'] = time();
        session('admin_auth', $user);

        return __success('信息修改成功');
    }

    /**
     * 修改管理员密码
     * @param $update 需要修改的数据
     * @return \think\response\Json
     */
    public function editPassword($update) {

        $this->startTrans();
        try {
            $this->save(['password' => password($update['password']),'id'=>$update['id']],['id'=> $update['id']]);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            return __error($e->getMessage());
        }
        return __success('密码修改成功');
    }


    /**
     * 获取用户列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userList($page = 1, $limit = 10, $search = []) {
        $where = [];
        //搜索条件
        foreach ($search as $key => $value) {
            if ($key == 'status' && $value != '') {
                $where[] = [$key, '=', $value];
            } elseif ($key == 'create_at' && $value != '') {
                $value_list = explode(" - ", $value);
                $where[] = [$key, 'BETWEEN', ["{$value_list[0]} 00:00:00", "{$value_list[1]} 23:59:59"]];
            } else {
                !empty($value) && $where[] = [$key, 'LIKE', '%' . $value . '%'];
            }
        }

        $field = 'id, auth_id, username,nickname, qq, phone, remark, status, create_at,create_by,google_token';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->select()
            ->each(function ($item, $key) {
                list($auth_id_list, $auth_title) = [json_decode($item['auth_id'], true), ''];

                foreach ($auth_id_list as $auth_id) {
                    $title = model('SysAuth')->where(['id' => $auth_id, 'status' => 1])->value('title');
                    $auth_title = empty($auth_title) ? $title : "{$auth_title}，{$title}";
                }
                $create_by_username =   getNamebyId($item['create_by']);
                empty($auth_title) ? $item['auth_title'] = '暂无权限信息' : $item['auth_title'] = $auth_title;
                $item['id'] == 1 && $item['auth_title'] = '不受权限控制';
                empty($create_by_username) ? $item['create_by_username'] = '无创建者信息' : $item['create_by_username'] = $create_by_username;
                !empty($item['google_token']) ? $item['google_token'] = 1 : $item['google_token'] = 0;
            });

        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';
        $info = [
            'limit'        => $limit,
            'page_current' => $page,
            'page_sum'     => ceil($count / $limit),
        ];
        $list = [
            'code'  => 0,
            'msg'   => $msg,
            'count' => $count,
            'info'  => $info,
            'data'  => $data,
        ];
        return $list;
    }



    /**
     * 昵称与Id数组
     * @param array $modules
     */
    public static function nickArr() {

        \think\facade\Cache::remember('nickArr', function () {
            $value =  self::column('nickname,id');
            \think\facade\Cache::tag('SysAdmin')->set('nickArr',$value,3600);
            return $value;
        },3600);

        return \think\facade\Cache::get('nickArr');
    }

    /**
     * ID与昵称 数组
     * @param array $modules
     */
    public static function idArr() {

        \think\facade\Cache::remember('idArr', function () {
            $value = self::column('id,nickname');
            \think\facade\Cache::tag('SysAdmin')->set('idArr',$value,3600);
            return $value;
        },3600);
        return \think\facade\Cache::get('idArr',[]);
    }

    /**
     * ID与权限名称 数组
     * @param array $modules
     */
    public static function titleArr() {

        \think\facade\Cache::remember('titleArr', function () {
            $users = self::field('id,auth_id')->all();
            $data = array();
            foreach ($users as $k => $val){
                $data[$val['id']] = $val['auth'];
            }
            \think\facade\Cache::tag('SysAdmin')->set('titleArr',$data,3600);
            return $data;
        },3600);
        return \think\facade\Cache::get('titleArr',[]);
    }

    /**
     * 权限名称与ID 数组
     * @param array $modules
     */
    public static function title2id() {

        \think\facade\Cache::remember('title2id1', function () {
            $users = self::field('id,auth_id')->all();
            $data = array();
            foreach ($users as $k => $val){
                $auth = explode('|',$val['auth']);
                foreach ($auth as $v1){
                    if(empty($v1)) continue;
                    $data[$v1][] = $val['id'];
                }
            }
            \think\facade\Cache::tag('SysAdmin')->set('title2id1',$data,3600);
            return $data;
        },3600);
        return \think\facade\Cache::get('title2id1',[]);
    }

}