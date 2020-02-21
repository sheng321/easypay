<?php
namespace app\user\controller;

use app\common\controller\UserController;

/**
 * Api 管理
 * Class Api
 * @package app\user\controller
 */
class Api extends UserController
{

    /**通道费率
     * @return mixed
     */
    public function index()
    {
        if (!$this->request->isPost()) {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 100);
            $search = (array)$this->request->get('search', []);
            $search['status'] = 1;
            $result = model('app\common\model\PayProduct')->aList($page, $limit, $search);

            $data = [];
            foreach ($result['data'] as $k => $v){
                $result['data'][$k]['status1'] = 1;
                $rateStatus = \app\common\service\RateService::getMemStatus($this->user['uid'],$v['id']); //当前用户的费率状态

                if(!empty($rateStatus)){
                    $result['data'][$k]['p_rate'] = $rateStatus['rate'];
                    $result['data'][$k]['status'] =  $rateStatus['status'];
                }

                $data[$k]['status'] = $result['data'][$k]['status'];
                $data[$k]['p_rate'] = $result['data'][$k]['p_rate'] * 1000 .'‰';
                $data[$k]['code'] = $result['data'][$k]['code'];
                $data[$k]['title'] = $result['data'][$k]['title'];

                if($result['data'][$k]['status'] == 0) $data[$k]['p_rate'] = '未设置';

            }

            //支付通道分组
            $Ulevel = Ulevel::quickGet($this->user['profile']['group_id']);
            if(empty($Ulevel) || $Ulevel['type1'] != 0 )  __jerror('未分配用户分组或商户分组不正确');

            halt($Ulevel);






            //基础数据
            $basic_data = [
                'title'  => '支付通道费率',
                'data'   => $data,
            ];

            return $this->fetch('', $basic_data);
        }
    }


    /**代付通道费率
     * @return mixed
     */
    public function df()
    {
        if (!$this->request->isPost()) {

            $data = config('custom.df');

            //基础数据
            $basic_data = [
                'title'  => '代付通道费率',
                'data'   => $data,
                'bank'   => config('bank.'),
            ];

            return $this->fetch('', $basic_data);
        }
    }


    /**
     * 开发文档
     * @return mixed
     */
    public function api() {
        //基础数据
        $basic_data = [
            'title'  => 'API设置',

        ];
        return $this->fetch('',$basic_data);
    }

    public function secret(){
        if ($this->request->isPost()) {

            $post = $this->request->only('paypwd', 'post');

            $post['paypwd1'] =  $this->user['profile']['pay_pwd'];

            //验证数据
            $validate = $this->validate($post, 'app\common\validate\Umember.paypwd');
            if (true !== $validate) return __error($validate);

            if($this->request->get('type/s','') === '0975458tyyuuuiiiooopp'){
                $secret = $this->user['profile']['df_secret'];
            }else{
                $secret = $this->user['profile']['secret'];
            }

            //修改密码数据
            return __success('',$secret);
        }
    }


}
