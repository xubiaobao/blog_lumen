<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redis;

class Controller extends BaseController
{
    protected static function response($error_code = '', $msg = '', $data = [])
    {
        $response = ['code' => $error_code, 'msg' => $msg, 'data' => $data];
        return response()->json($response);
    }

    protected static function success_response($msg = '', $data = [])
    {
        return self::response(SUCCESS, $msg, $data);
    }

    protected static function fail_response($msg = '', $data = [])
    {
        return self::response(FAILED, $msg, $data);
    }

    protected static function unauthorized()
    {
        exit(response()->json(['error' => 'Unauthorized'], 401));
    }

    /**
     * 根据ip地址获取当地天气
     */
    public function weather($locationInfo)
    {
        if (!$locationInfo) {
            // 腾讯定位服务
            $url = 'https://apis.map.qq.com/ws/location/v1/ip';
            // 服务密钥
            $key = 'UIJBZ-LKUK7-JHYXB-PHF7H-FUJLK-3HFIO';
            // 请求密钥
            $secret = '5YKQmpk2L9H4vyFN1UqkRxmVadE2wD';
            // 生成请求标识
            $url = $url . '?key=' . $key;
            $sig = md5('/ws/location/v1/ip?key=' . $key . $secret);
            // 生成请求地址
            $sendUrl = $url . '&sig=' . $sig;
            // 请求定位信息
            $locationInfo = file_get_contents($sendUrl);
            $locationInfo = json_decode($locationInfo, true);
        }

        if ($locationInfo['result']) {
            $data['city'] = $locationInfo['result']['ad_info']['city'];
            $data['adcode'] = $locationInfo['result']['ad_info']['adcode'];
            // 获取缓存
            $cache = Redis::get('weather_' . $data['adcode']);
            if (!is_null($cache)) {
                return json_decode($cache, true);
            }
            // 获取城市编码
            $weatherKey = '06ea91a7abc14780b8b70fd701d1bf5d'; // 和风天气密钥
            $cirtUrl = 'https://geoapi.qweather.com/v2/city/lookup'; // 城市查找
            $cityInfo = file_get_contents($cirtUrl . '?location=' . $data['adcode'] . '&key=' . $weatherKey);
            $cityInfo = json_decode(gzdecode($cityInfo), true);
            if ($cityInfo['code'] != 200) {
                return [];
            }
            $cityId = $cityInfo['location'][0]['id'];
            $data['cityId'] = $cityId;
            // 获取天气
            $weatherUrl = 'https://devapi.qweather.com/v7/weather/now?location=' . $data['cityId'] . '&key=' . $weatherKey;
            $weatherInfo = file_get_contents($weatherUrl);
            $weatherInfo = json_decode(gzdecode($weatherInfo), true);
            if ($weatherInfo['code'] != 200) {
                return [];
            }
            $data['temp'] = $weatherInfo['now']['temp'];
            $data['text'] = $weatherInfo['now']['text'];
            $data['windDir'] = $weatherInfo['now']['windDir'];
            $data['windScale'] = $weatherInfo['now']['windScale'];
            Redis::setex('weather_' . $data['adcode'], 3600, json_encode($data));
            return $data;
        }
        return [];
    }

    /**
     * @param $comment
     * @return array
     * 违法词过滤
     */
    public function checkWord($comment)
    {
        $list = config('errorWord');
        $count = 0; //违规词的个数
        $sensitiveWord = ''; //违规词
        $stringAfter = $comment; //替换后的内容
        $pattern = "/" . implode("|", $list) . "/i"; //定义正则表达式
        //匹配到了结果
        if (preg_match_all($pattern, $comment, $matches)) {
            //匹配到的数组
            $patternList = $matches[0];
            $count = count($patternList);
            //敏感词数组转字符串
            $sensitiveWord = implode(',', $patternList);
            //把匹配到的数组进行合并，替换使用
            $replaceArray = array_combine($patternList, array_fill(0, count($patternList), '*'));
            $stringAfter = strtr($comment, $replaceArray); //结果替换
        }
        return ['count' => $count, 'sensitiveWord' => $sensitiveWord, 'stringAfter' => $stringAfter];
    }
}
