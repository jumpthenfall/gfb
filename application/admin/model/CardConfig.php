<?php

namespace app\admin\model;
use think\Model;
use think\Db;

class CardConfig extends Model
{
    protected $name = 'card_config';

    /**
     * 获取卡配置列表
     * @param $where
     * @param $page
     * @param $limits
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getCardConfigList($where)
    {
        $res = $this
            ->field('col_name,col_value,status,add_time,remark')
            ->where($where)->select();
        return $res;
    }


}