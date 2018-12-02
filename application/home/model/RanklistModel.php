<?php
namespace app\home\model;

use think\Model;
use think\Db;

class RanklistModel extends Model
{
   public function getRanklist($rank_temp){
   	  $result = Db::name('ranklist')->field('id,popularValue')->where($rank_temp)->find();
   }

   public function updateRank($data_r){
   	  $result = Db::table('tp_ranklist')->update($data_r);
   }

   public function insertRank($data_new){
   	  $result = Db::table('tp_ranklist')->insert($data_new);
   }

}