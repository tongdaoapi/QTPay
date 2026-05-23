<?php

namespace app\controller;

use app\BaseController;
use PHPGangsta_GoogleAuthenticator;
use think\facade\Db;

class User extends BaseController
{

    protected $notNeedToken = ['login', 'createUser'];

    public function login()
    {
        $username = input('post.username');
        $password = input('post.password');
        $code = input('post.code');
        $merchant = Db::name('merchant')->where('username', $username)->where('password', md5($password))->find();
        if (!$merchant) {
            $this->error('用户名或者密码输入错误');
        }
        if ($merchant['googleVerification_secret'] && !$code) {
            $this->error('请输入谷歌验证码');
        }
        if ($merchant['googleVerification_secret'] && $code) {
            $ga = new PHPGangsta_GoogleAuthenticator();
            if (!$ga->verifyCode($merchant['googleVerification_secret'], $code, 2)) {
                return $this->error('验证码错误');
            }
        }
        $token = generateToken($merchant['id']);
        Db::name('merchant')->where('id', $merchant['id'])->update(['token' => $token]);
        $merchant['token'] = $token;
        $this->success([
            'user' => $merchant
        ]);
    }

    public function index()
    {
        $this->success([
            'amount' => $this->userInfo['amount'],
            'todayOrderCount' => Db::name('order')->where('merchant_id = ' . $this->userInfo['id'])->whereDay('created_at')->count(),
            'todayOrderAmount' => Db::name('order')->where('merchant_id = ' . $this->userInfo['id'])->whereDay('created_at')->sum('amount'),
            'todayOrderFee' => Db::name('order')->where('merchant_id = ' . $this->userInfo['id'])->whereDay('pay_at')->sum('fee'),
            'todayOrderPayCount' => Db::name('order')->where('merchant_id = ' . $this->userInfo['id'])->where('pay_status = 1')->whereDay('pay_at')->count(),
            'todayOrderPayAmount' => Db::name('order')->where('merchant_id = ' . $this->userInfo['id'])->where('pay_status = 1')->whereDay('pay_at')->sum('amount'),
        ]);
    }

    public function getUserInfo()
    {
        $this->success([
            'user' => $this->userInfo
        ]);
    }

    public function updateUserInfoSecret()
    {
        Db::name('merchant')->where('id', $this->userInfo['id'])->update([
            'secret' => generateSecret()
        ]);
        $this->refreshUser();
        $this->success([
            'user' => $this->userInfo
        ]);
    }

    public function updateUserInfoIpWhiteList()
    {
        $ipWhiteList = input('ip_white_list');
        Db::name('merchant')->where('id', $this->userInfo['id'])->update([
            'ip_white_list' => $ipWhiteList
        ]);
        $this->refreshUser();
        $this->success([
            'user' => $this->userInfo
        ]);
    }

    public function updateUserInfoPassword()
    {
        $oldPassword = input('oldPassword');
        $newPassword = input('newPassword');
        $confirmPassword = input('confirmPassword');
        $password = Db::name('merchant')->where('id', $this->userInfo['id'])->value('password');
        if (!$newPassword) {
            $this->error('新密码不能为空');
        }
        if (md5($oldPassword) != $password) {
            $this->error('旧密码错误');
        }
        if ($newPassword != $confirmPassword) {
            $this->error('两次密码不一致');
        }
        Db::name('merchant')->where('id', $this->userInfo['id'])->update([
            'password' => md5($newPassword)
        ]);
        $this->success();
    }

    public function getGoogleVerificationCode()
    {
        $ga = new PHPGangsta_GoogleAuthenticator();
        $secret = $ga->createSecret();
        session('googleVerification_secret_' . $this->userInfo['id'], $secret);
        $username = $this->userInfo['username'];
        $appName = '支付';
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($username, $secret, $appName);
        $this->success([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'googleVerification_secret' => $this->userInfo['googleVerification_secret']
        ]);
    }

    public function updateGoogleVerificationCode()
    {
        $secret = input('secret');
        if (!$secret) {
            $this->error('请输入验证码');
        }
        $tempSecret = session('googleVerification_secret_' . $this->userInfo['id']);
        if (!$tempSecret) {
            $this->error('请先获取绑定密钥');
        }
        $ga = new PHPGangsta_GoogleAuthenticator();
        $isValid = $ga->verifyCode($tempSecret, $secret, 2);
        if (!$isValid) {
            $this->error('验证码错误，请重新输入');
        }
        Db::name('merchant')->where('id', $this->userInfo['id'])->update([
            'googleVerification_secret' => $tempSecret,
        ]);
        // 清除临时数据
        session('googleVerification_secret_' . $this->userInfo['id'], null);
        $this->refreshUser();
        $this->getGoogleVerificationCode();
    }

    public function unbindGoogleVerificationCode()
    {
        Db::name('merchant')->where('id', $this->userInfo['id'])->update([
            'googleVerification_secret' => ''
        ]);
        $this->refreshUser();
        $this->getGoogleVerificationCode();
    }

    public function getPaymentProductList()
    {
        $pageSize = input('pageSize', 10);
        $page = input('page', 1);
        $data = Db::name('payment_product')->field(['id', 'name', 'min_amount', 'max_amount', 'status'])->order('status', 'asc')->order('id', 'desc')->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
            'var_page' => 'page',
        ])->toArray();
        foreach ($data['data'] as $key => &$value) {
            $merchantPaymentProduct = Db::name('merchant_payment_product')->where('payment_product_id', $value['id'])->find();
            if ($merchantPaymentProduct) {
                $value['product_id'] = $merchantPaymentProduct['id'];
                $value['rate'] = $merchantPaymentProduct['rate'];
            }
        }
        $this->success($data);
    }

    public function getFundFlowList()
    {
        $pageSize = input('pageSize', 10);
        $page = input('page', 1);
        $data = Db::name('order')->where('merchant_id = ' . $this->userInfo['id'])->order('id', 'desc')->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
            'var_page' => 'page',
        ])->toArray();
        $this->success($data);
    }

    public function createUser()
    {
        $username = input('username');
        if (!$username) $this->error('请输入用户名');
        $merchant = Db::name('merchant')->where('username', $username)->find();
        if ($merchant) {
            $this->success('查询成功', [
                'username' => $username,
                'appid' => $merchant['appid'],
                'secret' => $merchant['secret'],
            ]);
        } else {
            $password = generatePassword();
            $appid = generateAppid();
            $secret = generateSecret();
            Db::name('merchant')->insert([
                'username' => $username,
                'password' => md5($password),
                'appid' => $appid,
                'secret' => $secret,
            ]);
            $this->success('创建成功', [
                'username' => $username,
                'password' => $password,
                'appid' => $appid,
                'secret' => $secret,
            ]);
        }
    }

}
