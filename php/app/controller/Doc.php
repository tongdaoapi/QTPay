<?php

namespace app\controller;

use app\BaseController;
use PHPGangsta_GoogleAuthenticator;
use think\facade\Db;

class Doc extends BaseController
{

    protected $notNeedToken = ['index'];

    public function index()
    {
        return view();
    }
}
