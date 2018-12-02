<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class UserWithdrawModel extends Model
{
    protected $name = 'user_withdraw';

    /**
     * 根据条件获取列表信息
     * @param $where
     */
    public function getWithdrawList($where,$page,$limits)
    {
        $res =  $this->alias('uw')
            ->field('uw.id,uw.status,card_id,user_id,money,account,account_name,add_time,finished_time,uw.remark,u.mobile,
            IFNULL(a.username,"") username
            ')
            ->join('tp_user u','u.id = uw.user_id','left')
            ->join('tp_admin a','a.id = uw.operator_id','left')
            ->where($where)
            ->page($page,$limits)
            ->select();
        return $res;
    }

    /**
     *
     * 获取导出列表
     * @param $where
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getExportList($where)
    {
        $res =  $this->alias('uw')
            ->field('uw.id,uw.status,card_id,user_id,money,account,account_name,add_time,finished_time,
            uw.remark,u.mobile,IFNULL(a.username,"") username,
            (CASE uw.status WHEN 1 THEN "待审核" WHEN 2 THEN "已完成" WHEN 3 THEN "已拒绝" ELSE "其它" END) statusRemark
            ')
            ->join('tp_user u','u.id = uw.user_id','left')
            ->join('tp_admin a','a.id = uw.operator_id','left')
            ->where($where)
            ->select();
        return $res;
    }

    /**
     * 获取提现统计数据
     * @return array|false|\PDOStatement|string|Model
     */
    public function getWithdrawCountData()
    {
        return $this->field('count(*) withdraw_num,sum(money) withdraw_money')
            ->where(['status'=>2,'is_delete'=>0])
            ->find();
    }
}