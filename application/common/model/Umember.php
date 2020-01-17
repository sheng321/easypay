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
        'key'=> "String:table:Umember:username:{username}:uid:{uid}:id:{id}",
        'keyArr'=> ['id','username','uid'],
    ];

    protected $insert = [ 'create_by','uid'];

    /**
     * 商户号
     * @return int|mixed
     */
    protected function setUidAttr($value,$data)
    {
        if(isset($data['pid']) && isset($data['who']) && ($data['who'] == 1 ||$data['who'] == 3) ){
            //添加员工的情况
            $uid = $this->where(['id'=>$data['pid']])->value('uid');
        }else{
            $uid = $this->limit(1)->order(['uid'=>'desc'])->value('uid');
            if(empty($uid)) $uid = 20100000;
            $uid =  $uid+1;
        }

      return $uid;
    }


    /**
     * 追加输出属性和金额
     * @param $val
     * @param $data
     * @return mixed
     */
    protected function getUidAttr($val,$data)
    {
       $append =  $this->append;
       if(empty($append)) $append = [];
        $append[] = 'profile';
        $append[] = 'money';
       $this->append = $append; //追加输出字段
       return $val;
    }


    /**
     * 追加输出权限
     * @param $val
     * @param $data
     * @return mixed
     */
    protected function getAuth_idAttr($val,$data)
    {
        $append =  $this->append;
        if(empty($append)) $append = [];
        $append[] = 'auth';
        $this->append = $append; //追加输出字段
        return $val;
    }



    /**
     * 属性
     * @param $val
     * @param $data
     * @return mixed|string
     */
    protected function getProfileAttr($val,$data)
    {
        if(isset($data['uid'])&&isset($data['who'])){
           $Uprofile =  model('app\common\model\Uprofile');
            $find =  $Uprofile->where([['uid','=',$data['uid']]])->field('id,uid,level,pid,pay_pwd,secret,group_id,who')->find();
            if(empty($find)){
                $Uprofile->save(['uid'=>$data['uid'],'who'=>$data['who']]);
                $find =  $Uprofile->where([['uid','=',$data['uid']]])->field('id,uid,level,pid,pay_pwd,secret,group_id,who')->find();
            }
            $data = $find->toArray();
            return $data;
        }
    }

    /**
     * 金额
     * @param $val
     * @param $data
     * @return mixed|string
     */
    protected function getMoneyAttr($val,$data)
    {
        if(isset($data['uid'])){
            $Umoney =  model('app\common\model\Umoney');
            $find =  $Umoney->where([['uid','=',$data['uid']]])->field('id,uid,balance,artificial,frozen_amount_t1,frozen_amount,total_money')->find();
            if(empty($find)){
                $Umoney->save(['uid'=>$data['uid']]);
                $find =  $Umoney->where([['uid','=',$data['uid']]])->field('id,uid,balance,artificial,frozen_amount_t1,frozen_amount,total_money')->find();
            }
            $data =   $find->toArray();
            return $data;
        }
    }


    //所属权限名称
    protected function getAuthAttr($val,$data)
    {
        if(isset($data['auth_id'])){
            $id = json_decode($data['auth_id'],true);
            if(empty($id)) return '暂无权限信息';

            $title = \app\common\model\SysAuth::where([
                ['id','in',$id],
                ['status','=',1],
            ])->column('title','id');
            if(is_array($title)) return  implode(',',$title);

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

        $module =  app('request')->module();
        if($module == 'agent'  && $login['profile']['who'] == 0) return ['code' => 0, 'msg' => '账户不存在，请重新输入！', 'user' => $login]; //是否代理
        if($module == 'user'  && $login['profile']['who'] == 2) return ['code' => 0, 'msg' => '账户不存在，请重新输入！', 'user' => $login]; //是否用户

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
        $this->save($data,['id'=> $data['id']]);

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
            $this->save(['password' => password($update['password']),'id'=>$update['id']],['id'=>$update['id']]);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            return __error($e->getMessage());
        }
        return __success('密码修改成功');
    }


    /**
     * 获取普通列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function aList($page = 1, $limit = 10, $search = []) {
        $where = [
            ['who', 'in', [0,1]]
        ];
        //搜索条件
        $searchField['like'] = ['username'];
        $searchField['eq'] = ['phone','qq','status'];
        $searchField['time'] = ['create_at'];
        $where = search($search,$searchField,$where);

        //用户分组数组
        $group =  \app\common\model\Ulevel::idArr();

        $field = 'id, auth_id,uid, username,nickname, qq, phone, remark, status, create_at,create_by,google_token,pid,who,is_single';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->select()
            ->each(function ($item, $key) use ($group) {
                $item['auth_title'] =  $item['auth'];

                $item['group_title'] = isset($group[$item['profile']['group_id']])?$group[$item['profile']['group_id']]:'未分组' ;
                $create_by_username =   getNamebyId($item['create_by']);  //获取后台用户名
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
        $searchField['like'] = ['username'];
        $searchField['eq'] = ['phone','qq','status'];
        $searchField['time'] = ['create_at'];
        $where = search($search,$searchField,$where);

        //用户分组数组
        $group =  \app\common\model\Ulevel::idArr();

        $field = 'id, auth_id,uid, username,nickname, qq, phone, remark, status, create_at,create_by,google_token,pid,who,is_single';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->select()
            ->each(function ($item, $key) use ($group)  {
                $item['auth_title'] =  $item['auth'];
                $item['group_title'] = isset($group[$item['profile']['group_id']])?$group[$item['profile']['group_id']]:'未分组' ;
                $create_by_username =   getNamebyId($item['create_by']);  //获取后台用户名
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
                $auth = explode('|',$val['auth_id']);
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