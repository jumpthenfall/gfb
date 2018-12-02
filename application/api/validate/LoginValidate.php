<?php

namespace app\api\validate;

use think\Validate;

class LoginValidate extends Validate
{
    protected $rule = [
        ['card_number|卡号', 'require|length:4,15'],
        ['password|密码', 'require|length:4,15'],
        ['phone_mac|设备号', 'require|length:16,128'],
    ];

    protected $scene=[
        'login'=>['card_number','password','phone_mac'],
    ];

}