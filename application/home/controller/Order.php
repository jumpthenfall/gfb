<?php
namespace app\home\controller;

use think\Controller;
use think\Db;
use think\Model;
use app\home\model\OrderModel;


class Order extends Base
{

    public function index(){
        $roder_id = input('param.roder_id');
        //支付参数；1、活动用户id；2、门店id；3、商品数组（商品id，数量）；4、总价

        $order_p = Db::name('order')->field('id,order_num,order_money,cart_info,active_id')->where(array('id'=>$roder_id,'order_status'=>0))->find();

         $pp_tr = $order_p['cart_info'];

        $cart_json_res = json_decode($pp_tr);


       



       // $cart_info_re  = array();


        for ($i=0; $i < count($cart_json_res) ; $i++) { 

            $rtes_p = $cart_json_res[$i];


            $cart_info_re[$i] = Db::name('goods')->field('goods_name,price,pic')->where(array('id'=>$rtes_p->goods_id,'status'=>1))->find();

             $cart_info_re[$i]['num'] = $rtes_p->num;
    
        }



        $order_p['cart_info'] = $cart_info_re;
        

        if($order_p){
            return jsonp(array('code' => '0', 'success' => 'order调用成功','order_info' => $order_p));
        }else {
           // $this->error('保存失败');
            return jsonp(array('code' => 1, 'error' => '添加失败', 'order_info'=>$order_p));
        }


        
    }

    public function add(){

        $roder_id = input('param.roder_id');
        //支付参数；1、活动用户id；2、门店id；3、商品数组（商品id，数量）；4、总价

        $order_p = Db::name('order')->field('id,order_num,order_money,cart_info,active_id')->where(array('id'=>$roder_id,'order_status'=>0))->find();


        $pp_tr = $order_p['cart_info'];

        $cart_json_res = json_decode($pp_tr);


       



       // $cart_info_re  = array();


        for ($i=0; $i < count($cart_json_res) ; $i++) { 

            $rtes_p = $cart_json_res[$i];


            $cart_info_re[$i] = Db::name('goods')->field('goods_name,price,pic')->where(array('id'=>$rtes_p->goods_id,'status'=>1))->find();

    
        }



        $order_p['cart_info'] = $cart_info_re;

        



        if($order_p){
            return json_encode(array('code' => '0', 'success' => 'order调用成功','order_info' => $order_p));
        }else {
           // $this->error('保存失败');
            return json_encode(array('code' => 1, 'error' => '添加失败', 'order_info'=>$order_p));
        }


        
    }

    public function save($active_user_id,  $stores_id, $goods, $uid,  $totle_money){

       /* $active_user_id = input('post.active_user_id');
        $stores_id      = input('post.stores_id');
        $goods          = input('post.goods');
        $uid            = input('post.uid');
        $totle_money    = input('post.totle_money');*/


        //支付参数；1、活动用户active_user_id；2、门店stores_id；3、商品数组goods（商品id，数量）；4、总价totle_money

      //  return jsonp(array('code' => 0, 'msg' => '添加成功', 'active_user_id'=>$active_user_id,'stores_id'=>$stores_id,'goods'=>$goods,'totle_money'=>$totle_money));


        //if ($this->request->isPost()) {
         //   $data            = $this->request->post();

        $active_id_res = Db::name('active')->where(array('id'=>$active_user_id, 'status'=>1))->find();

        

        if($active_id_res == null){
        	return jsonp(array('code' => 0, 'error' =>'参加活动的用户信息有误！！'));
        }

        $stores_id_res = Db::name('stores')->where(array('id'=>$stores_id, 'status'=>1))->find();



        if($stores_id_res == null){
        	return jsonp(array('code' => 0, 'error' =>'参加活动的门店信息有误！！'));
        }

        $temp_totle = 0;
        $cart_info  = array();



        foreach ($goods as $key => $value) {


        	$goods_p = Db::name('goods')->field('price')->where(array('id'=>$value['id'], 'stores_id'=>$stores_id,'status'=>1))->find();

           // $pp =  Db::name('goods')->getLastSql();

            

        	if($goods_p == null){
        		return jsonp(array('code' => 0, 'error' =>'参加活动的商品信息有误！！'));
        	}

        	$temp_totle = $goods_p['price'] * $value['num'] + $temp_totle;

             
        	
			$cart_info[$key]['goods_id'] = $value['id'];
			$cart_info[$key]['num']      = $value['num'];
			$cart_info[$key]['price']    = $goods_p['price'];
        }

        $cart_zif = json_encode($cart_info);




        if($totle_money != $temp_totle){
        	return jsonp(array('code' => 0, 'error' =>'参加活动的商品价格有误！！'));
        }


        //status:0:待支付订单；1；已经支付的订单，待发货；2：已经发货，待收货的订单；3：已完成订单；4：失败订单；5：退款订单
        $temp['order_status'] = 0;

        $temp['uid']          = $uid;

        $temp['order_money']  = $temp_totle;

        $temp['add_id']       = $stores_id;

        $temp['add_time']     = time();

        $temp['order_num']    = date('YmdHis').rand(100000, 999999);

        $temp['cart_info']    = $cart_zif;

        $temp['active_id']    = $active_user_id;

        $temp['isdelete']     = 0;//默认为0


        //return jsonp(array('code' => 0, 'error' =>'参加活动的商品价格有误！！','temp'=>$temp));

        $order = new OrderModel();


        $res_id = $order->insertOrder($temp);
       
        if ($res_id != null) {
            $data_num['order_num'] = $temp['order_num'];

            $order_n = $order->getOrderNum($data_num);
            
            return jsonp(array('code' => '0', 'success' => '订单生成成功！','order_id' => $order_n['id']));

        } else {
          
            return jsonp(array('code' => 1, 'error' => '添加失败', 'orderid'=>$res_id));
        }
        
    }

     public function payorder($roder_id){

        $order_p = Db::name('order')->where(array('id'=>$roder_id,'order_status'=>1))->find();
        if($order_p){
            return json_encode(array('code' => '0', 'success' => 'order调用成功','order_info' => $order_p));
        }else {
           // $this->error('保存失败');
            return json_encode(array('code' => 1, 'error' => '添加失败', 'order_info'=>$order_p));
        }


     }

    

}