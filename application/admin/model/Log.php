<?php

namespace app\admin\controller;
use app\admin\model\LogModel;
use app\admin\model\CheckLogModel;
use think\Db;
use com\IpLocation;
 
class Log extends Base
{

    /**
     * [operate_log 操作日志]
     * @return [type] [description]
     * @author [史晓庆] [974196336@qq.com]
     */
    public function index()
    {

        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['admin_id'] =  $key;          
        }      
        $arr=Db::name("admin")->column("id,username"); //获取用户列表      
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = config('list_rows');// 获取总条数
        $count = Db::name('log')->where($map)->count();//计算总页面
        $allpage = intval(ceil($count / $limits));
        $lists = Db::name('log')->where($map)->page($Nowpage, $limits)->order('add_time desc')->select();       
        $Ip = new IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
        foreach($lists as $k=>$v){
            $lists[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            $lists[$k]['ipaddr'] = $Ip->getlocation($lists[$k]['ip']);
        }  
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数 
        $this->assign('count', $count);
        $this->assign("search_user",$arr);
        $this->assign('val', $key);
        if(input('get.page')){
            return json($lists);
        }
        return $this->fetch();
    }


    /**
     * [del_log 删除日志]
     * @return [type] [description]
     * @author [史晓庆] [974196336@qq.com]
     */
    public function del_log()
    {
        $id = input('param.id');
        $log = new LogModel();
        $flag = $log->delLog($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    /**充值审核日志
     * description:
     * @return mixed|\think\response\Json
     * User: zjc
     * email: zengjc08@163.com
     */
    public function recharge_check_log()
    {

        $store_id = input('stores_id');
        $starttime = input('starttime');
        $endtime = input('endtime');

        $where = [];
        $s_where = [];
        if(2!=session('storesid')){
            $store_id = session('storesid');
            $s_where['id'] = $store_id;
        }
        if($store_id){
            $where['stores_id'] = $store_id;
        }
        if($starttime && $endtime){
            $stime = strtotime($starttime);
            $etime = strtotime($endtime);
            $where['create_time'] =['between',"{$stime},{$etime}"];
        }elseif($starttime){
            $stime = strtotime($starttime);
            $where['create_time'] = ['>',$stime];
        }elseif($endtime){
            $etime = strtotime($endtime);
            $where['create_time'] = ['<',$etime];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = 50;// 获取总条数
        $log_model = new CheckLogModel();
        $logs = $log_model->getCheckLogs($where,$Nowpage,$limits);
        $count = $log_model->checkCount($where);//计算总页面
        $stores_list = Db::name('stores')->where($s_where)->select();
        $allpage = ceil($count/$limits);
        $this->assign('Nowpage', $Nowpage); //当前页
        $this->assign('allpage', $allpage); //总页数
        $this->assign('storeslist', $stores_list);
        $this->assign('stores_id', $store_id);
        $this->assign('starttime', $starttime);
        $this->assign('endtime', $endtime);
        if(input('get.page')){
            return json($logs);
        }
        return $this->fetch();
    }
 
}