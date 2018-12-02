<?php

namespace app\api\model;
use think\Model;
use think\Db;
use think\Exception;

class CardAccountModel extends Model
{
    protected  $name = 'card_account';

    /**
     * 查询卡账户信息
     * @param $card_number
     * @param password $
     * @return array
     */
    public function getCardAccountInfoByCardId($id)
    {
         $res = $this->field('total_money,balance,withdraw_money,total_num,withdraw_num')->where('card_id','=',$id)->find();
         return $res ? $res : array();

    }

    /**
     * 通过卡号获取卡绑定信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     */
    public function  getPhoneInfoByPK($id)
    {
        $res = $this->field('phone_version,phone_brand,phone_mac')->where(['id'=>$id])->find();
        return $res ? $res : array();
    }
}