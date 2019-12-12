<?php
namespace app\admin\controller;

use app\common\controller\AdminController;

//全局搜索
class Search  extends AdminController
{
    public function index()
    {
        $keywords = $this->request->get('keywords','');
        $data = ['type' => 'error', 'code' => 1, 'msg' => '请输入关键字'];
        empty($keywords) && exceptions($data);

        //左侧菜单
        $apimenu = new \app\admin\controller\api\Menu();

        $list = $apimenu->getNav();

        $res = $this->earch($list,$keywords);

        $basic_data = [
             'title'=> '全局搜索',
            'data' => $res,
        ];
        return $this->fetch('', $basic_data);
    }

    //递归
    public function earch($arr,&$str,&$result=array('num'=> 0,'list'=>'','data'=>array()),$a=0)
    {
            $a++;
            foreach ($arr as $k => $v ){

                if($a == 1) $result['list'] = '';

                if(isset($v['children'])){
                    $result['list'] =  empty($result['list']) ?$v['title']:  $result['list'].' => '.$v['title'];
                    $result = $this->earch($v['children'],$str,$result,$a);
                }else{
                    if(!isset($v['title'])) continue;

                    //模糊匹配
                    if(strpos($v['title'],$str) !== false ) {
                        $result['data'][$k]['str'] = $result['list'].' => '.$v['title'];
                        $result['data'][$k]['title'] = $v['title'];
                        $result['data'][$k]['href'] = $v['href'];
                        $result['num'] = $result['num'] + 1;

                    }
                }


            }

        return $result;

    }

    }
