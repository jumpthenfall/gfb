<?php
namespace app\home\model;

use think\Model;
use think\Db;

class RanklistInfoModel extends Model
{
   public function setRanklistinfo($data_ri){
   	   try{
			$result = Db::table('tp_ranklist_info')->insert($data_ri);
            return $result;
        }catch( PDOException $e){
            return 0;
        }
   }
   public function updateRanklistinfo($temp_rank){
   	   try{
			$result = Db::table('tp_ranklist_info')->update($temp_rank);
            return $result;
        }catch( PDOException $e){
            return 0;
        }
   }
}