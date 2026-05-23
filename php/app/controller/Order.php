<?php

namespace app\controller;

use app\BaseController;
use app\service\SandPayService;
use think\facade\Db;

class Order extends BaseController
{

    protected $notNeedToken = ['test', 'createOrder', 'queryOrder', 'notify'];


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
        Db::name('order')->insert([
            'order_sn' => getOrderSn(),
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
        $rawInput = file_get_contents('php://input');
//        $rawInput = 'respDesc=%E6%88%90%E5%8A%9F&respTime=2026-05-23+11%3A25%3A42&sign=TNqDJHKOD0jkh%2BHaMboDvmDUAz5Y52BY1UmlhBzkgm42SuL9WhsT20sYmV44709EkBawm79zrFujfS19eEUQs8f0uJNzwrbAlBXKSPFOeBsG41LWdEgLDPDcrD789xUOzflIfXWMbsHC5ncuOKOnGVeedShabJhUPGOILhrKNQdNxRN6WZuK4OLY%2Bz%2BV4A5oNEjxwYYJH5ArgskBjImBnKyeKlV0MMHCESKL3wcKdBYYYGG6zFSjXVYJTCdo4wwAoHSSybjWN0dmZ4g6P7Fa3UG%2BXBtSylb83JYLCIna430xUz3gVHjXEBktTp%2FPx5JrwoB74ZC0z5hUWFZdRSduNA%3D%3D&signType=RSA&accessMid=6888805129054&bizData=%7B%22marketProduct%22%3A%22QZF%22%2C%22amount%22%3A%221.00%22%2C%22payMode%22%3A%22SANDH5%22%2C%22plFeeAmt%22%3A%220.00%22%2C%22mid%22%3A%226888805129054%22%2C%22orderStatus%22%3A%22success%22%2C%22sandSerialNo%22%3A%22260523093505192000007492%22%2C%22channelOrderNo%22%3A%22052326j200000503%22%2C%22outRespTime%22%3A%222026-05-23+11%3A25%3A42%22%2C%22eventType%22%3A%22recv%22%2C%22outOrderNo%22%3A%22F2026052311241473%22%2C%22payer%22%3A%7B%22payerLogonNo%22%3A%22621691******1414%22%2C%22dcFlag%22%3A%221%22%2C%22payerAccNo%22%3A%22621691******1414%22%2C%22payerAccType%22%3A%22CUP%22%2C%22signNo%22%3A%22SDSMP00688880512905420260523104913750870%22%7D%2C%22feeAmt%22%3A%220.10%22%2C%22extraFeeAmt%22%3A%220.00%22%2C%22settleInfo%22%3A%7B%22mhtAccDate%22%3A%2220260523%22%2C%22expSettleAmt%22%3A%221.00%22%7D%2C%22resultStatus%22%3A%22success%22%2C%22payType%22%3A%22FASTPAY%22%2C%22discountInfo%22%3A%7B%22issuerSubsidyAmt%22%3A%220.00%22%2C%22activityNo%22%3A%22%22%2C%22channelSubsidyAmt%22%3A%220.00%22%2C%22sandSubsidyAmt%22%3A%220.00%22%7D%2C%22channelFinishTime%22%3A%2220260523112542%22%2C%22buyerPayAmt%22%3A%221.00%22%2C%22holidayFeeAmt%22%3A%220.00%22%2C%22channelSerialNo%22%3A%22%22%2C%22reqReserved%22%3A%7B%22reqMemo%22%3A%22%22%7D%2C%22finishedTime%22%3A%2220260523112541%22%7D&version=4.0.0&respCode=success';
        $orderSn = $rawInput['bizData']['outOrderNo'];
        $orderStatus = $rawInput['bizData']['orderStatus'];
        $order = Db::name('order')->where('orderSn', $orderSn)->find();
        if ($order && !$order['pay_status'] && $orderStatus == 'success') {
            Db::name('order')->where('id', $order['id'])->update([
                'pay_status' => 1,
                'pay_at' => date('Y-m-d H:i:s'),
            ]);
            Db::name('notify')->insert([
                'order_id' => $order['id'],
                'last_notify_time' => date('Y-m-d H:i:s'),
            ]);
        }
        file_put_contents('1.txt', $rawInput);
        file_put_contents('2.txt', json_encode(input('post.')));
        die('success');
    }

}
