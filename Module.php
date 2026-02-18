<?php

namespace humhub\modules\surface;

use Yii;

class Module extends \humhub\components\Module
{
    public $isCoreModule = true;

    /**
     * @inheritdoc
     */
    public function getPermissions($contentContainer = null): array
    {
        if ($contentContainer === null) {
            return [
                new permissions\ManageSurface(),
            ];
        }

        return [];
    }

    /**
     * Check if Surface admin mode is enabled (system-wide)
     */
    public function isAdminModeEnabled(): bool
    {
        $rule = models\SurfaceRule::find()->one();

        return $rule ? (bool)$rule->state : true;
    }

    /**
     * Toggle admin mode
     */
    public function toggleAdminMode(): void
    {
        $rule = models\SurfaceRule::find()->one();

        if ($rule) {
            $rule->state = !$rule->state;
            $rule->save(false);
        } else {
            $rule = new models\SurfaceRule();
            $rule->container_selector = 'admin_mode_state';
            $rule->created_by = Yii::$app->user->id;
            $rule->state = 0;
            $rule->save(false);
        }
    }
}