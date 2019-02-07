<?php

use yii\db\Migration;

class m190207_071058_create_table_domain_ent extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%domain_ent}}', [
            'id' => $this->primaryKey()->unsigned(),
            'domain_id' => $this->integer()->unsigned()->notNull()->defaultValue('0'),
            'id_type_basic' => $this->integer()->unsigned()->notNull()->defaultValue('0'),
            'type_basic' => $this->string()->notNull()->defaultValue(''),
            'field_basic' => $this->string()->notNull()->defaultValue(''),
            'new_value' => $this->string()->notNull()->defaultValue(''),
        ], $tableOptions);

        $this->createIndex('domain_ent_u', '{{%domain_ent}}', ['domain_id', 'id_type_basic', 'type_basic', 'field_basic'], true);
        $this->createIndex('domain_ent', '{{%domain_ent}}', ['id_type_basic', 'type_basic', 'field_basic', 'domain_id']);
    }

    public function down()
    {
        $this->dropTable('{{%domain_ent}}');
    }
}
