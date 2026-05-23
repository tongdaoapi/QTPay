<?php

namespace app\controller;

use app\BaseController;
use app\service\SandPayService;
use think\facade\Db;

class Order extends BaseController
{

    protected $notNeedToken = ['test', 'createOrder', 'queryOrder', 'notify', 'createNotify', 'getOrder'];


    public function test()
    {
        $data = [
            'appid' => '1',
            'amount' => '100',
            'productId' => '1',
            'orderSn' => time(),
            'notifyUrl' => 'asd',
            'returnUrl' => 'asd',
            'ip' => '123'
        ];
        $res = $this->curlPost('http://localhost:8848/api/order/createOrder', $data);
        return json(json_decode($res, true));
    }

    function curlPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function createOrder()
    {
        $params = input('post.');
        if (!isset($params['appid']) || !$params['appid']) $this->apiError('appid 不能为空', 500);
        if (!isset($params['amount']) || !$params['amount']) $this->apiError('amount 不能为空', 500);
        if (!isset($params['productId']) || !$params['productId']) $this->apiError('productId 不能为空', 500);
        if (!isset($params['orderSn']) || !$params['orderSn']) $this->apiError('orderSn 不能为空', 500);
        if (!isset($params['notifyUrl']) || !$params['notifyUrl']) $this->apiError('notifyUrl 不能为空', 500);
        if (!isset($params['returnUrl']) || !$params['returnUrl']) $this->apiError('returnUrl 不能为空', 500);
        if (!isset($params['ip']) || !$params['ip']) $this->apiError('ip 不能为空', 500);
        if (!isset($params['sign']) || !$params['sign']) $this->apiError('sign 不能为空', 500);
        $merchant = Db::name('merchant')->where('appid', $params['appid'])->find();
        if (!$merchant) $this->apiError('appid 填写错误', 500);
        if ($params['sign'] != $this->getSign($params, $merchant['secret'])) $this->apiError('sign 填写错误');
        $merchantPaymentProduct = Db::name('merchant_payment_product')->where('id', $params['productId'])->find();
        if (!$merchantPaymentProduct) $this->apiError('productId 填写错误', 500);
        $paymentProduct = Db::name('payment_product')->where('id', $merchantPaymentProduct['payment_product_id'])->find();
        if (!$paymentProduct) $this->apiError('productId 填写错误', 500);
        if (!$paymentProduct['status']) $this->apiError('productId 产品已关闭', 500);
        if ($params['amount'] > $paymentProduct['max_amount'] || $params['amount'] < $paymentProduct['min_amount']) $this->apiError('amount 超过最大值或者低于最小值', 500);
        $order = Db::name('order')->where('merchant_order_sn', $params['orderSn'])->where('merchant_id', $merchant['id'])->find();
        if ($order) $this->apiError('orderSn 订单号已存在', 500);
        $orderSn = getOrderSn();
        Db::name('order')->insert([
            'order_sn' => $orderSn,
            'merchant_order_sn' => $params['orderSn'],
            'created_at' => date('Y-m-d H:i:s'),
            'amount' => $params['amount'],
            'fee' => $merchantPaymentProduct['rate'] / 100 * $params['amount'],
            'price' => $params['amount'] - $merchantPaymentProduct['rate'] / 100 * $params['amount'],
            'merchant_id' => $merchant['id'],
            'product_id' => $params['productId'],
            'notify_url' => $params['notifyUrl'],
            'return_url' => $params['returnUrl'],
            'ip' => $params['ip'],
        ]);
        switch ($paymentProduct['id']) {
            case 1:
                $config = [
                    'amount' => $params['amount'],
                    'returnUrl' => $params['returnUrl'],
                    'notifyUrl' => 'https://zsmxnn.fs620.com/api/order/notify',
                    'ip' => $params['ip'],
                    'orderSn' => $orderSn
                ];
                $sandPayService = new SandPayService();
                $url = $sandPayService->createOrder($config);
                $this->success([
                    'url' => $url
                ]);
                break;
        }
    }

    public function queryOrder()
    {
        $params = input('post.');
        if (!isset($params['appid']) || !$params['appid']) $this->apiError('appid 不能为空', 500);
        if (!isset($params['orderSn']) || !$params['orderSn']) $this->apiError('orderSn 不能为空', 500);
        if (!isset($params['sign']) || !$params['sign']) $this->apiError('sign 不能为空', 500);
        $merchant = Db::name('merchant')->where('appid', $params['appid'])->find();
        if (!$merchant) $this->apiError('appid 填写错误', 500);
        $order = Db::name('order')->field(['amount', 'merchant_order_sn', 'order_sn', 'product_id', 'pay_status', 'pay_at', 'callback_status', 'callback_at'])->where('merchant_order_sn = "' . $params['orderSn'] . '" or order_sn = "' . $params['orderSn'] . '"')->where('merchant_id', $merchant['id'])->find();
        $order['appid'] = $merchant['appid'];
        $order['sign'] = $this->getSign($order, $merchant['secret']);
        if (!$order) $this->apiError('orderSn 订单号不存在', 500);
        $this->success([
            'order' => $order
        ]);
    }

    public function getSign($arr, $secret)
    {
        unset($arr['sign']);
        ksort($arr, SORT_STRING);
        $beforeSignString = urldecode(http_build_query($arr, '', '&')) . '&secret=' . $secret;
        $signString = strtoupper(md5($beforeSignString));
        return $signString;
    }

    public function notify()
    {
        $data = input('post.');
        $data['bizData'] = json_decode($data['bizData'], true);
        $orderSn = $data['bizData']['outOrderNo'];
        $orderStatus = $data['bizData']['orderStatus'];
        $order = Db::name('order')->where('order_sn', $orderSn)->find();
        if ($order && !$order['pay_status'] && $orderStatus == 'success') {
            Db::name('order')->where('id', $order['id'])->update([
                'pay_status' => 1,
                'pay_at' => date('Y-m-d H:i:s'),
                'notify_log' => json_encode($data)
            ]);
            Db::name('notify')->insert([
                'order_id' => $order['id'],
                'last_notify_time' => date('Y-m-d H:i:s'),
            ]);
        }
        die('success');
    }

    public function createNotify()
    {
        $orderSn = input('orderSn');
        if (!$orderSn) $this->error('请输入订单号');
        $order = Db::name('order')->where('order_sn = "' . $orderSn . '" or merchant_order_sn = "' . $orderSn . '"')->find();
        if (!$order) $this->error('订单不存在');
        $notify = Db::name('notify')->where('order_id', $order['id'])->find();
        if ($notify) {
            $this->error('回调正在执行，请勿重复创建');
        } else {
            Db::name('notify')->insert([
                'order_id' => $order['id'],
                'last_notify_time' => date('Y-m-d H:i:s'),
            ]);
            $this->success($orderSn . '回调创建成功');
        }
    }

    public function getOrder()
    {
        $orderSn = input('orderSn');
        if (!$orderSn) $this->error('请输入订单号');
        $order = Db::name('order')->where('order_sn = "' . $orderSn . '" or merchant_order_sn = "' . $orderSn . '"')->find();
        if (!$order) $this->error('订单不存在');
        $this->success('查询成功', [
            'order' => $order,
            'notifyList' => Db::name('notify')->where('order_id', $order['id'])->select()
        ]);
    }

}
