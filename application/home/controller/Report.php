<?php
namespace app\home\controller;
use think\Db;

header('Access-Control-Allow-Origin:*');


class Report extends Base
{
    public function index(){
        $jsonp = $_GET["callback"]; 
        if($_GET['openid'] == 'oWDEn0eGK5oWn5l0dEtF9Kk3UrtY' || $_GET['openid'] == 'oAq3H0l5JBtb3xFLWUS-9s7NiHCk' || $_GET['openid'] == 'oAq3H0rlIivLxnR7TRQEmnpERa5s' || $_GET['openid'] == 'oAq3H0u8Goj1pPJZykuslsAZOb00' || $_GET['openid'] == 'oAq3H0rOeGpMO9iMBqfJexknzze4' || $_GET['openid'] == 'oAq3H0niwqoREx0dP9a1D9tM7W70' || $_GET['openid'] == 'oWDEn0eGK5oWn5l0dEtF9Kk3UrtY' || $_GET['openid'] == 'oWDEn0T2PQkTVnNIR0U0IcdqSGzg' || $_GET['openid'] == 'oWDEn0dp1pmwrNmw4dTS0yQjQ29A' || $_GET['openid'] == 'oWDEn0WylzhMVgBcRbsiL2cYspr8' || $_GET['openid'] == 'oWDEn0T5j2wY1wrgVKzqAltWO6Og'){
        // if($_GET['openid'] == 'oWDEn0eGK5oWn5l0dEtF9Kk3UrtY' || $_GET['openid'] == 'oWDEn0T2PQkTVnNIR0U0IcdqSGzg' || $_GET['openid'] == 'oWDEn0dp1pmwrNmw4dTS0yQjQ29A' || $_GET['openid'] == 'oWDEn0WylzhMVgBcRbsiL2cYspr8' || $_GET['openid'] == 'oWDEn0T5j2wY1wrgVKzqAltWO6Og'){
            $datetime = $_GET["datetime"]; 
            $begin_time = strtotime($datetime) - 43200;
            $end_time = strtotime($datetime) + 43200;
            //联网门店
            $active_stores = DB::name('active_stores')->field('stores_id,stores_name')->select();
            foreach ($active_stores as $k => $v) {
                //充值人数
                $active_stores[$k]['count'] = DB::name('wine_recharge')->where(array('pay_type'=>2,'status'=>1,'stores_id'=>$v['stores_id']))->where("regist_time > ".$begin_time." and regist_time < ".$end_time)->count();
                //充值金额
                $active_stores[$k]['amount'] = DB::name('wine_recharge')->where(array('pay_type'=>2,'status'=>1,'stores_id'=>$v['stores_id']))->where("regist_time > ".$begin_time." and regist_time < ".$end_time)->sum('amount');
                if($active_stores[$k]['amount'] == null){
                    $active_stores[$k]['amount'] = 0;
                }
                //本存本取
                $active_stores[$k]['consumption'] = DB::name('wine_consumption')->where(array('stores_id'=>$v['stores_id'],'recharge_store_id'=>$v['stores_id'],'consu_status'=>1,'status'=>1))->where("submit_time > ".$begin_time." and submit_time < ".$end_time)->count();
                //本存他取
                $active_stores[$k]['recharge_consumption'] = DB::name('wine_consumption')->where("stores_id in (select stores_id from tp_active_stores ) and stores_id!=recharge_store_id and recharge_store_id = ".$v['stores_id']." and consu_status=1 and status=1")->where("submit_time > ".$begin_time." and submit_time < ".$end_time)->count();
                //它存本取
                $active_stores[$k]['consumption_recharge'] = DB::name('wine_consumption')->where("stores_id = ".$v['stores_id']." and recharge_store_id in (select stores_id from tp_active_stores ) and stores_id!=recharge_store_id and consu_status=1 and status=1")->where("submit_time > ".$begin_time." and submit_time < ".$end_time)->count();
            }
            //非联网门店
            $data_stores = DB::name('stores')->field('id,storesname')->where('id not in (select stores_id from tp_active_stores )')->select();
            foreach ($data_stores as $k => $v) {
                //充值人数
                $data_stores[$k]['count'] = DB::name('wine_recharge')->where(array('pay_type'=>2,'status'=>1,'stores_id'=>$v['id']))->where("regist_time > ".$begin_time." and regist_time < ".$end_time)->count();
                //充值金额
                $data_stores[$k]['amount'] = DB::name('wine_recharge')->where(array('pay_type'=>2,'status'=>1,'stores_id'=>$v['id']))->where("regist_time > ".$begin_time." and regist_time < ".$end_time)->sum('amount');
                if($data_stores[$k]['amount'] == null){
                    $data_stores[$k]['amount'] = 0;
                }
                //本存本取
                $data_stores[$k]['consumption'] = DB::name('wine_consumption')->where(array('stores_id'=>$v['id'],'recharge_store_id'=>$v['id'],'consu_status'=>1,'status'=>1))->where("submit_time > ".$begin_time." and submit_time < ".$end_time)->count();
            }

            $res['active_stores'] = $active_stores;
            $res['data_stores'] = $data_stores;

            $json_data = json_encode(['code' => 200, 'data' => $res, 'msg' =>'数据获取成功' ]);
        }else{
            $json_data = json_encode(['code' => 500, 'data' => '', 'msg' =>'数据获取失败' ]);
        }
        echo $jsonp ."(".$json_data.")";

    }
}

