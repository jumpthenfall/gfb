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
class Ads extends Base
{

    /**
     * @SWG\Post(
     *     path="/api/ads/ads_list",
     *     tags={"Ads"},
     *     summary="广告列表",
     *     description="广告列表",
     * @SWG\Response( response="200", description="success" )
     *
     * )
     */
    public function ads_list()
    {
        try{
            $redis = new Redis();
            $cols = ['add_time','id','title','status','modified_time'];
            $order_col = $cols[mt_rand(0,4)];
            $order_type = time()%2 ? 'asc' : 'desc';
            if($redis->has('ads_'.$order_col.$order_type)){
                $ads_list = json_decode($redis->get('ads_'.$order_col.$order_type),1);
            }else{
                $ads_model = new AdsModel();
                $ads_list = $ads_model->getAdsList([],$order_col,$order_type);
                $redis->set('ads_'.$order_col.$order_type,json_encode($ads_list));
            }
            $this->setData(['list'=>$ads_list]);
        }catch (Exception $e){
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
        }
        return json($this->return_data);
    }

    /**
     * @SWG\Post(
     *     path="/api/ads/get_profit",
     *     tags={"Ads"},
     *     summary="获取利润",
     *     description="获取利润",
     * @SWG\Parameter(
     *     name="id",
     *     type="integer",
     *     required=true,
     *     in="formData",
     *     description="卡ID"
     * ),
     * @SWG\Response( response="200", description="success" )
     *
     * )
     */
    public function get_profit()
    {
        try{
            $hour = date('H');
            if($hour<8 || $hour>19){
                throw Exception('广告时间为8:00~20:00',50001);
            }
            $id = input('id/d');
            if(!$id){
                throw Exception('参数缺失',10001);
            }
            $card_model = new CardModel();
            if(!$card_info = $card_model->getCardInfoByPK($id)){
                throw Exception('卡信息错误',50002);
            }
            $max = bcmul(bcdiv($card_info['earning_peak'],3600,4),10000);//最大随机数
            $min = bcmul(bcdiv($card_info['earning_peak']-5,3600,4),10000);//最小随机数
            $money = bcdiv(mt_rand($min,$max),10000,4);
            if(!1){

            }else{
                $redis = new Redis();
                if($redis->get('card_account_number_' .$id) <  3600 && $redis->get('card_profit_timestamp') < time()-10){
                    $redis->lPush('card_profit_list',serialize(['id'=>$id,'money'=>$money]));
                    $redis->set('card_profit_timestamp',time());
                }
            }
//            $account_model = new CardAccountModel();
//            $account_info = $account_model->getCardAccountInfoByCardId($id);
//            if(!$account_info){
//                throw Exception('卡信息错误',50002);
//            }
//            $up['total_money'] = bcadd($money,$account_info['total_money'],4);
//            $up['balance'] = bcadd($money,$account_info['balance'],4);
//            $up['total_num'] = $account_info['total_num'] + 1;
//            $up_res = $account_model->where('card_id','=',$id)->update($up);
//            if(!$up_res){
//                throw Exception('更新失败',50002);
//            }
//            $redis = new Redis();
//            if($up_res == 1){
//                if($redis->has('card_account_money_'.$id) &&$redis->has('card_account_number_'.$id)){
//                    $redis->set('card_account_money_'.$id,bcadd($redis->get('card_account_money_'.$id),$money,4));
//                    $redis->inc('card_account_number_'.$id);
//                }else{
//                    $redis->set('card_account_money_'.$id,$money);
//                    $redis->set('card_account_number_'.$id,1);
//                }
//            }

            $this->setData(['money'=>$money]);
        }catch (Exception $e){
            $this->setData(['money'=>0]);
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
        }
        return json($this->return_data);
    }
}