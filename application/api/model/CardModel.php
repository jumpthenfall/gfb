<?php

namespace app\api\model;
use think\Model;
use think\Db;
use think\Exception;

class CardModel extends Model
{
    protected  $name = 'card';

    /**
     * 查询卡信息
     * @param $card_number
     * @param password $
     * @return array
     */
    public function getCardInfo($where)
    {
         $res = $this
             ->field('id,user_id,card_number,password,IFNULL(start_time,"") start_time,phone_brand,IFNULL(end_time,"") end_time,phone_version,phone_mac,status,time_length')
             ->where($where)->find();
         return $res ? $res : array();

    }

    /**
     * 通过卡号获取卡绑定信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     */
    public function  getCardInfoByPK($id)
    {
        $res = $this
            ->field('id,user_id,card_number,password,earning_peak,start_time,end_time,phone_version,phone_brand,phone_mac,time_length')
            ->where(['id'=>$id])->find();
        return $res ? $res : array();
    }

    /**
     * 判断卡是否存在
     * @param $id
     * @return bool
     */
    public function card_exist($id)
    {
        $res = $this->where(['id'=>$id])->find();
        return $res ? true : false;
    }

    /**
     * 通过卡号查询用户信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     */
    public function getUserInfoByCardId($id)
    {
        $res = $this->alias('c')
            ->field('c.user_id,u.name,u.nickname,mobile,ali_account,ali_nickname,headimgurl')
            ->join('user u','c.user_id = u.id','left')
            ->where('c.id','=',$id)
            ->find();
        return $res;
    }


}