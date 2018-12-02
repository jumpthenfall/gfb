<?php
namespace app\admin\controller;
use think\Config;
use think\Loader;
use think\Db;
use app\admin\model\ConsumptionModel;
use app\admin\model\UserModel;

class Index extends Base
{
    public function index()
    {
        return $this->fetch('/index');
    }


    public function indexPage()
    {
        $info = array(
            'web_server' => $_SERVER['SERVER_SOFTWARE'],
            'onload'     => ini_get('upload_max_filesize'),
            'think_v'    => THINK_VERSION,
            'phpversion' => phpversion(),
        );

        $param = [];
        $storesid = session('storesid');

        //昨日起始时间
        $one = strtotime('-1 day');
        $stime =date('Y-m-d',$one) . " 00:00:00";
        $etime =date('Y-m-d',$one) . " 23:59:59";

        //平台昨日新增用户
        $u_where = " register_time > '{$stime}' and register_time < '{$etime}' and status = 1 ";
        $new_user = Db::name('user')->where($u_where)->count();
        $temp = [];
        $temp['status'] = 1;
        $user = new UserModel();

        $usernum = $user->getUserCount($temp);//平台用户总量

        $this->assign('usernum', $usernum);//用户总量
        $this->assign('new_user', $new_user);//新增用户
        $this->assign('stores_id', session('storesid'));//登录门店




        $this->assign('info',$info);
        return $this->fetch('index');
    }


    /**
     * 清除缓存
     */
    public function clear() {
        if (delete_dir_file(CACHE_PATH) || delete_dir_file(TEMP_PATH)) {
            return json(['code' => 1, 'msg' => '清除缓存成功']);
        } else {
            return json(['code' => 0, 'msg' => '清除缓存失败']);
        }
    }

}
