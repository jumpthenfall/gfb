<?php
namespace app\admin\model;

use think\Model;
use think\Db;

class DistributeLogModel extends Model
{
    protected $name = 'card_distribute_log';

    public function getLogList($where,$page,$limits)
    {
        $res = $this->alias('d')
            ->field('card_num,distribute_time,aa.real_name agent_name,ab.real_name operator_name')
            ->join('tp_admin aa','aa.id = d.agent_id','left')
            ->join('tp_admin ab','ab.id = d.operator_id','left')
            ->where($where)
            ->page($page,$limits)
            ->order('d.id','desc')
            ->select();
        return $res;
    }

    /**
     *获取日志记录
     * @param $where
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getLogExportData($where)
    {
        $res = $this->alias('d')
            ->field('card_num,distribute_time,aa.real_name agent_name,ab.real_name operator_name')
            ->join('tp_admin aa','aa.id = d.agent_id','left')
            ->join('tp_admin ab','ab.id = d.operator_id','left')
            ->where($where)
            ->order('d.id','desc')
            ->select();
        return $res;
    }

}