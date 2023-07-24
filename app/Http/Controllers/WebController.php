<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * 获取留言列表
     * page int 当前页
     * pageIndex int 每页显示条数
     * orderField str 排序字段
     * order str asc升序 desc倒序
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList(Request $request)
    {
        $message = [
            'page.integer' => '当前页必须是整数值',
            'pageIndex.integer' => '分页数必须是整数值',
            'orderField.in' => '排序字段只支持创建时间',
            'order.in' => '必须是desc或者asc'
        ];
        $validator = Validator::make($request->all(), [
            'page' => 'integer',
            'pageIndex'  => 'integer',
            'orderField' => 'in:create_time',
            'order' => 'in:asc,desc'
        ], $message);
        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $error) {
                return self::fail_response($error[0]);
            }
        }

        $page = $request->input('page');
        $order = $request->input('order');
        $pageIndex = $request->input('pageIndex');
        $orderField = $request->input('orderField');

        $page = $page ? intval($page) : 1;
        $order = $order ? strval($order) : 'desc';
        $pageIndex = $pageIndex ? intval($pageIndex) : 10;
        $orderField = $orderField ? strval($orderField) : 'create_time';

        $message = new Message();
        $condition = [['status', '=', 1]];
        $start = ($page - 1) * $pageIndex;
        $list = $message->get_list($condition, $orderField, $order, '*', $start, $pageIndex);
        return self::success_response('Success', ['list' => $list]);
    }

    /**
     * 留言
     * content str 留言内容
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addMsg(Request $request)
    {
        $message = [
            'content.required' => '请填写内容'
        ];
        $validator = Validator::make($request->all(), [
            'content' => 'required'
        ], $message);
        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $error) {
                return self::fail_response($error[0]);
            }
        }

        $content = $request->input('content');

        // 验证是否包含违法词
        $check = $this->checkWord($content);
        $content = $check['count'] > 0 ? $check['stringAfter'] : strval($content);

        $message = new Message();
        $data['status'] = 1;
        $data['content'] = $content;
        $id = $message->add($data);
        if ($id) {
            return self::success_response('Success', []);
        }
        return self::fail_response('数据插入失败');
    }

    public function delMsg(Request $request)
    {
        $msgIds = $request->route('msg_ids');
        $msgIds = explode(',', $msgIds);

        // 数据验证
        $message = new Message();
        $condition = [
            ['status', '=', 1]
        ];
        $count = $message->countIn($condition, $msgIds);
        if ($count != count($msgIds)) {
            return self::fail_response('数据错误');
        }

        // 假删除
        $result = $message->changeIn($condition, $msgIds, ['status' => 0]);
        if ($result) {
            return self::success_response('Success', []);
        }
        return self::fail_response('删除失败');
    }

    /**
     * 获取天气信息
     * content str 留言内容
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWeather(Request $request)
    {
        $message = [
            'locationInfo.required' => '参数错误'
        ];
        $validator = Validator::make($request->all(), [
            'locationInfo' => 'required'
        ], $message);
        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $error) {
                return self::fail_response($error[0]);
            }
        }

        $locationInfo = $request->input('locationInfo');
        $locationInfo = $locationInfo ? json_decode($locationInfo, true) : [];
        if (!$locationInfo) {
            return self::fail_response('获取失败');
        }

        $weather = $this->weather($locationInfo);

        if ($weather) {
            return self::success_response('Success', $weather);
        }

        return self::fail_response('获取失败');
    }
}
