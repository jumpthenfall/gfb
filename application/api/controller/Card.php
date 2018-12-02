<?php

namespace app\api\controller;
use app\api\model\CardAccountFlowModel;
use app\api\model\CardAccountModel;
use app\api\model\CardModel;

use app\api\validate\LoginValidate;
use think\cache\driver\Redis;
use think\Controller;
use think\Db;
use think\Exception;

/**
 * swagger: 卡接口
 */
class Card extends Base
{

    /**
     * @SWG\Post(
     *     path="/api/card/card_info",
     *     tags={"Card"},
     *     summary="卡信息",
     *     description="卡信息",
     * @SWG\Parameter(
     *     name="id",
     *     type="integer",
     *     required=true,
     *     in="formData",
     *     description="ID"
     * ),
     * @SWG\Response( response="200", description="success" )
     * )
     */
    public function card_info()
    {
        try{
            $id = input('id/d');
            if(!$id){
                throw Exception('参数缺失',10001);
            }
            $card_model = new CardModel();
            $card_info = $card_model->getCardInfoByPK($id);
            if(!$card_info){
                throw Exception('查询安全信息失败',40001);
            }
            $this->setData($card_info);
        }catch (Exception $e){
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
        }
        return json($this->return_data);
    }
    /**
     * @SWG\Post(
     *     path="/api/card/account_info",
     *     tags={"Card"},
     *     summary="账户信息",
     *     description="账户信息",
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
    public function account_info()
    {
        try{
            $id = input('id/d');
            if(!$id){
                throw Exception('参数缺失',10001);
            }
            $card_model = new CardAccountModel();
            $card_info = $card_model->getCardAccountInfoByCardId($id);
            if(!$card_info){
                throw Exception('查询资金信息失败',40001);
            }
            $card_info['allowed_money'] = bcmul(bcdiv($card_info['balance'],100),100);
            $this->setData($card_info);
        }catch (Exception $e){
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
        }
        return json($this->return_data);
    }

    /**
     * @SWG\Post(
     *     path="/api/card/withdraw_money",
     *     tags={"Card"},
     *     summary="用户申请提现表",
     *     description="用户申请提现表",
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
    public function withdraw_money()
    {
        try{
            $id = input('id/d');
            if(!$id){
                throw Exception('参数缺失',10001);
            }
            $card_model = new CardAccountModel();
            $card_info = $card_model->getCardAccountInfoByCardId($id);
            if(!$card_info){
                throw Exception('查询资金信息失败',40001);
            }
            $redis = new Redis();
            $card_info['allowed_money'] = bcmul(bcdiv($card_info['balance'],100),100);
            $card_info['today_profit'] = $redis->has('card_account_money_'.$id) ? $redis->get('card_account_money_'.$id) : 0;
            $this->setData($card_info);
        }catch (Exception $e){
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
        }
        return json($this->return_data);
    }
    /**
     * @SWG\Post(
     *     path="/api/card/withdraw_confirm",
     *     tags={"Card"},
     *     summary="确认提现",
     *     description="确认提现",
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
    public function withdraw_confirm()
    {
        try{
            Db::startTrans();
            $time = date('Y-m-d H:i:s');
            $id = input('id/d');
            if(!$id){
                throw Exception('参数缺失',10001);
            }
            $account_model = new CardAccountModel();
            $card_info = $account_model->getCardAccountInfoByCardId($id);
            if(!$card_info){
                throw Exception('查询资金信息失败',40001);
            }
            $card_info['allowed_money'] = bcmul(bcdiv($card_info['balance'],100),100);
            if(!$card_info['allowed_money']){
                throw Exception('可提现金额小于100',40001);
            }
            $up['balance'] = bcsub($card_info['balance'],$card_info['allowed_money'],4);
            $up['withdraw_money'] = bcadd($card_info['withdraw_money'],$card_info['allowed_money'],4);
            $up['withdraw_num'] = $card_info['withdraw_num'] + 1;
            $a_res = $account_model->where('card_id','=',$id)->update($up);
            if(!$a_res){
                throw Exception('更新钱包失败',40001);
            }
            $card_model = new CardModel();
            $user_info = $card_model->getUserInfoByCardId($id);
            if(!$user_info){
                throw Exception('未登记收款账号，请添加收款账号',40001);
            }
            $withdraw['card_id'] = $id;
            $withdraw['user_id'] = $user_info['user_id'];
            $withdraw['money'] = $card_info['allowed_money'];
            $withdraw['account'] = $user_info['ali_account'];
            $withdraw['account_name'] = $user_info['ali_nickname'];
            $withdraw['status'] = 1;
            $withdraw['add_time'] =$time ;
            $draw_id = Db::name('user_withdraw')->insertGetId($withdraw);
            if(!$draw_id){
                throw Exception('发起提现请求失败',40001);
            }
            $flow['card_id'] = $id;
            $flow['money'] = - $card_info['allowed_money'];
            $flow['balance'] = $up['balance'];
            $flow['add_time'] = $time;
            $flow['remark'] = '提现';
            $flow_id = Db::name('card_account_flow')->insertGetId($flow);
            if(!$flow_id){
                throw Exception('添加账户流水失败',40001);
            }
            $this->setData($up);
            Db::commit();
        }catch (Exception $e){
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
            Db::rollback();
        }
        return json($this->return_data);
    }
    /**
     * @SWG\Post(
     *     path="/api/card/bonus_pool",
     *     tags={"Card"},
     *     summary="平台资金池信息",
     *     description="平台资金池信息",
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
    public function bonus_pool()
    {
        try{
            $card_id = input('id');//卡ID
            $where['is_delete'] = 0;
            $where['status'] = 1;
            $where['col_name'] = ['in',['total_bonus','shared_bonus','remained_bonus']];
            $config = Db::name('card_config')->field('col_name,col_value,remark')->where($where)->select();
            $config[] = [
                "col_name"=> "continue_days",
                "col_value"=> $this->getContinueDays($card_id),
                "remark"=> "连续登录天数"
            ];
            $this->setData($config);
        }catch (Exception $e){
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
        }
        return json($this->return_data);
    }
    /**
     * @SWG\Post(
     *     path="/api/card/daily_data",
     *     tags={"Card"},
     *     summary="卡日收入接口",
     *     description="卡日收入接口",
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
    public function daily_data()
    {
        try{
            $id = input('id/d');
            if(!$id){
                throw Exception('参数缺失',10001);
            }
            $card_model = new CardModel();
            if(!$card_model->card_exist($id)){
                throw Exception('卡信息错误',40001);
            }
            $redis = new Redis();
            $data['today']['money'] = $redis->has('card_account_money_'.$id) ? $redis->get('card_account_money_'.$id) : '0.0000';
            $data['today']['number'] = $redis->has('card_account_number_'.$id) ? $redis->get('card_account_number_'.$id) : 0;
            $y_time = date('Y-m-d',strtotime('- 1day'));
            $y_data = Db::name('card_daily_data')->where(['card_id'=>$id,'date'=>$y_time])->find();
            $data['yesterday']['money'] = $y_data ? $y_data['money'] : '0.0000';
            $data['yesterday']['number'] = $y_data ? $y_data['number'] : 0;
            $account_model = new CardAccountModel();
            $t_data = $account_model->getCardAccountInfoByCardId($id);
            $data['total']['money'] = $t_data?$t_data['total_money']:0;
            $data['total']['number'] =$t_data? $t_data['total_num']:0;
            $data['total']['number'] =$data['total']['number'] < $data['today']['number'] ?$data['today']['number']:$data['total']['number'] ;
            $data['total']['money'] =$data['total']['money'] < $data['today']['money'] ?$data['today']['money']:$data['total']['money'] ;

            $this->setData($data);
        }catch (Exception $e){
            $this->setMsg($e->getMessage());
            $this->setCode($e->getCode());
        }
        return json($this->return_data);
    }

    /**
     * 获取用户联系登录天数
     * @param null $card_id
     * @return int
     */
    public function getContinueDays($card_id)
    {
        $model = new CardAccountFlowModel();
        $list = $model->getAccountDateList($card_id);

        $days = 1;
        $temp = date('Y-m-d',strtotime('- 1day'));
        foreach ($list as $l) {
            if($temp != date('Y-m-d',strtotime($l['date']))){
                break;
            }
            $days += 1;
            $temp = date('Y-m-d',strtotime('- 1day',strtotime($temp)));
        }
        return $days;
    }

}