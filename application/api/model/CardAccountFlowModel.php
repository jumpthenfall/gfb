<?php

namespace app\api\model;
use think\Model;
use think\Db;
use think\Exception;

class CardAccountFlowModel extends Model
{
    protected  $name = 'card_account_flow';

    /***
     * 获取日期列表
     * @param $card_id
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getAccountDateList($card_id)
    {
        $res = $this->field('add_time date')
            ->where('card_id','=',$card_id)
            ->group('date')
            ->order('add_time desc')
            ->select();
        return $res;
    }
}