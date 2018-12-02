<?php
namespace app\api\controller;

class Index extends Base
{
    public function index()
    {
        return json(array('tp' => '323223', 'mm' => '334434343'));
    }
}
