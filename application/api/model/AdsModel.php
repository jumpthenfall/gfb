<?php
namespace app\api\model;

use think\Model;

class AdsModel extends Model
{
    protected $name = 'card_ads';

    /**
     * 获取广告列表
     * @param $user_id
     * @return int
     */
    public function  getAdsList($where,$order_col,$order_type)
    {
        $res = $this->field('id,title,url')
            ->where($where)
            ->where(['is_delete'=>0,'status'=>1])
            ->order($order_col , $order_type)
            ->limit(0,15)
            ->select();
        return $res ;
    }
}