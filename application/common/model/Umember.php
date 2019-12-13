<?php

namespace app\common\model;

use app\common\service\UserService;

class Umember extends UserService {


    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_member';


    /**
     * redis (复制的时候不要少数组参数)
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [
        'is_open'=> true,
        'ttl'=> 3360 ,
        'key'=> "String:table:Umember:id:{id}:username:{username}",
        'keyArr'=> ['id','username'],
    ];




    protected $append = ['auth'];//追加输出字段


    protected $insert = [ 'create_by','uid'];

    /**
     * 商户号
     * @return int|mixed
     */
    protected function setUidAttr()
    {
      $uid = $this->limit(1)->order(['uid'=>'desc'])->value('uid');

      if(empty($uid)) $uid = 20100000;
      return $uid+1;
    }


    //所属权限名称
    protected function getAuthAttr($val,$data)
    {
        if(isset($data['auth_id'])){
            $id = json_decode($data['auth_id']);
            if(empty($id)) return '无权限';

            $title = \app\common\model\SysAuth::where('id','in',$id)->value('title');
            if(is_array($title)) return  implode('|',$title);

            return $title;
        }

    }



    /**
     * 登录验证
     * @param $username 会员账户
     * @param $password 会员密码
     * @return array
     */
    public function login($username, $password) {
        $login =  self::quickGet(['username'=>$username]);

        if (empty($login)) return ['code' => 0, 'msg' => '账户不存在，请重新输入！', 'user' => $login];
        if ($login['password'] !== password($password)) return ['code' => 0, 'msg' => '密码不正确，请重新输入！', 'user' => $login];
        if ($login['status'] == 0) return ['code' => 0, 'msg' => '该账户已被停用，请联系客服！', 'user' => $login];
        unset($login['password']);
        return ['code' => 1, 'msg' => '登录成功，正在进入后台系统！', 'user' => $login];
    }


    /**
     * 修改会员自己的信息
     * @param $update
     * @return \think\response\Json
     */
    public function editSelf($data) {

        $data['id'] = $data['id'];
        $this->save($data,['id', $data['id']]);

        //重新刷新session
        $user = self::quickGet(['id'=>$data['id']]);
        unset($user['password']);
        $user['login_at'] = time();
        session('admin_auth', $user);

        return __success('信息修改成功');
    }

    /**
     * 修改会员密码
     * @param $update 需要修改的数据
     * @return \think\response\Json
     */
    public function editPassword($update) {

        $this->startTrans();
        try {
            $this->save(['password' => password($update['password']),'id'=>$update['id']],['id', $update['id']]);
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
    public function aList($page = 1, $limit = 10, $search = []) {
        $where = [
            ['who', 'in', [0,1,2,3]]
        ];
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

        $field = 'id, auth_id,uid, username,nickname, qq, phone, remark, status, create_at,create_by,google_token,pid,who,is_single,u_rate';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->select()
            ->each(function ($item, $key) {
                list($auth_id_list, $auth_title) = [json_decode($item['auth_id'], true), ''];

                foreach ($auth_id_list as $auth_id) {
                    $title = model('SysAuth')->where(['id' => $auth_id, 'status' => 1])->value('title');
                    $auth_title = empty($auth_title) ? $title : "{$auth_title}，{$title}";
                }
                $create_by_username =   getNamebyId($item['create_by']);  //获取后台用户名
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
     * 获取代理列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bList($page = 1, $limit = 10, $search = []) {
        $where = [
            ['who', 'in', [2,3]]
        ];
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

        $field = 'id, auth_id,uid, username,nickname, qq, phone, remark, status, create_at,create_by,google_token,pid,who,is_single,agent_ level';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->select()
            ->each(function ($item, $key) {
                list($auth_id_list, $auth_title) = [json_decode($item['auth_id'], true), ''];

                foreach ($auth_id_list as $auth_id) {
                    $title = model('SysAuth')->where(['id' => $auth_id, 'status' => 1])->value('title');
                    $auth_title = empty($auth_title) ? $title : "{$auth_title}，{$title}";
                }
                $create_by_username =   getNamebyId($item['create_by']);  //获取后台用户名
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
     * 账号与Id数组
     * @param array $modules
     */
    public static function username2id() {

        \think\facade\Cache::remember('username2id', function () {
            $data = self::column('username,id');
            \think\facade\Cache::tag('Umember')->set('username2id',$data,3600);
            return \think\facade\Cache::get('username2id');
        });
        return \think\facade\Cache::get('username2id');
    }

    /**
     * ID与账号 数组
     * @param array $modules
     */
    public static function id2username() {
        \think\facade\Cache::remember('id2username', function () {
            $data = self::column('id,username');
            \think\facade\Cache::tag('Umember')->set('id2username',$data,3600);
            return \think\facade\Cache::get('id2username');
        });
        return \think\facade\Cache::get('id2username',[]);
    }


    /**
     * Id与商户号数组
     * @param array $modules
     */
    public static function id2uid() {

        \think\facade\Cache::remember('id2uid', function () {
            $data = self::column('id,uid');
            \think\facade\Cache::tag('Umember')->set('id2uid',$data,3600);
            return \think\facade\Cache::get('id2uid');
        });
        return \think\facade\Cache::get('id2uid');
    }



    /**
     * 商户号与id 数组
     * @param array $modules
     */
    public static function uid2id() {

        \think\facade\Cache::remember('uid2id', function () {
            $arr = self::column('uid,id','id');
            $data = [];
            foreach ($arr as $k => $v){
                $data[$v][] = $k;
            }
            \think\facade\Cache::tag('Umember')->set('uid2id',$data,3600);
            return \think\facade\Cache::get('uid2id');
        });
        return \think\facade\Cache::get('uid2id',[]);
    }



    /**
     * ID与权限名称 数组
     * @param array $modules
     */
    public static function titleArr() {

        \think\facade\Cache::remember('titleArr1', function () {
            $users = self::field('id,auth_id')->all();
            $data = array();
            foreach ($users as $k => $val){
                $data[$val['id']] = $val['auth'];
            }
            \think\facade\Cache::tag('Umember')->set('titleArr1',$data,3600);
            return \think\facade\Cache::get('titleArr1');
        });
        return \think\facade\Cache::get('titleArr1',[]);
    }


    /**
     * 权限名称与ID 数组
     * @param array $modules
     */
    public static function title2id() {

        \think\facade\Cache::remember('title2id', function () {
            $users = self::field('id,auth_id')->all();
            $data = array();
            foreach ($users as $k => $val){
                $auth = explode('|',$val['auth']);
                foreach ($auth as $v1){
                    if(empty($v1)) continue;
                    $data[$v1][] = $val['id'];
                }
            }
            \think\facade\Cache::tag('Umember')->set('title2id',$data,3600);
            return \think\facade\Cache::get('title2id');
        });
        return \think\facade\Cache::get('title2id',[]);
    }

}