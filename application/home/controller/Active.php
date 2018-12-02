<?php
namespace app\home\controller;

use app\home\model\ActiveModel;
use app\home\model\UserModel;
use think\Db;


class Active extends Base
{
   

    public function index()
    {
        $storesid = input('param.storesid');

        $jssdk = new Jssdk();
        $res_jssdk = $jssdk->getSignPackage();
        //var_dump($res_jssdk);
       // exit;
        $this->assign('jssdk',$res_jssdk);



        if (isset($_GET['code'])){
            session('uid', 0);
            //session('uid', 0);
            $code_res = $_GET['code'];
            //$storesid = $_GET['storesid'];
            

            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx1a977e6944e78282&secret=40af637ea814759753770a4e87228a15&code=".$code_res."&grant_type=authorization_code";
            //$ttoo_res = file_get_contents($url);

            //echo "====".$storesid."---".$url;
            //exit;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $res_p = curl_exec($ch);
            curl_close($ch);

            $array_info = json_decode($res_p,true);

            //var_dump($array_info);
            //exit;

            

             $url_info = "https://api.weixin.qq.com/sns/userinfo?access_token=".$array_info['access_token']."&openid=".$array_info['openid'];

            $ch1 = curl_init();
            curl_setopt($ch1, CURLOPT_URL, $url_info);
            curl_setopt($ch1, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
            $res_p1 = curl_exec($ch1);
            curl_close($ch1);

            $array_info1 = json_decode($res_p1,true);

            

           // var_dump($array_info1);
            $openid = $array_info1['openid'];
            $headimgurl = $array_info1['headimgurl'];
            $nickname = $array_info1['nickname'];

             $user_select = new UserModel();
             $user_tp_se['openid'] = $openid;
             $user_tp_se['status'] = 1;


            $user_temp = $user_select->getUserOpenid($user_tp_se);
           
            if($user_temp != null){
                $openid_res = $user_temp['id'];

                $this->assign('storesid',$storesid);
                $this->assign('code',1);
                $this->assign('uid',$openid_res);

                //设置session uid
                session('uid', $openid_res);

                
                return $this->fetch('active/index');
            }else{
               // $user_temp11 = Db::name('user')->field('id')->order("id desc")->limit(1)->find();


                $userinfos = new UserModel();
                $userinfos_data['openid'] = $openid;
                $userinfos_data['headimgurl'] = $headimgurl;
               // if(is_string($nickname)){
                  //  $userinfos->nickname = $nickname;
               // }else{
               //     $userinfos->nickname = "####";
               // }
                //
                $userinfos_data['nickname'] = $nickname;
                $userinfos_data['status'] = 1;
                $userinfos_data['sex'] = $array_info1['sex'];
                $userinfos_data['regtime'] = time();
                $userinfos_data['userip'] = $_SERVER['REMOTE_ADDR'];

                

                $user_save = $userinfos->insertUserInfo($userinfos_data);
                if($user_save){

                    $id_tp = $user_save;

                   // echo $userinfos->id;
                    //exit;

                    $this->assign('storesid',$storesid);
                    $this->assign('code',1);
                    $this->assign('uid',$id_tp);


                    //设置session uid
                    session('uid', $id_tp);



                    return $this->fetch('active/index');

                }else{
                    $this->assign('storesid',$storesid);
                    $this->assign('code',0);
                    $this->assign('uid',0);

                  
                    
                    return $this->fetch('active/index');
                }


            }
            
        }else{
           // echo "NO CODE";

             
           //  $url_info = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa7d04aa2082e7edb&redirect_uri=http://tpmv.whjxry.net/active/index/storesid/"+storesid+".html&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect";


          //  $res = file_get_contents($url_info);

            if($storesid == ''){
                $storesid = 3;
            }

            //session('storesid', $storesid);
            //session('code', 0);
            //session('uid', 0);

            if(session('uid') == null){
                $this->assign('code',0);
                $this->assign('uid',0);
                session('uid', 0);
            }else{
                $this->assign('code',1);
                $this->assign('uid',session('uid'));
            }

            $this->assign('storesid',$storesid);

           return $this->fetch('active/index');
        }   

    }

	public function indexapi()
    {
        
        $storesid = input('post.storesid');
        $keyword = input('post.keyword');




        $page = 1;
        $map = [];
        if($keyword){
            session('activekeyword',$keyword);
            $map['username'] = ['like', "%{$keyword}%"];
        }else{
			if(session('activekeyword')!=''&&$page>1){
        		$map['username'] = ['like', "%".session('activekeyword')."%"];
        	
        	}else{
        		session('activekeyword',null);
        	}
		}

        //stores_id  门店的id
        if($storesid != ''){
            $map['stores_id'] = $storesid;
        }else{
            $map['stores_id'] = 3;
        }
        
        $map['status'] = 1;
        $active = new ActiveModel();
       
        $active_list = $active->getActiveList($map);
     
      //  foreach($active_list as $val){
       //    $key_arrays[]=$val['sentiment'];
       // }

       // array_multisort($key_arrays,SORT_DESC,SORT_NUMERIC,$active_list);

        //return $active_list;

        $len = count($active_list);


          //设置一个空数组 用来接收冒出来的泡
          //该层循环控制 需要冒泡的轮数
        for($i=1;$i<$len;$i++)
        { //该层循环用来控制每轮 冒出一个数 需要比较的次数
            for($k=0;$k<$len-$i;$k++)
            {
               if($active_list[$k]['sentiment']<$active_list[$k+1]['sentiment'])
                {
                    $tmp=$active_list[$k+1];
                    $active_list[$k+1]=$active_list[$k];
                    $active_list[$k]=$tmp;
                }
            }
        }

      //  var_dump($active_list);
        //exit;


		if($active_list !== null){

			return jsonp(array('code' => '0', 'success' => '首页信息列表，调用成功！','active_list' => $active_list));
			//return jsonp(array('code' => '0', 'success' => '首页信息列表，调用成功！','active_list' => 'test'));
			//return json_encode(array('active_list' => $active_list));
		}else{
			return jsonp(array('code' => '0', 'success' => '首页信息列表，调用成功！','active_list' => $active_list));
			//return jsonp(array('code' => '0', 'success' => '首页信息列表，调用成功！','active_list' => 'test'));
		}

       //return $this->fetch('index');

    }



     public function gift(){

        $id = input('param.id');
        $storeid = input('param.storeid');
        $uid = input('param.uid');

        $storesid = input('param.storesid');

        $jssdk = new Jssdk();
        $res_jssdk = $jssdk->getSignPackage();
        //var_dump($res_jssdk);
       // exit;
        $this->assign('jssdk',$res_jssdk);


        if(isset($_GET['code'])){
            session('uid', 0);
            $code_res = $_GET['code'];
            //$storesid = $_GET['storesid'];
            

            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx1a977e6944e78282&secret=40af637ea814759753770a4e87228a15&code=".$code_res."&grant_type=authorization_code";
            //$ttoo_res = file_get_contents($url);

            //echo "====".$storesid."---".$url;
            //exit;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $res_p = curl_exec($ch);
            curl_close($ch);

            $array_info = json_decode($res_p,true);

            //var_dump($array_info);
            //exit;

            

             $url_info = "https://api.weixin.qq.com/sns/userinfo?access_token=".$array_info['access_token']."&openid=".$array_info['openid'];

            $ch1 = curl_init();
            curl_setopt($ch1, CURLOPT_URL, $url_info);
            curl_setopt($ch1, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
            $res_p1 = curl_exec($ch1);
            curl_close($ch1);

            $array_info1 = json_decode($res_p1,true);

           // var_dump($array_info1);
            $openid = $array_info1['openid'];
            $headimgurl = $array_info1['headimgurl'];
            $nickname = $array_info1['nickname'];

            // $user_select = new UserModel();

             $user_select = new UserModel();
             $user_tp_se['openid'] = $openid;
             $user_tp_se['status'] = 1;


            $user_temp = $user_select->getUserOpenid($user_tp_se);

           // $user_temp = $user_select->field('id,openid,headimgurl')->where(array('openid' => $openid,'status' => 1))->find();

            
            if($user_temp != null){
                $openid_res = $user_temp['id'];

                $marke['id'] = $id;
                $marke['stores_id'] = $storeid;
                $marke['status'] = 1;
                $active_res = Db::name('active')->field('id,username,sentiment,pic')->where($marke)->find();
                $this->assign('activeres',$active_res);
               
                //设置session uid
                session('uid', $openid_res);


                $this->assign('id',$id);
                $this->assign('storeid',$storeid);
                $this->assign('code',1);
                $this->assign('uid',$openid_res);
                return $this->fetch('active/gift');


            }else{


                $user_temp11 = Db::name('user')->field('id')->order("id desc")->limit(1)->find();

               $userinfos = new UserModel();
                $userinfos_data['openid'] = $openid;
                $userinfos_data['headimgurl'] = $headimgurl;
               // $userinfos_data['nickname'] = $nickname;
                $userinfos_data['status'] = 1;
                $userinfos_data['sex'] = $array_info1['sex'];
                $userinfos_data['regtime'] = time();
                $userinfos_data['userip'] = $_SERVER['REMOTE_ADDR'];

                

                $user_save = $userinfos->insertUserInfo($userinfos_data);
                if($user_save){
                    $data_getid['openid'] = $openid;
                    $data_getid['status'] = 1;

                    $getid_res = $userinfos->getId($data_getid);

                    $id_tp = $getid_res['id'];

                    $marke['id'] = $id;
                    $marke['stores_id'] = $storeid;
                    $marke['status'] = 1;
                    $active_res = Db::table('tp_active')->field('id,username,sentiment,pic')->where($marke)->find();
                    $this->assign('activeres',$active_res);

                    //设置session uid
                    session('uid', $id_tp);


                    $this->assign('id',$id);
                    $this->assign('storeid',$storeid);
                    $this->assign('code',1);
                    $this->assign('uid',$id_tp);
                    return $this->fetch('active/gift');

                }else{

                    $marke['id'] = $id;
                    $marke['stores_id'] = $storeid;
                    $marke['status'] = 1;
                    $active_res = Db::table('tp_active')->field('id,username,sentiment,pic')->where($marke)->find();
                    $this->assign('activeres',$active_res);
                   // echo $uid;
                   // exit;

                    $this->assign('id',$id);
                    $this->assign('storeid',$storeid);
                    $this->assign('code',0);
                    $this->assign('uid',0);
                    return $this->fetch('active/gift');
                }


            }
            
        }else{
            //id:活动用户id  stores_id：门店的id
            $marke['id'] = $id;
            $marke['stores_id'] = $storeid;
            $marke['status'] = 1;

            $active = new ActiveModel();
       
            $active_res = $active->getActiveRes($marke);
            $this->assign('activeres',$active_res);
            

            $this->assign('id', $id);
            $this->assign('storeid', $storeid);

           

            if(session('uid') == 0){
                $this->assign('code',0);
                $this->assign('uid',0);
            }else{
                $this->assign('code',1);
                $this->assign('uid',session('uid'));
            }

          /*  if(session('uid') == null){
                $this->assign('code',0);
                $this->assign('uid',0);
            }else{
                $this->assign('code',1);
                $this->assign('uid',session('uid'));
            }
*/

    		return $this->fetch('active/gift');
        }
    }


    public function giftapi(){

        $id = input('post.id');
        $stores_id = input('post.stores_id');

        //id:活动用户id  stores_id：门店的id
        $marke['id'] = $id;
        $marke['stores_id'] = $stores_id;
        $marke['status'] = 1;

        $active = new ActiveModel();
       
        $active_res = $active->getActiveRes($marke);

        $marke_g['stores_id'] = $stores_id;
        $marke_g['status'] = 1;

        $goods_list = Db::name('goods')->field('id,goods_name,price,pic')->where($marke_g)->order('id asc')->select();

       /* $temp_goods = 0;
        $len = count($goods_list);
        for($i=0;$i<$len;$i++)
          { //该层循环用来控制每轮 冒出一个数 需要比较的次数
            for($k=0;$k<$len-$i;$k++)
            {
               if($goods_list[$k]['price'] > $goods_list['price'][$k+1])
                {
                    $temp_goods=$goods_list[$k+1];
                    $goods_list[$k+1]=$goods_list[$k];
                    $goods_list[$k]=$temp_goods;
                }
            }
          }*/

        if($goods_list !== null and $active_res !== null){
            return jsonp(array('code' => '0', 'success' => '信息详情页，调用成功！','goods_list' => $goods_list, 'active_res' => $active_res));
        }else{
            return jsonp(array('code' => '1', 'success' => '信息详情页，调用为空！','goods_list' => $goods_list, 'active_res' => $active_res));
        }

        //return $this->fetch('gift', ['goods_list' => $goods_list, 'active_res' => $active_res]);
    }

    public function rank(){

        $activeid = input('param.activeid');

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx1a977e6944e78282&secret=40af637ea814759753770a4e87228a15";
        //$ttoo_res = file_get_contents($url);

        //echo "====".$storesid."---".$url;
        //exit;





        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res_p = curl_exec($ch);
        curl_close($ch);

        $array_info = json_decode($res_p,true);






        //每天排行榜
        

        $time_new = strtotime(date("Y-m-d",time("Y-m-d")));

        $time_end = strtotime(date("Y-m-d",strtotime("+1 day")));

        $rank_res_info = Db::table('tp_ranklist_info')
                        ->field('user_id,sum(popularValue) as popularValueres,sendtime')
                        ->where(array('active_id'=>$activeid))
                        ->whereTime('sendtime', 'between', [$time_new, $time_end])
                        ->group('user_id')
                        ->select();
        

        foreach ($rank_res_info as $key => $value) {
            $user_res =  Db::table('tp_user')
                         ->field('openid,nickname,headimgurl')
                         ->where(array('id' => $value['user_id']))
                         ->find();

            if($user_res['nickname'] != null){
                $rank_res_info[$key]['nickname']   = $user_res['nickname'];
            }else{
                 $url_info2 = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$array_info['access_token']."&openid=".$user_res['openid']."&lang=zh_CN";

                // $url_info2 = "https://api.weixin.qq.com/sns/userinfo?access_token=".$array_info['access_token']."&openid=".$user_res['openid']."&lang=zh_CN";

               

                $ch12 = curl_init();
                curl_setopt($ch12, CURLOPT_URL, $url_info2);
                curl_setopt($ch12, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch12, CURLOPT_RETURNTRANSFER, 1);
                $res_p12 = curl_exec($ch12);
                curl_close($ch12);


                 //$res_p12 = https_request($url_info2);

                $array_info12 = json_decode($res_p12,true);

               // var_dump($array_info12);
               // exit;

                if(isset($array_info12['nickname'])){
                    $rank_res_info[$key]['nickname'] = $array_info12['nickname'];
                }else{
                    $rank_res_info[$key]['nickname'] = "####";
                }


       
                
    


                
            }
           // $rank_res_info[$key]['nickname'] = "";


           
            $rank_res_info[$key]['headimgurl'] = $user_res['headimgurl'];

        }

       

        

        $leninfo = count($rank_res_info);
          //设置一个空数组 用来接收冒出来的泡
          //该层循环控制 需要冒泡的轮数
        for($i=1;$i<$leninfo;$i++)
        { //该层循环用来控制每轮 冒出一个数 需要比较的次数
            for($k=0;$k<$leninfo-$i;$k++)
            {
               if($rank_res_info[$k]['popularValueres']<$rank_res_info[$k+1]['popularValueres'])
                {
                    $tmp=$rank_res_info[$k+1];
                    $rank_res_info[$k+1]=$rank_res_info[$k];
                    $rank_res_info[$k]=$tmp;
                }
            }
        }
        //end

        //累计排行榜;
        $ranklist_res = Db::table('tp_ranklist')
                       ->field('user_id,popularValue')
                       ->where(array('active_id'=>$activeid))
                       ->select();
        foreach ($ranklist_res as $key => $value) {
            $user_rank_res =  Db::table('tp_user')
                            ->field('openid,nickname,headimgurl')
                            ->where(array('id' => $value['user_id']))
                            ->find();

            if($user_rank_res['nickname'] != null){
                $ranklist_res[$key]['nickname']   = $user_rank_res['nickname'];
            }else{
                 $url_info = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$array_info['access_token']."&openid=".$user_rank_res['openid']."&lang=zh_CN";

                $ch1 = curl_init();
                curl_setopt($ch1, CURLOPT_URL, $url_info);
                curl_setopt($ch1, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
                $res_p1 = curl_exec($ch1);
                curl_close($ch1);

                $array_info1 = json_decode($res_p1,true);

                if(isset($array_info1['nickname'])){
                    $ranklist_res[$key]['nickname'] = $array_info1['nickname'];
                }else{
                    $ranklist_res[$key]['nickname'] = "####";
                }
            }

          //  $ranklist_res[$key]['nickname'] = "";

           
            $ranklist_res[$key]['headimgurl'] = $user_rank_res['headimgurl'];

        }
        $len = count($ranklist_res);
          //设置一个空数组 用来接收冒出来的泡
          //该层循环控制 需要冒泡的轮数
        for($i=1;$i<$len;$i++)
        { //该层循环用来控制每轮 冒出一个数 需要比较的次数
            for($k=0;$k<$len-$i;$k++)
            {
               if($ranklist_res[$k]['popularValue']<$ranklist_res[$k+1]['popularValue'])
                {
                    $tmp=$ranklist_res[$k+1];
                    $ranklist_res[$k+1]=$ranklist_res[$k];
                    $ranklist_res[$k]=$tmp;
                }
            }
        }

        

        //筛选出来前三名

        $ranklistres = array();

        if(count($ranklist_res)>3){
            
            $ranklist_top = array($ranklist_res[0],$ranklist_res[1],$ranklist_res[2]);

            for($i=3; $i <= (count($ranklist_res) - 1); $i++) { 
                $ranklistres[$i-3] = $ranklist_res[$i];
            }

        }else{
            //var_dump($ranklist_top);

            if(count($ranklist_res) == 0){
                $ranklist_res[0]['headimgurl'] = "http://tp.whjxry.net/uploads/images/zhan/zhan01.png";
                $ranklist_res[0]['nickname'] = "最强王者";
                $ranklist_res[0]['popularValue'] = "0";
            }

            if(count($ranklist_res) == 1){
                $ranklist_res[1]['headimgurl'] = "http://tp.whjxry.net/uploads/images/zhan/zhan02.png";
                $ranklist_res[1]['nickname'] = "至尊星耀";
                $ranklist_res[1]['popularValue'] = "0";
            }
           
            if(count($ranklist_res) == 2){
                $ranklist_res[2]['headimgurl'] = "http://tp.whjxry.net/uploads/images/zhan/zhan03.png";
                $ranklist_res[2]['nickname'] = "荣耀之神";
                $ranklist_res[2]['popularValue'] = "0";
            }

            $ranklist_top = array($ranklist_res[0],$ranklist_res[1],$ranklist_res[2]);
        }

      
        
        $this->assign('rankresinfo', $rank_res_info);

        $this->assign('ranklisttop', $ranklist_top);

        $this->assign('ranklistres', $ranklistres);

        return $this->fetch('rank');
    }

}