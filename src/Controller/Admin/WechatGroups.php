<?php

namespace Miaoxing\WechatGroup\Controller\Admin;

class WechatGroups extends \miaoxing\plugin\BaseController
{
    protected $controllerName = '微信分组管理';

    protected $actionPermissions = [
        'syncFromWechat' => '同步',
    ];

    /**
     * 同步微信分组
     * @param $req
     * @return \Wei\Response
     */
    public function syncFromWechatAction($req)
    {
        $ret = wei()->wechatGroup->syncFromWechat();

        return $this->ret($ret);
    }
}
