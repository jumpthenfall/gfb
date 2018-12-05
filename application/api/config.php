<?php
//配置文件
return [
	'default_return_type'	=> 'json',
    //jwt token 参数
    'jwt_token'=>[

        'jwt_key'=>'707be756ab9121c5ada120b93e6bc7483b1282bf',//加密字符串
        'jwt_alg'=>'HS256',//默认加密方法
        'jwt_leeway'=>1440000,//token有效时长，单位：秒
        'playload'=>[
            'iss'=>'https://www.gfb.cn',// jwt签发者
            'aud'=>'https://www.gfb.cn',// 接收jwt的一方
//            'iat'=> time(),// jwt的签发时间
        ]
    ],
    //不需要登录验证的接口
    'unauthority_area'=>[
        'index/index',//首页
        'login/login',//登录接口
        'admin_login',//管理员登录接口
        'shell/manage_daily_data',//脚本
    ],
];