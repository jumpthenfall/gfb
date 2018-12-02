<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class AverageuserModel extends Model
{
    protected $name = 'recharge';

    /**
     * 根据条件获取列表信息
     * @param $where
     * @param $Nowpage
     * @param $limits
     */
    public function getRechargeAll($map, $Nowpage, $limits)
    {
         $temp = 'tp_recharge.status = 1 and rechargestatus = 2 and tp_user.id = '.$map;
        return $this->field('tp_recharge.id,tp_user.nickname,tp_user.headimgurl,tp_user.mobile,tp_recharge.money,case tp_recharge.paytype when 1 then "微信充值" when 2 then "现金充值" else "没有数据" end as paytype,FROM_UNIXTIME(tp_recharge.finishtime) as finishtime,FROM_UNIXTIME(tp_recharge.rechargetime) as rechargetime,tp_stores.storesname,tp_recharge.rechargestatus,tp_admin.username as adminusername')
                                ->join('tp_user', 'tp_user.id = tp_recharge.user_id')
                                ->join('tp_admin', 'tp_admin.id = tp_recharge.admin_id')
                                ->join('tp_stores', 'tp_stores.id = tp_recharge.stores_id')
                                ->page($Nowpage,$limits)
                                ->where($temp)
                                ->order('tp_recharge.id desc')
                                ->select();
    }

    /**
     * 根据条件获取列表数量
     * @param $where
     */
    public function getRechargeCount($map)
    {
        $temp = 'tp_recharge.status = 1 and rechargestatus = 2 and tp_user.id = '.$map;
        return $this->field('tp_recharge.id,tp_user.nickname,tp_user.headimgurl,tp_user.mobile,tp_recharge.money,case tp_recharge.paytype when 1 then "微信充值" when 2 then "现金充值" when 3 then "支付宝充值" else "没有数据" end as paytype,FROM_UNIXTIME(tp_recharge.finishtime) as finishtime,tp_stores.storesname,tp_recharge.rechargestatus,tp_admin.username as adminusername')
                                ->join('tp_user', 'tp_user.id = tp_recharge.user_id')
                                ->join('tp_admin', 'tp_admin.id = tp_recharge.admin_id')
                                ->join('tp_stores', 'tp_stores.id = tp_recharge.stores_id')
                                ->where($temp)
                                ->order('tp_recharge.id desc')
                                ->count();
          
    }

  

    /**
     * 根据条件获取列表信息
     * @param $where
     * @param $Nowpage
     * @param $limits
     */
    public function getConsumptionAll($map, $Nowpage, $limits)
    {
        $temp = 'tp_consumption.status = 1 and tp_consumption.paystatus = 1 and tp_user.id = '.$map;
        return Db::name('consumption')->field('tp_consumption.id,tp_consumption.ordernum,tp_user.headimgurl,tp_user.mobile,tp_user.nickname,tp_consumption.price,case tp_consumption.paytype when 1 then "余额支付"  else "没有支付方式" end as paytype,tp_consumption.paystatus,FROM_UNIXTIME(tp_consumption.finishtime) as finishtime,tp_stores.storesname,tp_makeup.makeupname,tp_dresser.dressername')
                ->join('tp_user', 'tp_user.id = tp_consumption.user_id')
                ->join('tp_dresser', 'tp_dresser.id = tp_consumption.dresser_id')
                ->join('tp_makeup', 'tp_makeup.id = tp_consumption.makeup_id')
                ->join('tp_stores', 'tp_stores.id = tp_consumption.stores_id')
                 ->where($temp)
                ->page($Nowpage,$limits)
                ->order('tp_consumption.id desc')
                ->select();
    
    }
    /**
     * 根据条件获取列表数量
     * @param $where
     */
    public function getConsumptionCount($map)
    {
        $temp ='tp_consumption.status = 1 and tp_consumption.paystatus = 1 and tp_user.id = '.$map;
        return Db::name('consumption')->field('tp_consumption.id,tp_consumption.ordernum,tp_user.mobile,tp_user.headimgurl,tp_user.nickname,tp_consumption.price,case tp_consumption.paytype when 1 then "余额支付"  else "没有支付方式" end as paytype,tp_consumption.paystatus,FROM_UNIXTIME(tp_consumption.finishtime) as finishtime,tp_stores.storesname,tp_makeup.makeupname,tp_dresser.dressername')
                ->join('tp_user', 'tp_user.id = tp_consumption.user_id')
                ->join('tp_dresser', 'tp_dresser.id = tp_consumption.dresser_id')
                ->join('tp_makeup', 'tp_makeup.id = tp_consumption.makeup_id')
                ->join('tp_stores', 'tp_stores.id = tp_consumption.stores_id')
                 ->where($temp)
                ->order('tp_consumption.id desc')
                ->count();
    
    }

    

    

}