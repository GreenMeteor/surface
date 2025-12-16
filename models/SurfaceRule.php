<?php

namespace humhub\modules\surface\models;

use humhub\modules\user\models\User;
use humhub\components\ActiveRecord;
use Yii;

/**
 * @property int $id
 * @property string $container_selector
 * @property string $container_name
 * @property int $disabled_for_all
 * @property int|null $user_id
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 * @property User $user
 * @property User $creator
 */
class SurfaceRule extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'surface_rule';
    }

    public function rules(): array
    {
        return [
            [['container_selector', 'created_by'], 'required'],
            [['disabled_for_all', 'user_id', 'created_by'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['container_selector', 'container_name'], 'string', 'max' => 255],
            ['user_id', 'required', 'when' => function($model) {
                return !$model->disabled_for_all;
            }],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'container_selector' => Yii::t('SurfaceModule.base', 'Container Selector'),
            'container_name' => Yii::t('SurfaceModule.base', 'Container Name'),
            'disabled_for_all' => Yii::t('SurfaceModule.base', 'Disable for all users'),
            'user_id' => Yii::t('SurfaceModule.base', 'Specific User'),
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Get active rules for a specific user
     */
    public static function getActiveRules(?int $userId): array
    {
        return static::find()
            ->where([
                'or',
                ['disabled_for_all' => 1],
                ['user_id' => $userId]
            ])
            ->all();
    }

    /**
     * Check if a container is disabled for a user
     */
    public static function isDisabled(string $selector, ?int $userId): bool
    {
        return static::find()
            ->where(['container_selector' => $selector])
            ->andWhere([
                'or',
                ['disabled_for_all' => 1],
                ['user_id' => $userId]
            ])
            ->exists();
    }
}