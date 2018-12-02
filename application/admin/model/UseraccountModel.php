<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class UseraccountModel extends Model
{
    protected $name = 'user_account';

    /**
     * 根据条件获取列表信息
     * @param $where
     */
    public function getUseraccount()
    {
        return $this->field('sum(rechargemoney) as rechargemoney,sum(balance) as balance,sum(consumptionmoney) as consumptionmoney')
                    ->where(array('status'=>1))
                    ->find();     
    }
}