<?php

namespace app\api\controller;
use app\api\model\AdsModel;
use app\api\model\CardAccountModel;
use app\api\model\CardModel;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

/**
 * swagger: 广告类
 */
class Shell extends Base
{

    /**
     * 更新用户日收益及日流水
     */
    public function manage_daily_data()
    {
        try{
            set_time_limit(0);
            ini_set('memory_limit', '1024M');
            $total_money = 0;
            $redis = new Redis();
            $list = $redis ->keys('card_account_money_*');
            $insert = [];
            $flow =[];
            $date = date('Y-m-d',strtotime('-1 day'));
            $account_model = new CardAccountModel();
            foreach ($list as $l){
                $money = $redis->get($l);
                $total_money = bcadd($total_money,$money,4);
                $id = substr($l,strrpos($l,'_')+1);
                $insert[] = [
                    'money'=>$money,
                    'number'=>$redis->get('card_account_number_'.$id),
                    'date'=>$date,
                    'card_id'=>$id
                ];
                $account = $account_model->getCardAccountInfoByCardId($id);
                $flow[] = [
                    'card_id'=>$id,
                    'money'=>$money,
                    'balance'=>bcadd($money,$account['balance'],4),
                    'add_time'=>$date . ' 20:00:00',
                    'remark'=>'日收益记录'
                ];
                $redis->rm($l);
                $redis->rm('card_account_number_'.$id);
                if(count($insert) == 1000){
                    $num = Db::name('card_daily_data')->insertAll($insert);
                    Db::name('card_account_flow')->insertAll($flow);
                    echo $num;
                    $insert = [];
                    $flow = [];
                }
            }

            Db::name('card_daily_data')->insertAll($insert);//添加用户每日收益
            Db::name('card_account_flow')->insertAll($flow);//增加用户资金流水
            Db::name('card_config')->where('col_name','=','remained_bonus')->setDec('col_value',(int)$total_money);
            Db::name('card_config')->where('col_name','=','shared_bonus')->setInc('col_value',(int)$total_money);
        }catch (Exception $e){
//            dump($e->getMessage());
        }
    }
}

