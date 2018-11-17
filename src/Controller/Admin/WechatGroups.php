<?php

namespace Miaoxing\WechatGroup\Controller\Admin;

class WechatGroups extends \Miaoxing\Plugin\BaseController
{
    protected $controllerName = '微信分组管理';

    protected $actionPermissions = [
        'syncFromWechat' => '同步',
    ];

    public function syncFromWechatAction()
    {
        $ret = wei()->wechatGroup->syncFromWechat();

        return $ret;
    }
}
