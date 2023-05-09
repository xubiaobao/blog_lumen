<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Redis;

class Controller extends BaseController
{
    public function weather()
    {
        // 腾讯定位服务
        $url = 'https://apis.map.qq.com/ws/location/v1/ip';
        // 服务密钥
        $key = 'UIJBZ-LKUK7-JHYXB-PHF7H-FUJLK-3HFIO';
        // 请求密钥
        $secret = '5YKQmpk2L9H4vyFN1UqkRxmVadE2wD';
        // 生成请求标识
        $url = $url . '?key=' . $key;
        $sig = md5('/ws/location/v1/ip?key=' . $key . $secret);
        // 请求定位信息
        $locationInfo = file_get_contents($url . '&sig=' . $sig);
        $locationInfo = json_decode($locationInfo, true);
        if ($locationInfo['status'] === 0) {
            $data['city'] = $locationInfo['result']['ad_info']['city'];
            $data['adcode'] = $locationInfo['result']['ad_info']['adcode'];
            // 获取城市编码
            $weatherKey = '06ea91a7abc14780b8b70fd701d1bf5d'; // 和风天气密钥
            $cirtUrl = 'https://geoapi.qweather.com/v2/city/lookup'; // 城市查找
            $cityInfo = file_get_contents($cirtUrl . '?location=' . $data['adcode'] . '&key=' . $weatherKey);
            $cityInfo = json_decode(gzdecode($cityInfo), true);
            if ($cityInfo['code'] != 200) {
                return json_encode(['code' => 0, 'data' => []]);
            }
            $cityId = $cityInfo['location'][0]['id'];
            $data['cityId'] = $cityId;
            // 获取缓存
            $cache = Redis::get('weather_'.$cityId);
            if(!is_null($cache)){
                return json_encode(['code' => 1, 'data' => json_decode($cache,true)]);
            }
            // 获取天气
            $weatherUrl = 'https://devapi.qweather.com/v7/weather/now?location=' . $data['cityId'] . '&key=' . $weatherKey;
            $weatherInfo = file_get_contents($weatherUrl);
            $weatherInfo = json_decode(gzdecode($weatherInfo), true);
            if ($weatherInfo['code'] != 200) {
                return json_encode(['code' => 0, 'data' => []]);
            }
            $data['temp'] = $weatherInfo['now']['temp'];
            $data['text'] = $weatherInfo['now']['text'];
            $data['windDir'] = $weatherInfo['now']['windDir'];
            $data['windScale'] = $weatherInfo['now']['windScale'];
            Redis::setex('weather_'.$cityId, 3600, json_encode($data));
            return json_encode(['code' => 1, 'data' => $data]);
        }
        return json_encode(['code' => 0, 'data' => []]);
    }
}
