<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Common\Account as Acc;

class Message extends Model
{
    protected $table = 'message';

    public static function get_list($condition, $order_field = 'create_time', $order = 'desc', $fields = '*', $start = 0, $page_index = 10)
    {
        return self::where($condition)->orderBy($order_field, $order)->offset($start)->limit($page_index)->get($fields)->toArray();
    }

    public static function add($data)
    {
        $data['create_time'] = time();
        return self::insertGetId($data);
    }
}
