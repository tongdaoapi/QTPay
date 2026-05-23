<?php
declare (strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class Notify extends Command
{
    protected function configure()
    {
        $this->setName('Notify')->setDescription('通知');
    }

    protected function execute(Input $input, Output $output)
    {
        while (true) {
            $notifyList = Db::name('notify')->where('status', 0)->select();
            foreach ($notifyList as $key => $value) {
                $order = Db::name('order')->where('id', $value['order_id'])->find();
                if (time() >= strtotime($value['last_notify_time']) + 5 && $value['time'] < 10 && !$order['callback_status']) {
                    if ($order) {
                        $merchant = Db::name('merchant')->where('id', $order['merchant_id'])->find();
                        if ($merchant) {
                            $params = [
                                'appid' => $merchant['appid'],
                                'amount' => $order['amount'],
                                'merchant_order_sn' => $order['merchant_order_sn'],
                                'order_sn' => $order['order_sn'],
                                'product_id' => $order['product_id'],
                                'pay_status' => $order['pay_status'],
                                'pay_at' => $order['pay_at']
                            ];
                            $params['sign'] = $this->getSign($params, $merchant['secret']);
                            $log = $value['log'] ? json_decode($value['log']) : [];
                            $request = makeCurlRequest($order['notify_url'], $params);
                            $log[] = $request;
                            if ($request == 'success') {
                                Db::name('notify')->where('id', $value['id'])->update([
                                    'log' => json_encode($log),
                                    'last_notify_time' => date('Y-m-d H:i:s'),
                                    'status' => 1,
                                    'time' => $value['time'] + 1
                                ]);
                                Db::name('order')->where('id', $order['id'])->update([
                                    'callback_status' => 1,
                                    'callback_at' => date('Y-m-d H:i:s')
                                ]);
                            } else {
                                Db::name('notify')->where('id', $value['id'])->update([
                                    'log' => json_encode($log),
                                    'last_notify_time' => date('Y-m-d H:i:s'),
                                    'time' => $value['time'] + 1
                                ]);
                            }
                            dump('订单：' . $order['order_sn'] . '，通知：' . $value['time'] . '，地址：' . $order['notify_url'] . '，参数：' . json_encode($params));
                        }
                    }
                } else {
                    if (!$order['callback_status']) {
                        Db::name('order')->where('id', $value['order_id'])->update([
                            'callback_status' => 2,
                            'callback_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
        }
    }

    public function getSign($arr, $secret)
    {
        unset($arr['sign']);
        ksort($arr, SORT_STRING);
        $beforeSignString = urldecode(http_build_query($arr, '', '&')) . '&secret=' . $secret;
        $signString = strtoupper(md5($beforeSignString));
        return $signString;
    }

}
