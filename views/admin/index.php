<?php

use humhub\widgets\bootstrap\Button;
use humhub\modules\ui\icon\widgets\Icon;
use yii\helpers\Html;
use yii\helpers\Url;

$this->pageTitle = Yii::t('SurfaceModule.base', 'Surface Rules Management');
?>

<div class="container-fluid">
    <div class="panel panel-default">
        <div class="panel-heading">
            <?= Yii::t('SurfaceModule.base', 'Container Visibility Rules') ?>
        </div>
        <div class="panel-body">
            <div class="surface-alert-info">
                <?= Icon::get('info-circle') ?>
                <?= Yii::t('SurfaceModule.base', 'Configure which UI containers are visible to specific users or all users. Hover over containers on any page to see the flag icon and create rules.') ?>
            </div>

            <?php if (empty($rules)): ?>
                <div class="surface-empty-state">
                    <?= Icon::get('flag') ?>
                    <p><?= Yii::t('SurfaceModule.base', 'No rules configured yet.') ?></p>
                    <p><?= Yii::t('SurfaceModule.base', 'Navigate to any page and hover over containers marked with data-surface-container to create rules.') ?></p>
                </div>
            <?php else: ?>
                <div class="surface-stats">
                    <div class="surface-stat-card">
                        <span class="stat-number"><?= count($rules) ?></span>
                        <span class="stat-label"><?= Yii::t('SurfaceModule.base', 'Total Rules') ?></span>
                    </div>
                    <div class="surface-stat-card">
                        <span class="stat-number"><?= count(array_filter($rules, fn($r) => $r->disabled_for_all)) ?></span>
                        <span class="stat-label"><?= Yii::t('SurfaceModule.base', 'Global Rules') ?></span>
                    </div>
                    <div class="surface-stat-card">
                        <span class="stat-number"><?= count(array_filter($rules, fn($r) => !$r->disabled_for_all)) ?></span>
                        <span class="stat-label"><?= Yii::t('SurfaceModule.base', 'User-Specific Rules') ?></span>
                    </div>
                </div>

                <table class="table table-striped surface-rules-table">
                    <thead>
                        <tr>
                            <th><?= Yii::t('SurfaceModule.base', 'Container Name') ?></th>
                            <th><?= Yii::t('SurfaceModule.base', 'Selector') ?></th>
                            <th><?= Yii::t('SurfaceModule.base', 'Scope') ?></th>
                            <th><?= Yii::t('SurfaceModule.base', 'Created By') ?></th>
                            <th><?= Yii::t('SurfaceModule.base', 'Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rules as $rule): ?>
                            <tr>
                                <td>
                                    <strong><?= Html::encode($rule->container_name ?: $rule->container_selector) ?></strong>
                                </td>
                                <td>
                                    <code><?= Html::encode($rule->container_selector) ?></code>
                                </td>
                                <td>
                                    <?php if ($rule->disabled_for_all): ?>
                                        <span class="surface-badge-all">
                                            <?= Icon::get('globe') ?>
                                            <?= Yii::t('SurfaceModule.base', 'All Users') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="surface-badge-user">
                                            <?= Icon::get('user') ?>
                                            <?= Html::encode($rule->user->username ?? 'Unknown') ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= Html::encode($rule->creator->username ?? 'System') ?>
                                </td>
                                <td>
                                    <?= Button::danger()
                                        ->icon('trash')
                                        ->sm()
                                        ->link(Url::to(['/surface/admin/delete', 'id' => $rule->id]))
                                        ->confirm(
                                            Yii::t('SurfaceModule.base', 'Delete Rule'),
                                            Yii::t('SurfaceModule.base', 'Are you sure you want to delete this rule?')
                                        ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>