<?php

use yii\db\Migration;

class m190207_071051_create_table_domain extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%domain}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()->defaultValue(''),
            'type_domain' => $this->tinyInteger()->notNull()->defaultValue('0'),
            'publish' => $this->tinyInteger()->notNull()->defaultValue('0'),
            'date_publish_start' => $this->dateTime()->notNull(),
            'alias' => $this->string()->notNull()->defaultValue(''),
            'deliv_main_city_min' => $this->string()->notNull()->defaultValue(''),
            'deliv_main_city_max' => $this->string()->notNull()->defaultValue(''),
            'deliv_sub_city_min' => $this->string()->notNull()->defaultValue(''),
            'deliv_sub_city_max' => $this->string()->notNull()->defaultValue(''),
            'log' => $this->string()->notNull()->defaultValue(''),
            'gtm_head' => $this->string()->notNull()->defaultValue(''),
            'gtm_body' => $this->string()->notNull()->defaultValue(''),
            'go_ver' => $this->string()->notNull()->defaultValue(''),
            'ya_ver' => $this->string()->notNull()->defaultValue(''),
            'status' => $this->tinyInteger()->notNull()->defaultValue('0'),
            'price_politics' => $this->string()->notNull()->defaultValue(''),
            'geo_domain' => $this->tinyInteger()->notNull()->defaultValue('0'),
            'validation' => $this->tinyInteger()->notNull()->defaultValue('0'),
            'topdomain' => $this->string()->notNull()->defaultValue(''),
        ], $tableOptions);

        $this->createIndex('f', '{{%domain}}', ['alias', 'publish', 'status']);
        $this->createIndex('name', '{{%domain}}', 'name');
    }

    public function down()
    {
        $this->dropTable('{{%domain}}');
    }
}
