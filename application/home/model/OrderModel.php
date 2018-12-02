<?php
namespace app\home\model;

use think\Model;
use think\Db;

class OrderModel extends Model
{
	public function insertOrder($temp)
	{
		
		try{
			$result = Db::table('tp_order')->insert($temp);
           //$result =  $order->add($temp);
            
            return $result;
        }catch( PDOException $e){
            return 0;
        }


		
	}

	public function getOrderNum($data_num)
	{
		return Db::name('order')->field('id')->where($data_num)->find();
	}
    
}