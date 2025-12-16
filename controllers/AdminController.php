<?php

namespace humhub\modules\surface\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\surface\models\SurfaceRule;
use humhub\modules\surface\models\forms\SurfaceRuleForm;
use humhub\modules\user\models\User;
use Yii;
use yii\web\Response;

class AdminController extends Controller
{
    public function actionIndex()
    {
        $rules = SurfaceRule::find()->orderBy(['created_at' => SORT_DESC])->all();

        return $this->render('index', [
            'rules' => $rules,
        ]);
    }

    public function actionRuleModal($selector = null, $name = null)
    {
        $model = new SurfaceRuleForm();

        if ($selector) {
            $model->container_selector = $selector;
            $model->container_name = $name;
        }

        $existingRules = [];
        if ($selector) {
            $rules = SurfaceRule::find()
                ->where(['container_selector' => $selector])
                ->with(['user'])
                ->all();

            foreach ($rules as $rule) {
                $existingRules[] = [
                    'id' => $rule->id,
                    'disabled_for_all' => (bool)$rule->disabled_for_all,
                    'user_id' => $rule->user_id,
                    'username' => $rule->user ? $rule->user->username : null,
                ];
            }
        }

        $userList = User::find()
            ->select(['username', 'id'])
            ->indexBy('id')
            ->column();

        return $this->renderAjax('_ruleModal', [
            'model' => $model,
            'userList' => $userList,
            'existingRules' => $existingRules,
        ]);
    }

    public function actionSaveRule()
    {
        $model = new SurfaceRuleForm();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'success' => true,
                'message' => Yii::t('SurfaceModule.base', 'Rule saved successfully.'),
            ];
        }

        $userList = User::find()
            ->select(['username', 'id'])
            ->indexBy('id')
            ->column();

        $existingRules = [];
        if ($model->container_selector) {
            $rules = SurfaceRule::find()
                ->where(['container_selector' => $model->container_selector])
                ->with(['user'])
                ->all();

            foreach ($rules as $rule) {
                $existingRules[] = [
                    'id' => $rule->id,
                    'disabled_for_all' => (bool)$rule->disabled_for_all,
                    'user_id' => $rule->user_id,
                    'username' => $rule->user ? $rule->user->username : null,
                ];
            }
        }

        return $this->renderAjax('_ruleModal', [
            'model' => $model,
            'userList' => $userList,
            'existingRules' => $existingRules,
        ]);
    }

    public function actionDelete($id)
    {
        $rule = $this->findModel($id);
        $rule->delete();
        $this->view->success(Yii::t('SurfaceModule.base', 'Rule deleted successfully'));

        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = SurfaceRule::findOne($id)) !== null) {
            return $model;
        }
        throw new \yii\web\HttpException(404, Yii::t('SurfaceModule.base', 'The requested rule does not exist.'));
    }

    public function actionGetRuleData($selector)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $rules = SurfaceRule::find()
            ->where(['container_selector' => $selector])
            ->all();

        return [
            'exists' => !empty($rules),
            'rules' => array_map(function($rule) {
                return [
                    'id' => $rule->id,
                    'disabled_for_all' => (bool)$rule->disabled_for_all,
                    'user_id' => $rule->user_id,
                    'username' => $rule->user ? $rule->user->username : null,
                ];
            }, $rules),
        ];
    }
}