<?php

use yii\db\Migration;

class m241215_000001_surface extends Migration
{
    public function safeUp()
    {
        $this->createTable('surface_rule', [
            'id' => $this->primaryKey(),
            'container_selector' => $this->string(255)->notNull(),
            'container_name' => $this->string(255),
            'disabled_for_all' => $this->tinyInteger(1)->defaultValue(0),
            'user_id' => $this->integer()->null(),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);

        $this->createIndex('idx_surface_selector', 'surface_rule', 'container_selector');
        $this->createIndex('idx_surface_user', 'surface_rule', 'user_id');

        $this->addForeignKey(
            'fk_surface_rule_user',
            'surface_rule',
            'user_id',
            'user',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_surface_rule_creator',
            'surface_rule',
            'created_by',
            'user',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_surface_rule_user', 'surface_rule');
        $this->dropForeignKey('fk_surface_rule_creator', 'surface_rule');
        $this->dropTable('surface_rule');
    }
}