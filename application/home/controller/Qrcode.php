<?php
namespace app\home\controller;

use think\Controller;
use think\Db;
use com\Qrcode;

class Qrcode extends Base
{
	public function qrcode()
    {
        $storeId = input('storeId');
        $referees = input('referees');

        $value = "https://www.whfhnd.cn/hykjpt/drink?storeId=".$storeId."&referees=".$referees; //二维码内容
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 6;//生成图片大小

        
        //生成二维码图片
        QRcode::png($value, './uploads/qrcode/twocode/qrcode.png', $errorCorrectionLevel, $matrixPointSize, 2);
        $logo = './uploads/qrcode/logo.png';//准备好的logo图片
        $QR = './uploads/qrcode/twocode/qrcode.png';//已经生成的原始二维码图



        if ($logo !== FALSE) {
         
        
            $QR = ImageCreateFromString(file_get_contents($QR));
            $logo = ImageCreateFromString(file_get_contents($logo));
 
            $QR_width = imagesx($QR);//二维码图片宽度
            $QR_height = imagesy($QR);//二维码图片高度
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度

            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
            $logo_qr_height, $logo_width, $logo_height);
        }
        
        //输出图片
        imagepng($QR, './uploads/qrcode/twocodelogo/helloweixin.png');
        $res="./uploads/qrcode/twocodelogo/helloweixin.png";



        return json(['code' => 1, 'data' => $res, 'msg' => '门店渠道二维码生成成功!!']);
    }
}