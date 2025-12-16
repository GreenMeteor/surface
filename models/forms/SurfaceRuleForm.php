<?php

namespace humhub\modules\surface\models\forms;

use humhub\modules\surface\models\SurfaceRule;
use yii\base\Model;
use Yii;

class SurfaceRuleForm extends Model
{
    public $container_selector;
    public $container_name;
    public $disabled_for_all = 0;
    public $user_id;

    public function rules(): array
    {
        return [
            [['container_selector'], 'required'],
            [['disabled_for_all', 'user_id'], 'integer'],
            [['container_selector', 'container_name'], 'string', 'max' => 255],
            ['user_id', 'required', 'when' => function($model) {
                return !$model->disabled_for_all;
            }, 'message' => Yii::t('SurfaceModule.base', 'Please select a user or enable for all users.')],
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

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $rule = SurfaceRule::findOne([
            'container_selector' => $this->container_selector,
            'disabled_for_all' => $this->disabled_for_all,
            'user_id' => $this->user_id,
        ]);

        if (!$rule) {
            $rule = new SurfaceRule();
            $rule->container_selector = $this->container_selector;
            $rule->container_name = $this->container_name;
            $rule->disabled_for_all = $this->disabled_for_all;
            $rule->user_id = $this->disabled_for_all ? null : $this->user_id;
            $rule->created_by = Yii::$app->user->id;
        }

        return $rule->save();
    }
}