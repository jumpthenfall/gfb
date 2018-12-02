<?php

namespace app\admin\validate;
use think\Validate;

class AdministratorValidate extends Validate
{


    protected $rule = [
        'id'                 => 'require|number',
        'username|用户名'                 => 'unique:admin|require|length:1,255',
        'password'                 => 'length:6,24',
        'portrait'                 => 'length:1,255',
        'real_name'                 => 'require|length:2,255',
        'groupid'                 => 'require|number',
        'status'                    => 'number',
    ];
    protected $scene =[
        'add'      => ['username','password|require','portrait','real_name','group_id','status'],
    ];

}