<?php
namespace app\home\model;

use think\Model;
use think\Db;

class StoresModel extends Model
{
	public function StoreInfoByShopId($shop_id)
	{
		
		try{
			$result = Db::name('stores')->alias('s')
                ->field('s.storesname,s.sharepic,s.latitude,s.longitude,s.address,s.phone')
                ->join('tp_way_shop wp','wp.ws_stores_id = s.id')
                ->where('wp.id',$shop_id)
                ->select();
            return $result;
        }catch( PDOException $e){
            return array();
        }


		
	}


}