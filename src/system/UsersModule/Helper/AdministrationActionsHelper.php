<?php

declare(strict_types=1);

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\UsersModule\Helper;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zikula\PermissionsModule\Api\ApiInterface\PermissionApiInterface;
use Zikula\UsersModule\Api\ApiInterface\CurrentUserApiInterface;
use Zikula\UsersModule\Constant as UsersConstant;
use Zikula\UsersModule\Entity\UserEntity;

class AdministrationActionsHelper
{
    /**
     * @var PermissionApiInterface
     */
    private $permissionsApi;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CurrentUserApiInterface
     */
    private $currentUser;

    public function __construct(
        PermissionApiInterface $permissionsApi,
        RouterInterface $router,
        TranslatorInterface $translator,
        CurrentUserApiInterface $currentUserApi
    ) {
        $this->permissionsApi = $permissionsApi;
        $this->router = $router;
        $this->translator = $translator;
        $this->currentUser = $currentUserApi;
    }

    public function user(UserEntity $user): array
    {
        $actions = [];
        if (!$this->permissionsApi->hasPermission('ZikulaUsersModule::', 'ANY', ACCESS_MODERATE)) {
            return $actions;
        }
        if (UsersConstant::ACTIVATED_ACTIVE !== $user->getActivated() && $this->permissionsApi->hasPermission('ZikulaUsersModule::', '::', ACCESS_ADMIN)) {
            $actions['approveForce'] = [
                'url' => $this->router->generate('zikulausersmodule_useradministration_approve', ['user' => $user->getUid(), 'force' => true]),
                'text' => $this->translator->trans('Approve %sub%', ['%sub%' => $user->getUname()]),
                'icon' => 'check text-success',
            ];
        }
        $hasEditPermissionToUser = $this->permissionsApi->hasPermission('ZikulaUsersModule::', $user->getUname() . '::' . $user->getUid(), ACCESS_EDIT);
        $hasDeletePermissionToUser = $this->permissionsApi->hasPermission('ZikulaUsersModule::', $user->getUname() . '::' . $user->getUid(), ACCESS_DELETE);
        if ($hasEditPermissionToUser && $user->getUid() > UsersConstant::USER_ID_ANONYMOUS) {
            $actions['modify'] = [
                'url' => $this->router->generate('zikulausersmodule_useradministration_modify', ['user' => $user->getUid()]),
                'text' => $this->translator->trans('Edit %sub%', ['%sub%' => $user->getUname()]),
                'icon' => 'pencil-alt',
            ];
        }
        $isCurrentUser = $this->currentUser->get('uid') === $user->getUid();
        if (!$isCurrentUser && $hasDeletePermissionToUser && $user->getUid() > UsersConstant::USER_ID_ADMIN) {
            $actions['delete'] = [
                'url' => $this->router->generate('zikulausersmodule_useradministration_delete', ['user' => $user->getUid()]),
                'text' => $this->translator->trans('Delete %sub%', ['%sub%' => $user->getUname()]),
                'icon' => 'trash-alt',
            ];
        }

        return $actions;
    }
}
