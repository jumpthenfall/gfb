<?php

namespace app\home\controller;
use app\home\model\BaseModel;
use think\Db;

/**
 * description: 微信公众号支付回调
 * Class Notify
 * User: zjc
 * email: zengjc08@163.com
 * @package app\home\controller
 */
class Notify extends Base
{
    /**
     * description: 回调入口函数
     * User: zjc
     * email: zengjc08@163.com
     */
    public  function wxpay()
    {
        $postStr = file_get_contents("php://input");
        libxml_disable_entity_loader(true);
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if((string)$postObj->return_code == "SUCCESS"){
            $dir = './data/wxnotify/'.date('Ymd') . '/';
            if(!is_dir($dir)){
                mkdir($dir,0777,true);
            }
            //通过微信支付返回的openid来修改数据库数据
            $open_id = (string) $postObj->openid;
            //查询数据库是否有该用户信息
            $exist = Db::name('way_customer')->where('wc_openid', '=',$open_id)->find();
            if($exist){//存在更新优惠券状态为已购买
               $res =  Db::name('way_customer')->where('wc_openid','=',$open_id)->setField('wc_type',2);
               if(! $res){
                   file_put_contents($dir . "updateerror.log",json_encode($exist['wc_openid'] . "||||"),FILE_APPEND);
               }
               //给推荐商家账号增加奖励 30 元每人
                $shop_res = Db::name('way_shop')->where('id',$exist['wc_ws_id'])->setInc('ws_wayTotal',30);
               if(!$shop_res){
                   file_put_contents($dir . "recorderror.log",json_encode($exist['wc_openid'] . "||||"),FILE_APPEND);
               }

            }else{
                file_put_contents('./data/log/fail.txt',json_encode($postObj->return_code),FILE_APPEND);
                //不存在则将该订单放入日志文件

                $file = $dir . $open_id . '.log';
                file_put_contents($file,json_encode($postObj),FILE_APPEND);
            }
        }else{
            $dir = './data/wxnotify/'.date('Ymd') . '/';
            if(!is_dir($dir)){
                mkdir($dir,0777,true);
            }
            $file = $dir  . 'error.log';
            file_put_contents($file,json_encode($postObj),FILE_APPEND);
        }

    }

    /**
     * description: 成功返回字符串
     * User: zjc
     * email: zengjc08@163.com
     */
    public function  returnSuccess()
    {
        $str = <<<ETO
<xml>
  <return_code><![CDATA[SUCCESS]]></return_code>
  <return_msg><![CDATA[OK]]></return_msg>
</xml>
ETO;
    echo $str;
    }
}