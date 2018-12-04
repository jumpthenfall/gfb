<?php

namespace app\api\validate;

use think\Validate;

class UserValidate extends Validate
{
    protected $rule = [
        ['mobile|手机号', 'require|number|length:11'],
        ['ali_account|支付宝账号', 'require|length:4,64'],
        ['ali_nickname|支付宝用户名', 'require|length:1,32'],
        ['content|反馈内容', 'require|length:1,300'],
        ['card_id|登录卡ID', 'require|number'],
        ['score|登录卡ID', 'number|between:1,5'],
        ['contact|联系方式', 'length:3,64'],
        ['phone_brand|手机品牌', 'require|length:2,64'],
        ['phone_version|手机型号', 'require|length:2,64'],

    ];

    protected $scene=[
        'update'=>['card_id','mobile','ali_account','ali_nickname','phone_brand','phone_version'],
        'feedback'=>['content','card_id','contact','score'],
    ];

}