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
                new permissions\ManageSurface
            ];
        }
        return [];
    }
}
