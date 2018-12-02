<?php
namespace app\home\controller;
use think\Model;
use think\Db;

header('Access-Control-Allow-Origin:*');


class Test extends Base
{
    public function index(){
        $jsonp = input('callback');

        $openid = "oWDEn0T2PQkTVnNIR0U0IcdqSGzg,oAq3H0l5JBtb3xFLWUS-9s7NiHCk,oAq3H0rlIivLxnR7TRQEmnpERa5s,oAq3H0u8Goj1pPJZykuslsAZOb00,oAq3H0rOeGpMO9iMBqfJexknzze4,oAq3H0niwqoREx0dP9a1D9tM7W70,oWDEn0YZCysknT7JGsTNQ9EVd8o8,oWDEn0f1UwHT1u4qXjTN-v1CkKnI,oAq3H0j3QpJheBBEFrCEkOlqLXd4,oWDEn0dp1pmwrNmw4dTS0yQjQ29A,oAq3H0v2lrR41EB2B9PmHLp58abE";

        if(strpos($openid,input('openid')) || input('openid') == 'oWDEn0T2PQkTVnNIR0U0IcdqSGzg' || input('openid') == 'oAq3H0l5JBtb3xFLWUS-9s7NiHCk' || input('openid') == 'oWDEn0f1UwHT1u4qXjTN-v1CkKnI'){
            $datetime = input('datetime'); 
            $type = input('type'); 
            if($type ==1){
                $begin_time = strtotime($datetime) - 43200;
                $end_time = strtotime($datetime) + 43200;
            }elseif($type ==2){
                $begin_time = mktime('12', 0, 0, date('m',strtotime($datetime))-1, 26, date('Y',strtotime($datetime)));
                $end_time = mktime('12', 0, 0, date('m',strtotime($datetime)), 26, date('Y',strtotime($datetime)));
            }else{
                $begin_time = 1420045200;
                $end_time = time();
            }
            // $stores = DB::name('wine_recharge')->query('SELECT ts.storesname as storesname,count(*) as number,sum(wr.amount) as money,wr.stores_id as stores_id FROM tp_wine_recharge wr LEFT JOIN tp_stores ts ON ts.id = wr.stores_id where wr.regist_time >  '.$begin_time.' and wr.regist_time <  '.$end_time.' and wr.stores_id in (select stores_id from tp_active_stores) and wr.pay_type = 2 GROUP BY wr.stores_id ORDER BY money desc');
            
            $stores = DB::name('wine_recharge')->query('SELECT A.storesname as stores_name,A.number as count,A.money as amount,A.stores_id,B.consumption,C.consumption_recharge from (SELECT ts.storesname as storesname,count(*) as number,sum(wr.amount) as money,wr.stores_id as stores_id FROM tp_wine_recharge wr LEFT JOIN tp_stores ts ON ts.id = wr.stores_id where wr.regist_time > '.$begin_time.' and wr.regist_time < '.$end_time.' and wr.stores_id in (select stores_id from tp_active_stores) and wr.pay_type = 2 and wr.status = 1 GROUP BY wr.stores_id ) as A LEFT JOIN (SELECT stores_id,count(*) as consumption FROM tp_wine_consumption where consu_status =1 and status = 1 and submit_time >'.$begin_time.' and submit_time <'.$end_time.' and stores_id = recharge_store_id GROUP BY stores_id) as B ON B.stores_id = A.stores_id LEFT JOIN (SELECT stores_id,count(*) as consumption_recharge FROM tp_wine_consumption where consu_status =1 and status = 1 and submit_time >'.$begin_time.' and submit_time <'.$end_time.' and stores_id <> recharge_store_id GROUP BY stores_id) as C ON C.stores_id = A.stores_id GROUP BY A.stores_id ORDER BY A.money desc,A.number desc,C.consumption_recharge desc,B.consumption desc,A.storesname asc');
            $sum_stores = DB::name('wine_recharge')->query('SELECT sum(A.number) as sum_count,sum(A.money) as sum_amount,sum(B.consumption) as sum_consumption,sum(C.consumption_recharge) as consumption_recharge from (SELECT ts.storesname as storesname,count(*) as number,sum(wr.amount) as money,wr.stores_id as stores_id FROM tp_wine_recharge wr LEFT JOIN tp_stores ts ON ts.id = wr.stores_id where wr.regist_time > '.$begin_time.' and wr.regist_time < '.$end_time.' and wr.stores_id in (select stores_id from tp_active_stores) and wr.pay_type = 2 and wr.status = 1 GROUP BY wr.stores_id ) as A LEFT JOIN (SELECT stores_id,count(*) as consumption FROM tp_wine_consumption where consu_status =1 and status = 1 and submit_time >'.$begin_time.' and submit_time <'.$end_time.' and stores_id = recharge_store_id GROUP BY stores_id) as B ON B.stores_id = A.stores_id LEFT JOIN (SELECT stores_id,count(*) as consumption_recharge FROM tp_wine_consumption where consu_status =1 and status = 1 and submit_time >'.$begin_time.' and submit_time <'.$end_time.' and stores_id <> recharge_store_id GROUP BY stores_id) as C ON C.stores_id = A.stores_id ORDER BY A.money desc,A.number desc,C.consumption_recharge desc,B.consumption desc,A.storesname asc');
            $res['active_stores'] = $stores;
            $res['sum_stores'] = $sum_stores;
            //联网门店
            // $active_stores = DB::name('active_stores')->field('stores_id,stores_name')->select();
            // foreach ($active_stores as $k => $v) {
            //     //充值人数
                // $active_stores[$k]['count'] = DB::name('wine_recharge')->where(array('pay_type'=>2,'status'=>1,'stores_id'=>$v['stores_id']))->where("regist_time > ".$begin_time." and regist_time < ".$end_time)->count();
            //     //充值金额
            //     $active_stores[$k]['amount'] = DB::name('wine_recharge')->where(array('pay_type'=>2,'status'=>1,'stores_id'=>$v['stores_id']))->where("regist_time > ".$begin_time." and regist_time < ".$end_time)->sum('amount');
            //     if($active_stores[$k]['amount'] == null){
            //         $active_stores[$k]['amount'] = 0;
            //     }
            //     //本存本取
            //     $active_stores[$k]['consumption'] = DB::name('wine_consumption')->where(array('stores_id'=>$v['stores_id'],'recharge_store_id'=>$v['stores_id'],'consu_status'=>1,'status'=>1))->where("submit_time > ".$begin_time." and submit_time < ".$end_time)->count();
            //     //本存他取
            //     // $active_stores[$k]['recharge_consumption'] = DB::name('wine_consumption')->where("stores_id in (select stores_id from tp_active_stores ) and stores_id!=recharge_store_id and recharge_store_id = ".$v['stores_id']." and consu_status=1 and status=1")->where("submit_time > ".$begin_time." and submit_time < ".$end_time)->count();
            //     //它存本取
            //     $active_stores[$k]['consumption_recharge'] = DB::name('wine_consumption')->where("stores_id = ".$v['stores_id']." and recharge_store_id in (select stores_id from tp_active_stores ) and stores_id!=recharge_store_id and consu_status=1 and status=1")->where("submit_time > ".$begin_time." and submit_time < ".$end_time)->count();
            // }
            //非联网门店
            // $data_stores = DB::name('stores')->field('id,storesname')->where('id not in (select stores_id from tp_active_stores )')->select();
            // foreach ($data_stores as $k => $v) {
            //     //充值人数
            //     $data_stores[$k]['count'] = DB::name('wine_recharge')->where(array('pay_type'=>2,'status'=>1,'stores_id'=>$v['id']))->where("regist_time > ".$begin_time." and regist_time < ".$end_time)->count();
            //     //充值金额
            //     $data_stores[$k]['amount'] = DB::name('wine_recharge')->where(array('pay_type'=>2,'status'=>1,'stores_id'=>$v['id']))->where("regist_time > ".$begin_time." and regist_time < ".$end_time)->sum('amount');
            //     if($data_stores[$k]['amount'] == null){
            //         $data_stores[$k]['amount'] = 0;
            //     }
            //     //本存本取
            //     $data_stores[$k]['consumption'] = DB::name('wine_consumption')->where(array('stores_id'=>$v['id'],'recharge_store_id'=>$v['id'],'consu_status'=>1,'status'=>1))->where("submit_time > ".$begin_time." and submit_time < ".$end_time)->count();
            // }

            // $res['active_stores'] = $active_stores;
            // $res['data_stores'] = $data_stores;

            $json_data = json_encode(['code' => 200, 'data' => $res, 'msg' =>'数据获取成功' ]);
        }else{
            $json_data = json_encode(['code' => 500, 'data' => '', 'msg' =>'数据获取失败' ]);
        }
        echo $jsonp ."(".$json_data.")";

    }
}

