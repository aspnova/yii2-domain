<?php

namespace aspn\domain\models;

use Yii;

/**
 * This is the model class for table "domain_ent".
 *
 * @property int $id
 * @property int $domain_id
 * @property int $id_type_basic
 * @property string $type_basic
 * @property string $field_basic
 * @property string $new_value
 */
class DomainEnt extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'domain_ent';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['domain_id', 'id_type_basic'], 'integer'],
            [['type_basic', 'field_basic'], 'string', 'max' => 45],
            [['new_value'], 'string', 'max' => 127],

            [
                ['domain_id', 'id_type_basic','type_basic','field_basic'],'unique',
                'comboNotUnique' => 'Запись такая уже существует',
                'targetAttribute' => ['domain_id', 'id_type_basic','type_basic','field_basic']]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'domain_id' => 'Domain ID',
            'id_type_basic' => 'Id Type Basic',
            'type_basic' => 'Type Basic',
            'field_basic' => 'Field Basic',
            'new_value' => 'New Value',
        ];
    }
}
