<?php
namespace app\home\controller;

use think\Controller;
use think\Db;
use think\Model;
use app\home\model\RanklistModel;
use app\home\model\RanklistInfoModel;

/**
 * 样例控制器
 *
 * Class Index
 * @package app\index\controller
 * @author goldeagle
 */
class Pay extends Base
{
    public function index()
    {
    	$id = input('param.id');
    	$activeid = input('param.activeid');

		//order_num 订单的订单号
		//transaction_id  微信支付订单号
		//tprenqi_temp 为0，表示订单可以增加人气值，为1时是已经增加了人气值
	  $find_order_res = Db::name('order')->field('id,uid,order_num,active_id,order_money,add_id')->where(array('id'=>$id,'order_status'=>0))->find();



      $data['order_status'] = 1;

      $data['pay_time'] = time();
	  //$data['transaction_id'] = $transaction_id;

      $data['id'] = $find_order_res['id'];

     // $list = M()->table('bg_content')->save($data);

      $order_update_res = Db::name('order')->update($data);

      if($order_update_res){

		  $find_renqi_res = Db::name('order')->field('id,uid,active_id,order_money')->where(array('order_num'=>$find_order_res['order_num'],'order_status'=>1,'tprenqi_temp'=>0))->find();

		  if($find_renqi_res){
			$active_selsect_res = Db::name('active')->field('sentiment')->where(array('id'=>$find_renqi_res['active_id'],'status'=>1))->find();

			$temp['sentiment'] = $active_selsect_res['sentiment'] + $find_renqi_res['order_money'];
			$temp['id'] = $find_renqi_res['active_id'];

			$active_update_res = Db::name('active')->update($temp);
			if($active_update_res){
				$ranklistinfo = new RanklistInfoModel();

				$data_ri['user_id'] = $find_renqi_res['uid'];
				$data_ri['active_id'] = $find_renqi_res['active_id'];
				$data_ri['order_id'] = $find_renqi_res['id'];
				$data_ri['popularValue'] = $find_renqi_res['order_money'];

				$data_ri['sendtime'] = time();
				$data_ri['is_use'] = 1;
				$data_ri['status'] = 1;
				$data_ri['activity_id'] = 0;//活动规则目前没有定出

				$ranklistinfo_save = $ranklistinfo->setRanklistinfo($data_ri);
				if($ranklistinfo_save){
					$ranklist = new RanklistModel();

					$rank_temp['user_id'] = $find_renqi_res['uid'];
					$rank_temp['active_id'] = $find_renqi_res['active_id'];
					$rank_temp['status'] = 1;

					//$ranklist_se = $ranklist->getRanklist($rank_temp);
					$ranklist_se = Db::table('tp_ranklist')->field('id,popularValue')->where($rank_temp)->find();//$ranklist->getRanklist($rank_temp);

					if($ranklist_se){
						$data_r['popularValue'] = $ranklist_se['popularValue'] + $find_renqi_res['order_money'];
						$data_r['id'] = $ranklist_se['id'];

						
						$rank_update = $ranklist->updateRank($data_r);
						if($rank_update){
							//设置session uid
              //              session('uid', $find_renqi_res['uid']);


							return $this->fetch('active/index',['storesid'=>$find_order_res['add_id'], 'code'=>1, 'uid'=>$find_order_res['uid']]);
						}else{
							$temp_rank['remarke'] = "总榜更新出现问题，未更新，需要人为更新";
							$temp_rank['id'] = $ranklistinfo_save;
							$temp_rank_save = $ranklistinfo->updateRanklistinfo($temp_rank);
							return $this->fetch('active/index',['storesid'=>$find_order_res['add_id'], 'code'=>0, 'uid'=>$find_order_res['uid']]);
						}
					}else{
						$data_new['user_id'] = $find_renqi_res['uid'];
						$data_new['active_id'] = $find_renqi_res['active_id'];
						$data_new['popularValue'] = $find_renqi_res['order_money'];
						$data_new['activity_id'] = 0;//活动规则目前没有定出
						$data_new['status'] = 1;

						$rank_new = $ranklist->insertRank($data_new);
						if($rank_new){
							return $this->fetch('active/index',['storesid'=>$find_order_res['add_id'], 'code'=>1, 'uid'=>$find_order_res['uid']]);
						}else{
							$temp_rank['remarke'] = "总榜更新出现问题，未更新，需要人为更新";
							$temp_rank['id'] = $ranklistinfo_save;
							$temp_rank_save = $ranklistinfo->updateRanklistinfo($temp_rank);
							return $this->fetch('active/index',['storesid'=>$find_order_res['add_id'], 'code'=>0, 'uid'=>$find_order_res['uid']]);
						}

					}

				}else{
					$temp_rank['remarke'] = "明细更新出现问题，未更新，需要人为更新";
					//$temp_rank['id'] = $ranklistinfo_save;

					$temp_rank['user_id'] = $find_renqi_res['uid'];
					$temp_rank['active_id'] = $find_renqi_res['active_id'];
					$temp_rank['order_id'] = $find_renqi_res['id'];
					$temp_rank['popularValue'] = $find_renqi_res['order_money'];

					$temp_rank['sendtime'] = time();
					$temp_rank['is_use'] = 1;
					$temp_rank['status'] = 1;
					$temp_rank['activity_id'] = 0;//活动规则目前没有定出


					$temp_rank_save = $ranklistinfo->setRanklistinfo($temp_rank);
					return $this->fetch('active/index',['storesid'=>$find_order_res['add_id'], 'code'=>0, 'uid'=>$find_order_res['uid']]);
				}



			 /* return $this->fetch('active/index',['storesid'=>$find_order_res['add_id']]);*/
			}else{
			  return $this->fetch('active/index',['storesid'=>$find_order_res['add_id'], 'code'=>0, 'uid'=>$find_order_res['uid']]);
			}
		  }else{
			  return $this->fetch('active/index',['storesid'=>$find_order_res['add_id'], 'code'=>0, 'uid'=>$find_order_res['uid']]);
		  }

      }else{

        return $this->fetch('active/index',['storesid'=>$find_order_res['add_id'], 'code'=>0, 'uid'=>$find_order_res['uid']]);
      }

     // return $this->fetch('active/index');


        //return $this->fetch('index',['jsApiParameters'=>$jsApiParameters,'editAddress'=>$editAddress]);
    }
    public function save()
    {

		//order_num 订单的订单号
		//transaction_id  微信支付订单号
		//tprenqi_temp 为0，表示订单可以增加人气值，为1时是已经增加了人气值
	  /*$find_order_res = Db::name('order')->field('id,active_id,order_money')->where(array('order_num'=>$order_num,'order_status'=>0))->find();



      $data['order_status'] = 1;

      $data['pay_time'] = time();
	  $data['transaction_id'] = $transaction_id;

      $data['id'] = $find_order_res['id'];*/

     // $list = M()->table('bg_content')->save($data);

      /*$order_update_res = Db::name('order')->update($data);

      if($order_update_res){

		  $find_renqi_res = Db::name('order')->field('id,active_id,order_money')->where(array('order_num'=>$order_num,'order_status'=>1,'tprenqi_temp'=>0))->find();

		  if($find_renqi_res){
			$active_selsect_res = Db::name('active')->field('sentiment')->where(array('id'=>$find_renqi_res['active_id'],'status'=>1))->find();

			$temp['sentiment'] = $active_selsect_res['sentiment'] + $find_renqi_res['order_money'];
			$temp['id'] = $find_renqi_res['active_id'];

			$active_update_res = Db::name('active')->update($temp);
			if($active_update_res){

			  return $this->fetch('active/index');
			}else{
			  return $this->fetch('active/index');
			}
		  }else{
			  return $this->fetch('active/index');
		  }

      }else{

        return $this->fetch('active/index');
      }*/

      return $this->fetch('active/index');


        //return $this->fetch('index',['jsApiParameters'=>$jsApiParameters,'editAddress'=>$editAddress]);
    }
}
