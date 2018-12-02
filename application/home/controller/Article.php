<?php
namespace app\home\controller;
use app\home\model\ArticleModel;
use app\home\model\ArticleCommentModel;
use app\home\model\UserModel;
use think\Db;

class Article extends Base
{

    public function index(){

        if (session('uid') == 0 and isset($_GET['code'])){
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

              
                $this->assign('code',1);
                $this->assign('uid',$openid_res);

                //设置session uid
                session('uid', $openid_res);


                $key = input('key');
                $map = [];
                if($key&&$key!==""){
                    $map['title'] = ['like',"%" . $key . "%"];
                }
                //$map['status'] = 1;
                    
                $article = new ArticleModel();    
                //最新  
                $lists = $article->getAll($map);

                //热门  
                $listsRed = $article->getAllRed($map);
                $this->assign('val', $key);
                $this->assign('lists', $lists);
                $this->assign('listsred', $listsRed);


                
                return $this->fetch('article/index');
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

                  
                    $this->assign('code',1);
                    $this->assign('uid',$id_tp);


                    //设置session uid
                    session('uid', $id_tp);

                    $key = input('key');
                    $map = [];
                    if($key&&$key!==""){
                        $map['title'] = ['like',"%" . $key . "%"];
                    }
                    //$map['status'] = 1;
                        
                    $article = new ArticleModel();    
                    //最新  
                    $lists = $article->getAll($map);

                    //热门  
                    $listsRed = $article->getAllRed($map);
                    $this->assign('val', $key);
                    $this->assign('lists', $lists);
                    $this->assign('listsred', $listsRed);




                    return $this->fetch('article/index');

                }else{
                   
                    $this->assign('code',0);
                    $this->assign('uid',0);


                    $key = input('key');
                    $map = [];
                    if($key&&$key!==""){
                        $map['title'] = ['like',"%" . $key . "%"];
                    }
                    //$map['status'] = 1;
                        
                    $article = new ArticleModel();    
                    //最新  
                    $lists = $article->getAll($map);

                    //热门  
                    $listsRed = $article->getAllRed($map);
                    $this->assign('val', $key);
                    $this->assign('lists', $lists);
                    $this->assign('listsred', $listsRed);

                  
                    
                    return $this->fetch('article/index');
                }


            }
            
        }else{
            session('uid', 0);
        }

        $key = input('key');
        $map = [];
        if($key&&$key!==""){
            $map['title'] = ['like',"%" . $key . "%"];
        }
        //$map['status'] = 1;
            
        $article = new ArticleModel();    
        //最新  
        $lists = $article->getAll($map);

        //热门  
        $listsRed = $article->getAllRed($map);
        $this->assign('val', $key);
        $this->assign('lists', $lists);
        $this->assign('listsred', $listsRed);
        return $this->fetch();
    }

    public function detail(){

        

        $uid = input('param.uid');


        $id = input('param.id');
        
        $map = [];
        if($id&&$id!==""){
            $map['id'] = $id;          
        }
            
        $article = new ArticleModel();    
        $oneinfo = $article->getOne($map);
        $articlecomment = new ArticleCommentModel();

        $temp = [];
        $temp['article_id'] = $id;
        $temp['status']     = 1;
        $commentinfo = $articlecomment->getAll($temp);

        $this->assign('oneinfo', $oneinfo);
        $this->assign('uid', $uid);
        $this->assign('commentinfo', $commentinfo);
        return $this->fetch();
    }

    public function comment(){

        $article_id = input('param.article_id');

        $content    = input('param.content');
        $uid        = input('param.uid');
        $map = [];
        $map['article_id'] = $article_id;
        $map['content']    = $content;
        $map['from']       = '软文评论';
        $map['writer']     = 'core';
        $map['user_id']    = $uid;
        $map['create_time']    = time();
        $map['status'] = 1;
            
        $articlecomment = new ArticleCommentModel();
        $lists = $articlecomment->insertComment($map);
        if(isset($lists)){
            $article = new ArticleModel();
            $temp = [];
            $temp['id'] = $article_id;
            $oneres = $article->getOne($temp);

            if(isset($oneres)){
                $data = [];
                $data['id'] = $article_id;
                $data['comment_num'] = $oneres['comment_num'] + 1;
                $oneresinfo = $article->updateArticle($data);

               
                return jsonp($lists);
               

            }else{
                return jsonp($lists);
            }

        }else{
            return jsonp($lists);
        }
        
    }

}
