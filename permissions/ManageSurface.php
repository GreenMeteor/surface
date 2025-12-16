<?php

namespace humhub\modules\surface\permissions;

use Yii;
use humhub\modules\user\models\User;
use humhub\modules\user\models\Group;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\admin\components\BaseAdminPermission;

/**
 * ManageSurface permission allows admins to configure surface rules
 */
class ManageSurface extends BaseAdminPermission
{
    /**
     * @inheritdoc
     */
    protected $id = 'manage_surface';

    /**
     * @inheritdoc
     */
    protected $title = 'Manage Surface';

    /**
     * @inheritdoc
     */
    protected $description = 'Can manage surface rules and settings';

    /**
     * @inheritdoc
     */
    protected $moduleId = 'surface';

    /**
     * @inheritdoc
     */
    public function getDefaultState($userId, $contentContainer = null)
    {
        $user = User::findOne(['id' => $userId]);

        if ($user !== null && Group::getAdminGroup()->isMember($user)) {
            return self::STATE_ALLOW;
        }

        return self::STATE_DENY;
    }

    /**
     * @inheritdoc
     */
    protected $fixedGroups = [
        User::USERGROUP_SELF,
        User::USERGROUP_FRIEND,
        User::USERGROUP_USER,
        User::USERGROUP_GUEST
    ];

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Yii::t('SurfaceModule.base', 'Manage Surface');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Yii::t('SurfaceModule.base', 'Can manage surface rules and settings');
    }

    /**
     * @inheritdoc
     */
    public function requiredGroups()
    {
        return [
            ['name' => 'admin', 'class' => ManageModules::class]
        ];
    }
}