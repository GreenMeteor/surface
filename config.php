<?php

use humhub\components\View;
use humhub\modules\surface\Module;
use humhub\modules\surface\Events;
use humhub\modules\admin\widgets\AdminMenu;

return [
    'id' => 'surface',
    'class' => Module::class,
    'isCoreModule' => true,
    'events' => [
        ['class' => View::class, 'event' => View::EVENT_END_BODY, 'callback' => [Events::class, 'onViewEndBody']],
        ['class' => AdminMenu::class, 'event' => AdminMenu::EVENT_INIT, 'callback' => [Events::class, 'onAdminMenuInit']],
    ],
];
