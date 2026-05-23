<?php

namespace app\service;

use think\facade\Db;
use think\facade\Cache;


class SandPayService
{

    private $apiUrl;
    public $privateKeyPath;
    public $pfxPassword;
    public $publicKeyPath;

    public $mid = '6888805129054';

    public function __construct()
    {
        $rootPath = app()->getRootPath() . 'public/pay/sandpay/';
        $this->apiUrl = 'https://openapi.sandpay.com.cn/v4/sd-receipts/api/trans/trans.order.create';
        $this->privateKeyPath = $rootPath . 'private_key.pfx';
        $this->publicKeyPath = $rootPath . 'sand_prod.cer';
        $this->pfxPassword = '123456';
    }

    public function createOrder($params = [])
    {
        $publicData = [
            "accessMid" => $this->mid,
            "timestamp" => date('Y-m-d H:i:s'),
            "version" => "4.0.0",
            "signType" => "RSA",
            "sign" => "",
            "encryptType" => "AES",
            "encryptKey" => "",
            "bizData" => ""
        ];
        $options = [
            'outReqTime' => date('YmdHis'),
            'amount' => $params['amount'],
            'payMode' => 'SANDH5',
            'goodsClass' => 99,
            'mid' => $this->mid,
            'description' => '订单充值',
            'outOrderNo' => $params['orderSn'],
            'payType' => 'FASTPAY',
            'payerInfo' => [
                'frontUrl' => $params['returnUrl'],
                'userId' => '1'
            ],
            'notifyUrl' => $params['notifyUrl'],
            'riskmgtInfo' => [
                'sourceIp' => $params['ip']
            ]
        ];
        $aesKey = $this->aes_generate(16);
        $publicData['bizData'] = $this->aesEncrypt($options, $aesKey);
        $publicData['encryptKey'] = $this->rsaEncryptByPub($aesKey, $this->loadX509Cert($this->publicKeyPath));
        $publicData['sign'] = $this->sign($publicData['bizData'], $this->loadPk12Cert($this->privateKeyPath, $this->pfxPassword));
        $result = $this->http_post_json($this->apiUrl, $publicData);
        $result = json_decode($result, true);
        $verify = $this->verify($result['bizData'], $result['sign'], $this->loadX509Cert($this->publicKeyPath));
        $decryptAESKey = $this->rsaDecryptByPri($result['encryptKey'], $this->loadPk12Cert($this->privateKeyPath, $this->pfxPassword));
        $result = json_decode($this->aesDecrypt($result['bizData'], $decryptAESKey), true);
        return $result['credential']['cashierUrl'];
    }

    function sign($plainText, $path)
    {
        try {
            $resource = openssl_pkey_get_private($path);
            $result = openssl_sign($plainText, $sign, $resource, OPENSSL_ALGO_SHA256);
            if (!$result) {
                throw new \Exception('签名出错' . $plainText);
            }
            return base64_encode($sign);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    function aes_generate($size)
    {
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $arr = array();
        for ($i = 0; $i < $size; $i++) {
            $arr[] = $str[mt_rand(0, 61)];
        }
        return implode('', $arr);
    }

    function aesEncrypt($plainText, $key)
    {
        ksort($plainText);
        $plainText = json_encode($plainText, JSON_UNESCAPED_UNICODE);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-ECB");
        $iv = !$ivlen ? "" : openssl_random_pseudo_bytes($ivlen);
        $result = openssl_encrypt($plainText, 'AES-128-ECB', $key, OPENSSL_RAW_DATA, $iv);
        if (!$result) {
            throw new \Exception('报文加密错误');
        }
        return base64_encode($result);
    }

    function rsaEncryptByPub($plainText, $puk)
    {
        if (!openssl_public_encrypt($plainText, $cipherText, $puk, OPENSSL_PKCS1_PADDING)) {
            throw new \Exception('AESKey 加密错误');
        }
        return base64_encode($cipherText);
    }

    function loadX509Cert($path)
    {
        $certContent = file_get_contents($path);
        if ($certContent === false) {
            throw new \Exception("读取证书文件失败: {$path}");
        }
        if (strpos($certContent, '-----BEGIN CERTIFICATE-----') === false) {
            $certContent = "-----BEGIN CERTIFICATE-----\n"
                . chunk_split(base64_encode($certContent), 64, "\n")
                . "-----END CERTIFICATE-----\n";
        }
        $cert = openssl_x509_read($certContent);
        if ($cert === false) {
            throw new \Exception("解析证书失败");
        }
        $publicKey = openssl_pkey_get_public($cert);
        if ($publicKey === false) {
            throw new \Exception("获取公钥失败");
        }
        $detail = openssl_pkey_get_details($publicKey);
        if (!$detail || !isset($detail['key'])) {
            throw new \Exception("提取公钥失败");
        }
        return $detail['key'];
    }

    function http_post_json($url, $param)
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        if ($param['encryptType'] == 'NONE') {
            $param['bizData'] = json_decode($param['bizData']);
        }
        $param = json_encode($param, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $data = curl_exec($ch);//运行curl
            curl_close($ch);
            if (!$data) {
                throw new \Exception('请求出错');
            }
            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    function loadPk12Cert($path, $pwd)
    {
        try {
            $file = file_get_contents($path);
            if (!$file) {
                throw new \Exception('loadPk12Cert::file_get_contents');
            }
            if (!openssl_pkcs12_read($file, $cert, $pwd)) {
                throw new \Exception('loadPk12Cert::openssl_pkcs12_read ERROR');
            }
            return $cert['pkey'];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    function verify($plainText, $sign, $path)
    {
        $resource = openssl_pkey_get_public($path);
        $result = openssl_verify($plainText, base64_decode($sign), $resource, 'SHA256');
        if (!$result) {
            throw new \Exception('签名验证未通过,plainText:' . $plainText . '。sign:' . $sign, '02002');
        }
        return $result;
    }

    function rsaDecryptByPri($cipherText, $prk)
    {
        if (!openssl_private_decrypt(base64_decode($cipherText), $plainText, $prk, OPENSSL_PKCS1_PADDING)) {
            throw new \Exception('AESKey 解密错误');
        }
        return (string)$plainText;
    }

    function aesDecrypt($cipherText, $key)
    {
        $result = openssl_decrypt(base64_decode($cipherText), 'AES-128-ECB', $key, 1);
        if (!$result) {
            throw new \Exception('报文解密错误', 2003);
        }
        return $result;
    }
}