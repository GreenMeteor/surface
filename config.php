<?php

use humhub\modules\surface\Module;
use humhub\modules\surface\Events;

return [
    'id' => 'surface',
    'class' => Module::class,
    'isCoreModule' => true,
    'events' => [
        ['class' => \yii\web\View::class, 'event' => \yii\web\View::EVENT_END_BODY, 'callback' => [Events::class, 'onViewEndBody']],
    ],
];