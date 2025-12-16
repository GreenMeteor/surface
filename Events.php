<?php

namespace humhub\modules\surface;

use humhub\modules\surface\models\SurfaceRule;
use humhub\modules\surface\assets\SurfaceAsset;
use yii\web\View;
use Yii;

class Events
{
    public static function onViewEndBody($event)
    {
        /** @var View $view */
        $view = $event->sender;

        SurfaceAsset::register($view);

        $userId = Yii::$app->user->isGuest ? null : Yii::$app->user->id;
        $rules = SurfaceRule::getActiveRules($userId);

        if (!empty($rules)) {
            $selectors = array_map(function($rule) {
                return $rule->container_selector;
            }, $rules);

            $view->registerJs(
                'humhub.modules.surface.setDisabledContainers(' . json_encode($selectors) . ');',
                View::POS_END,
                'surface-disabled-containers'
            );
        }

        $isAdmin = Yii::$app->user->isAdmin() ? 'true' : 'false';
        $view->registerJs(
            'humhub.modules.surface.setAdminStatus(' . $isAdmin . ');',
            View::POS_END,
            'surface-admin-status'
        );
    }
}