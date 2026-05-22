<?php
declare (strict_types=1);

namespace app;

use think\App;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Db;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 不需要验证token的方法列表（子控制器覆盖此属性）
     * @var array
     */
    protected $notNeedToken = [];

    /**
     * Token验证后的用户信息
     * @var array|null
     */
    protected $userInfo = null;

    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();

        // Token验证（放在initialize之后，这样子控制器可以在initialize中动态修改$notNeedToken）
        $this->checkToken();
    }

    // 初始化
    protected function initialize()
    {
    }

    /**
     * Token验证
     * @access protected
     * @return void
     */
    protected function checkToken()
    {
        $action = $this->request->action();
        if (in_array($action, $this->notNeedToken)) {
            return;
        }
        $token = $this->getToken();
        if (!$token) {
            $this->error('请先登录', 401);
        }
        $userInfo = $this->verifyToken($token);
        if (!$userInfo) {
            $this->error('Token无效或已过期', 401);
        }
        $this->userInfo = $userInfo;
    }

    /**
     * 获取Token
     * @access protected
     * @return string|null
     */
    protected function getToken()
    {
        $token = $this->request->header('Authorization');
        if ($token) {
            if (strpos($token, 'Bearer ') === 0) {
                $token = substr($token, 7);
            }
            return $token;
        }
        return null;
    }

    /**
     * 验证Token（子控制器可重写此方法）
     * @access protected
     * @param string $token
     * @return array|false 验证成功返回用户信息数组，失败返回false
     */
    protected function verifyToken($token)
    {
        return Db::name('merchant')->field(['id', 'username', 'amount', 'appid', 'secret', 'ip_white_list', 'googleVerification_secret', 'token'])->where('token', $token)->find();
    }

    protected function refreshUser()
    {
        $this->userInfo = Db::name('merchant')->field(['id', 'username', 'amount', 'appid', 'secret', 'ip_white_list', 'googleVerification_secret', 'token'])->where('id', $this->userInfo['id'])->find();
    }

    /**
     * 成功返回
     * @access protected
     * @param mixed $data 返回数据
     * @param string $msg 提示信息
     * @param int $code 状态码
     * @return \think\response\Json
     */
    protected function success($data = [], $msg = 'success', $code = 200)
    {
        $response = json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'time' => time()
        ]);
        throw new HttpResponseException($response);
    }

    /**
     * 错误返回
     * @access protected
     * @param string $msg 提示信息
     * @param int $code 状态码
     * @param mixed $data 附加数据
     * @return \think\response\Json
     */
    protected function error($msg = 'error', $code = 400, $data = [])
    {
        $response = json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ], $code);
        throw new HttpResponseException($response);
    }

    protected function apiError($msg = 'error', $code = 400, $data = [])
    {
        $response = json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
        throw new HttpResponseException($response);
    }

    /**
     * 验证数据
     * @access protected
     * @param array $data 数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array $message 提示信息
     * @param bool $batch 是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, string|array $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

}
