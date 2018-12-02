<?php

namespace app\admin\controller;
use think\Controller;
use think\File;
use think\Request;

class Upload extends Base
{
	//图片上传
    public function upload(){
       $file = request()->file('file');
       $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/images');
       if($info){
            echo $info->getSaveName();
        }else{
            echo $file->getError();
        }
    }
	
	//分佣广告图片上传
    public function uploadAds(){
       $file = request()->file('file');
       $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/ads');
       if($info){
            echo $info->getSaveName();
        }else{
            echo $file->getError();
        }
    }

    //会员头像上传
    public function uploadface(){
       $file = request()->file('file');
       $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/face');
       if($info){
            echo $info->getSaveName();
        }else{
            echo $file->getError();
        }
    }

    //图片上传
    public function uploadactive(){
       $file = request()->file('file');
       $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/active');
       if($info){
            echo $info->getSaveName();
        }else{
            echo $file->getError();
        }
    }

    //图片上传
    public function uploadmerchant(){
       $file = request()->file('file');
       $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/merchant');
       if($info){
            echo $info->getSaveName();
        }else{
            echo $file->getError();
        }
    }

    //上传商品缩略图
    public function uploadgoodsthumbnail()
    {
        $file = request()->file('file');
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/goodsthumbnail');
        if($info){
            return DS . 'uploads/goodsthumbnail' . DS . $info->getSaveName();
        }else{
            return  $file->getError();
        }
    }
    //上传广告图
    public function ad()
    {
        $file = request()->file('file');
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/ad');
        if($info){
            return DS . 'uploads/ad' . DS . $info->getSaveName();
        }else{
            return  $file->getError();
        }
    }




}