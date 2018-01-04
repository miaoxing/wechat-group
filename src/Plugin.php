<?php

namespace Miaoxing\WechatGroup;

use Miaoxing\Plugin\Service\Group;
use Miaoxing\Plugin\Service\User;
use Miaoxing\Wechat\Service\WechatAccount;
use Wei\WeChatApp;

class Plugin extends \Miaoxing\Plugin\BasePlugin
{
    protected $name = '微信分组';

    protected $description = '包含分组同步,';

    public function onGroupUpdate(Group $group)
    {
        $wechatGroup = wei()->wechatGroup;
        $account = wei()->wechatAccount->getCurrentAccount();
        $api = $account->createApiService();
        if (!$group['wechatId']) {
            $ret = $api->createGroup($wechatGroup->getCreateWechatGroupData($group));

            if (isset($ret['group'])) {
                $group->save(['wechatId' => $ret['group']['id']]);
            }
        } else {
            $ret = $api->updateGroup($wechatGroup->getUpdateWechatGroupData($group));
        }

        if (!$ret) {
            return $api->getResult();
        }
    }

    public function onGroupDestroy(Group $group)
    {
        if ($group['wechatId']) {
            $account = wei()->wechatAccount->getCurrentAccount();
            $api = $account->createApiService();
            $ret = $api->deleteGroup($group['wechatId']);

            if (!$ret) {
                return $api->getResult();
            }
        }
    }

    public function onGroupMove(array $userIds, Group $group)
    {
        $account = wei()->wechatAccount->getCurrentAccount();
        $api = $account->createApiService();

        $users = wei()->user()->where(['id' => $userIds])->findAll();
        $openIds = array_filter(wei()->coll->column($users->toArray(), 'wechatOpenId'));
        $openIds = array_values($openIds); // 重置键值

        if (count($openIds) > 1) {
            $ret = $api->updateBatchMemberGroup($openIds, $group['wechatId']);
        } elseif (count($openIds) == 1) {
            $ret = $api->updateMemberGroup($openIds[0], $group['wechatId']);
        }

        if (isset($ret) && !$ret) {
            return $api->getResult();
        }
    }

    public function onPreSyncUser()
    {
        // 5分钟调用一次
        $cacheKey = 'wechatGroupSyncTime' . $this->app->getId();
        wei()->cache->get($cacheKey, 300, function () {
            wei()->wechatGroup->syncFromWechat();
        });
    }

    /**
     * 用户重新关注时,加入到原来的分组
     *
     * @param WeChatApp $app
     * @param \Miaoxing\Plugin\Service\User $user
     * @param \Miaoxing\Wechat\Service\WechatAccount $account
     */
    public function onWechatSubscribe(WeChatApp $app, User $user, WechatAccount $account)
    {
        if (!$user['groupId']) {
            return;
        }

        $group = wei()->group()->findOrInitById($user['groupId']);
        if (!$group['wechatId']) {
            return;
        }

        $api = $account->createApiService();
        $result = $api->updateMemberGroup($user['wechatOpenId'], $group['wechatId']);
        if (!$result) {
            $this->logger->alert('移动分组失败', $api->getResult());
        }
    }
}
