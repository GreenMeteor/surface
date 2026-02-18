<?php

use yii\db\Migration;

class m241219_000001_add_indexes extends Migration
{
    public function safeUp()
    {
        $this->createIndex(
            'idx_surface_rule_lookup',
            'surface_rule',
            ['container_selector', 'disabled_for_all', 'user_id']
        );

        $this->createIndex(
            'idx_surface_disabled_all',
            'surface_rule',
            'disabled_for_all'
        );

        return true;
    }

    public function safeDown()
    {
        $this->dropIndex('idx_surface_rule_lookup', 'surface_rule');
        $this->dropIndex('idx_surface_disabled_all', 'surface_rule');

        return true;
    }
}