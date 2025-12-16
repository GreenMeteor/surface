<?php

namespace humhub\modules\surface\assets;

use yii\web\AssetBundle;

class SurfaceAsset extends AssetBundle
{
    public $sourcePath = '@surface/resources';

    public $js = [
        'js/humhub.surface.js',
    ];

    public $css = [
        'css/surface.css',
    ];

    public $depends = [
        \humhub\assets\CoreApiAsset::class,
    ];
}