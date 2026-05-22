<?php
// 应用公共文件
use think\facade\Db;

function generateSecret($length = 32)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $secret = '';
    $max = strlen($chars) - 1;

    for ($i = 0; $i < $length; $i++) {
        $secret .= $chars[random_int(0, $max)];
    }

    return $secret;
}

function generateToken($userId)
{
    return md5($userId . time() . uniqid());
}

function getOrderSn()
{
    while (true) {
        $orderSn = 'F' . date('YmdHis') . rand(10, 99);
        if (!Db::name('order')->where('order_sn', $orderSn)->find()) {
            return $orderSn;
        }
    }
}

function makeCurlRequest($url, $data)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}