<?php

namespace humhub\modules\surface\models;

use humhub\modules\user\models\User;
use humhub\components\ActiveRecord;
use Yii;

/**
 * @property int         $id
 * @property string      $container_selector
 * @property string      $container_name
 * @property int         $disabled_for_all
 * @property int|null    $user_id
 * @property int         $created_by
 * @property string      $created_at
 * @property string      $updated_at
 * @property int         $state
 * @property User        $user
 * @property User        $creator
 *
 * Virtual properties (not stored in DB):
 * @property-read string $username     Display name of the assigned user, or empty string.
 * @property-read string $cssSelector  Resolved CSS/jQuery selector string.
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
            [['disabled_for_all', 'user_id', 'created_by', 'state'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['container_selector', 'container_name'], 'string', 'max' => 255],
            ['user_id', 'required', 'when' => function ($model) {
                return !$model->disabled_for_all;
            }],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'container_selector' => Yii::t('SurfaceModule.base', 'Container Selector'),
            'container_name'     => Yii::t('SurfaceModule.base', 'Container Name'),
            'disabled_for_all'   => Yii::t('SurfaceModule.base', 'Disable for all users'),
            'user_id'            => Yii::t('SurfaceModule.base', 'Specific User'),
            'state'              => Yii::t('SurfaceModule.base', 'State'),
        ];
    }

    // -------------------------------------------------------------------------
    // Serialisation — fields() ensures virtual getters are included in toArray()
    // and therefore appear in the JSON returned to the JS displayExistingRules().
    // -------------------------------------------------------------------------

    /**
     * Columns included by default in toArray() / JSON responses.
     * Keeps the payload minimal — only what the JS actually reads.
     */
    public function fields(): array
    {
        return [
            'id',
            'container_selector',
            'container_name',
            'disabled_for_all',
            'user_id',
            'state',
        ];
    }

    /**
     * Extra fields available via toArray(['username', 'cssSelector']).
     * The JS get-rule-data action should call ->toArray([], ['username'])
     * so that rule.username is present in the JSON for displayExistingRules().
     */
    public function extraFields(): array
    {
        return [
            // Virtual getter getUsername() → exposed as "username"
            'username'    => function () { return $this->getUsername(); },
            // Virtual getter getCssSelector() → exposed as "cssSelector"
            'cssSelector' => function () { return $this->getCssSelector(); },
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    // -------------------------------------------------------------------------
    // Virtual helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the display name of the assigned user, or an empty string when
     * the rule applies to all users.
     *
     * Consumed by JS displayExistingRules() as rule.username.
     * Exposed via extraFields() so it appears in toArray([], ['username']).
     */
    public function getUsername(): string
    {
        if ($this->disabled_for_all || $this->user === null) {
            return '';
        }

        return $this->user->displayName ?? $this->user->username ?? '';
    }

    /**
     * Resolves the stored container_selector to a CSS/jQuery selector string.
     *
     * Mirrors JS resolveSelector() exactly — same three-branch priority order:
     *
     *   contains '.' or '#'  →  pass through as-is   e.g. "div.btn-group.dark-mode"
     *   "id-{value}"         →  "#value"
     *   anything else        →  [data-surface-container="value"]   (legacy fallback)
     *
     * The legacy "class-{x}-{y}" format (dash-joined) cannot be reliably split
     * back into class names, so it falls through to the attribute selector.
     * Those old rows are still hidden because the old JS wrote
     * data-surface-container="class-..." onto the element directly.
     * New saves produce "div.btn-group.dark-mode" and hit the first branch.
     */
    public function getCssSelector(): string
    {
        $selector = $this->container_selector;

        // Branch 1 — new CSS selector format, pass through unchanged
        if (strpos($selector, '.') !== false || strpos($selector, '#') !== false) {
            return $selector;
        }

        // Branch 2 — legacy id- prefix
        if (strpos($selector, 'id-') === 0) {
            return '#' . substr($selector, 3);
        }

        // Branch 3 — legacy fallback (covers old class- prefix rows too)
        return '[data-surface-container="' . addslashes($selector) . '"]';
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    /**
     * Active rules that apply to the given user (used to build the JS
     * setDisabledContainers() payload on page load).
     */
    public static function getActiveRules(?int $userId): array
    {
        return static::find()
            ->where(['state' => 1])
            ->andWhere([
                'or',
                ['disabled_for_all' => 1],
                ['user_id' => $userId],
            ])
            ->all();
    }

    /**
     * Check whether a specific selector is currently disabled for a user.
     */
    public static function isDisabled(string $selector, ?int $userId): bool
    {
        return static::find()
            ->where(['container_selector' => $selector, 'state' => 1])
            ->andWhere([
                'or',
                ['disabled_for_all' => 1],
                ['user_id' => $userId],
            ])
            ->exists();
    }

    /**
     * Raw selector strings for the current user — passed directly to the JS
     * setDisabledContainers() call. The JS resolveSelector() then converts
     * each string to a usable jQuery selector at runtime.
     */
    public static function getDisabledSelectors(?int $userId): array
    {
        return static::find()
            ->select('container_selector')
            ->where(['state' => 1])
            ->andWhere([
                'or',
                ['disabled_for_all' => 1],
                ['user_id' => $userId],
            ])
            ->column();
    }
}