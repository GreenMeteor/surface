<?php

use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;
use humhub\widgets\form\ActiveForm;
use humhub\modules\ui\icon\widgets\Icon;
use yii\helpers\Html;

$this->registerJs(
    'humhub.modules.surface.initFormHandler();',
    \yii\web\View::POS_READY,
    'surface-form-handler'
);
?>

<?php $form = Modal::beginFormDialog([
    'title' => Yii::t('SurfaceModule.base', 'Configure Container Visibility'),
    'footer' => ModalButton::cancel() . ' ' . ModalButton::save()->submit(),
    'form' => [
        'action' => ['/surface/admin/save-rule'],
        'options' => [
            'id' => 'surface-rule-form',
        ],
    ],
]) ?>

    <div class="surface-container-info">
        <?= Icon::get('cube') ?>
        <strong><?= Yii::t('SurfaceModule.base', 'Container:') ?></strong>
        <?= Html::encode($model->container_name ?: $model->container_selector) ?>
    </div>

    <?= $form->field($model, 'container_selector')->hiddenInput()->label(false) ?>

    <?= $form->field($model, 'container_name')->textInput([
        'readonly' => true,
        'placeholder' => Yii::t('SurfaceModule.base', 'Container identifier'),
    ]) ?>

    <?= $form->field($model, 'disabled_for_all')->checkbox([
        'label' => Yii::t('SurfaceModule.base', 'Disable this container for all users'),
    ]) ?>

    <div id="user-select-container" class="d-none">
        <?= $form->field($model, 'user_id')->dropDownList($userList, [
            'prompt' => Yii::t('SurfaceModule.base', 'Select a user...'),
            'class' => 'form-control',
        ])->hint(Yii::t('SurfaceModule.base', 'Select a specific user to disable this container for')) ?>
    </div>

    <div class="alert alert-info">
        <?= Icon::get('info-circle') ?>
        <?= Yii::t('SurfaceModule.base', 'This rule will hide the selected container from view. The container will be completely invisible to affected users.') ?>
    </div>

<?php Modal::endFormDialog() ?>