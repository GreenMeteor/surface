<?php

use yii\db\Migration;

class m241216_000002_add_state_to_surface_rule extends Migration
{
    public function safeUp()
    {
        $this->addColumn('surface_rule', 'state', $this->tinyInteger(1)->notNull()->defaultValue(1)->after('updated_at'));

        $this->createIndex('idx_surface_state', 'surface_rule', 'state');
    }

    public function safeDown()
    {
        $this->dropIndex('idx_surface_state', 'surface_rule');
        $this->dropColumn('surface_rule', 'state');
    }
}