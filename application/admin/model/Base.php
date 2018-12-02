<?php
namespace app\admin\model;

use think\Model;

class Base extends Model
{
    public function fetchByWhere($where = null, $order = null, $page = null, $limits = null, $cols = null)
    {
        if (is_null($cols)) {
            $cols = '*';
        }

        $this->field($cols);
        if (is_string($where)) {
            $this->where($where);
        } elseif (is_array($where) && count($where)) {
            /**
             * 数组, 支持两种形式.
             */
            foreach ($where as $key => $val) {
                if (preg_match("/^[0-9]/", $key)) {
                    $this->where($val);
                } else {
                    $this->where($key, '=', $val);
                }
            }
        }

         if($order){
             $this->order($order);
         }
         if($page && $limits){
             $this->page($page,$limits);
         }
        return  $this->select();


       // if (is_string($where)) {
       //     $select->where($where);
       // } elseif (is_array($where) && count($where)) {
       //     /**
       //      * 数组, 支持两种形式.
       //      */
       //     foreach ($where as $key => $val) {
       //         if (preg_match("/^[0-9]/", $key)) {
       //             $select->where($val);
       //         } else {
       //             $select->where($key . '= ?', $val);
       //         }
       //     }
       // }

       // if ($order !== null) {
       //     $select->order($order);
       // }
       // if ($count !== null || $offset !== null) {
       //     $select->limit($count, $offset);
       // }
       // $rows = $select->query()->fetchAll();
//       return (is_array($rows) && count($rows)) ? $rows : null;
    }

    public function fetchByFV($field = null,$value = null)
    {
        $result = $this->where($field,'=',$value)->select();
        return $result ? $result : array();
    }

    public function  fetchValueByFV($field=null,$value=null,$col=null)
    {
        $res = $this->where($field,'=',$value)->value($col);
        return $res;
    }
}