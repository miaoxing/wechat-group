<?php

namespace Miaoxing\WechatGroup\Service;

use Miaoxing\Plugin\Service\Group;

class WechatGroup extends \Miaoxing\Plugin\BaseService
{
    public function syncFromWechat()
    {
        $counts = [
            'created' => 0,
            'updated' => 0,
        ];

        $account = wei()->wechatAccount->getCurrentAccount();
        $api = $account->createApiService();
        $batchGetRet = $api->batchGetGroup();

        if (!$batchGetRet) {
            return $api->getResult();
        }

        if (!$batchGetRet['groups']) {
            return ['code' => 1, 'message' => '没有获取到任何分组'];
        }

        $groups = $batchGetRet['groups'];
        foreach ($groups as $arrGroup) {
            // 除去未分组的
            if ($arrGroup['id'] == 0) {
                continue;
            }

            $group = wei()->group()->findOrInit(['wechatId' => $arrGroup['id']]);
            if ($group) {
                $group->isNew() ? $counts['created']++ : $counts['updated']++;
                $group->save([
                    'name' => $arrGroup['name'],
                    'wechatCount' => $arrGroup['count'],
                ]);
            }
        }

        $message = vsprintf('同步完成,共新增了%s个,更新了%s个', $counts);

        return ['code' => 1, 'message' => $message];
    }

    public function getCreateWechatGroupData(\Miaoxing\Plugin\Service\Group $group)
    {
        return [
            'group' => [
                'name' => $group['name'],
            ],
        ];
    }

    public function getUpdateWechatGroupData(\Miaoxing\Plugin\Service\Group $group)
    {
        return [
            'group' => [
                'id' => $group['wechatId'],
                'name' => $group['name'],
            ],
        ];
    }
}
