<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Released under the MIT License.
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------

namespace app\demo\controller;

use cmf\controller\AdminBaseController;

class AdminIndexController extends AdminBaseController
{
    public function index()
    {
        echo "admin index";die;
        return $this->fetch();
    }

    public function ws()
    {
        echo "admin ws";die;
        return $this->fetch(':ws');
    }
}
