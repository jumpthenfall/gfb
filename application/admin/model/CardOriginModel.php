<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class CardOriginModel extends Model
{
    protected $name = 'card_origin';

    /**
     * 根据条件获取列表信息
     * @param $where
     */
    public function getUnregisterCardList($where,$page,$limits)
    {
        $res =  $this->alias('c')
            ->field('c.id,c.card_number,c.password,c.add_time,
            (CASE c.is_distribute WHEN 1 THEN "已分配" WHEN 0 THEN "未分配"ELSE "状态异常" END) statusRemark
            ')
            ->where($where)
            ->page($page,$limits)
            ->select();
        return $res;
    }

    /**
     * 查询未分发（未售）卡列表
     * @param $where
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getUnregisterExportDate($where)
    {
        $res =  $this->alias('c')
            ->field('c.id,c.card_number,c.password,c.add_time,
            (CASE c.status WHEN 1 THEN "已激活" WHEN 2 THEN "已禁用" WHEN 3 THEN "已过期" WHEN 4 THEN "未激活" ELSE "状态异常" END) statusRemark
            ')
            ->where($where)
            ->select();
        return $res;
    }

    /**
     * 获取已分发卡列表
     * @param $where
     * @param $page
     * @param $limits
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getCardList($where,$page,$limits)
    {
        $res =  $this->alias('c')
            ->field('
            c.id,c.card_number,c.password,c.add_time,ca.total_money,IFNULL(c.start_time,"未激活") start_time,c.status,
            ca.balance,IFNULL(c.end_time,"未激活") end_time,ca.withdraw_money,ca.withdraw_num,ca.total_num,IFNULL(a.username,"") agent_name,
            (CASE c.status WHEN 1 THEN "已激活" WHEN 2 THEN "已禁用" WHEN 3 THEN "已过期" WHEN 4 THEN "未激活" ELSE "状态异常" END) statusRemark
            ')
            ->join('tp_card_account ca','c.id = ca.card_id','left')
            ->join('tp_admin a','c.agent_id = a.id','left')
            ->where($where)
            ->page($page,$limits)
            ->select();
        return $res;
    }

    /**
     * 获取导出数据列表
     * @param $where
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getExportList($where)
    {
        $res =  $this->alias('c')
            ->field('
            c.id,c.card_number,c.password,c.add_time,ca.total_money,IFNULL(c.start_time,"未激活") start_time,c.status,
            ca.balance,IFNULL(c.end_time,"未激活") end_time,ca.withdraw_money,ca.withdraw_num,ca.total_num,IFNULL(a.username,"") agent_name,
            (CASE c.status WHEN 1 THEN "已激活" WHEN 2 THEN "已禁用" WHEN 3 THEN "已过期" WHEN 4 THEN "未激活" ELSE "状态异常" END) statusRemark
            ')
            ->join('tp_card_account ca','c.id = ca.card_id','left')
            ->join('tp_admin a','c.agent_id = a.id','left')
            ->where($where)
            ->select();
        return $res;
    }

    /**
     * 获取所有代理列表
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getAgentList()
    {
        $res = $this->alias('c')
            ->join('tp_admin a','c.agent_id = a.id')
            ->field('a.id,a.username')
            ->group('c.agent_id')
            ->where('c.agent_id','not null')
            ->select();
        return $res;
    }
}