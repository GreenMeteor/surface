<?php

namespace humhub\modules\surface;

use humhub\modules\ui\icon\widgets\Icon;
use humhub\modules\surface\models\SurfaceRule;
use humhub\modules\surface\assets\SurfaceAsset;
use humhub\components\View;
use yii\helpers\Url;
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
            $css = "/* Surface Module - Hidden Containers */\n";
            foreach ($rules as $rule) {
                $cssSelector = $rule->getCssSelector();
                $css .= "{$cssSelector} { display: none !important; }\n";
            }
            $view->registerCss($css, [], 'surface-hidden-rules');
        }

        /** @var Module $module */
        $module = Yii::$app->getModule('surface');

        if ($module->isAdminModeEnabled()) {

            if (!empty($rules)) {
                $selectors = array_map(fn($rule) => $rule->container_selector, $rules);

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

            if (Yii::$app->user->isAdmin()) {
                $view->registerJs(
                    'document.body.classList.add("user-is-admin");',
                    View::POS_READY,
                    'surface-admin-body-class'
                );
            }
        }
    }

    public static function onAdminMenuInit($event)
    {
        /** @var AdminMenu $menu */
        $menu = $event->sender;

        $menu->addItem([
            'label' => 'Surface',
            'url' => Url::to(['/surface/admin/index']),
            'group' => 'manage',
            'icon' => Icon::get('pause-circle'),
            'isActive' => (Yii::$app->controller->module
                && Yii::$app->controller->module->id === 'surface'),
            'sortOrder' => 500,
        ]);
    }
}