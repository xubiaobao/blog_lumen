<?php
if (!function_exists('get_rand')) {
    function get_rand($length = 8, $lower = TRUE, $upper = TRUE, $number = TRUE, $ext = '')
    {
        //字符组合
        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $number = '0123456789';
        $str = '';
        if ($lower) {
            $str .= $lower;
        }
        if ($upper) {
            $str .= $upper;
        }
        if ($number) {
            $str .= $number;
        }
        if ($ext) {
            $str .= $ext;
        }
        $len = strlen($str) - 1;
        $randstr = '';
        for ($i = 0; $i < $length; $i++) {
            $num = mt_rand(0, $len);
            $randstr .= $str[$num];
        }
        return $randstr;
    }
}

if (!function_exists('get_avatar_url')) {
    function get_avatar_url($filename)
    {
        return MEDIA_URL . $filename;
    }
}