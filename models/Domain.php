<?php

namespace aspnova\domain\models;


use Yii;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * This is the model class for table "domain".
 *
 * @property integer $id
 * @property string $name
 * @property integer $type_domain
 * @property integer $publish
 * @property string $date_publish_start
 * @property string $alias
 * @property string $gtm_head
 * @property string $gtm_body
 * @property string $ya_ver
 * @property string $go_ver
 * @property string $status
 * @property string $price_politics
 * @property string $geo_domain
 * @property string $validation
 * @property string $topdomain
 *
 *
 */
class Domain extends \yii\db\ActiveRecord
{

    const ST_A_O = 1;
    const ST_A_NO = 0;

    const P_OK = 0;
    const P_NO = 1;

    const G_Y = 0;
    const G_N = 1;

    const V_Y = 0;
    const V_N = 1;

    public static  $arrTxtStatus = [ self::ST_A_O => 'api есть', self::ST_A_NO =>'api нету'];

    public static  $arrTxtStatusGeo = [ self::G_Y => 'да', self::G_N => 'нет'];
    public static  $arrTxtValidation = [ self::V_Y => 'да', self::V_N => 'нет'];

    public static  $arrTxtPublish = [ self::P_OK => 'Опубликован', self::P_NO =>'Неопубликован'];

    public $count_domains;
    public $count_scripts;
    public $count_url;


    public function beforeSave($insert)
    {
        $this->convertDateToDatetime();
        return parent::beforeSave($insert);
    }



    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'domain';
    }


    public function convertDateToDatetime(){
        //12-04-2017
        //YYYY-MM-DD HH:MM:SS
        $a = ['date_publish_start'];
        foreach ($a as $item){
            $phpdate = strtotime( $this->{$item} );
            $mysqldate = date( 'Y-m-d H:i:s', $phpdate );
            $this->{$item} = $mysqldate;
        }
    }



    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [

         //   [['name','alias'], 'unique'],
            [['name','alias','topdomain'], 'required'],
            [['publish','status','geo_domain','validation'], 'integer'],
            [['date_publish_start'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['alias','deliv_main_city_min','deliv_main_city_max',
                'deliv_sub_city_min','deliv_sub_city_max','price_politics','topdomain'], 'string', 'max' => 45],
            [['publish'], 'default', 'value'=>0],
            [['topdomain'], 'default', 'value'=>'ru'],


            [['date_publish_start'], 'default', 'value'=>''],

            [['price_politics'], 'default', 'value'=>'site'],

            [['name'], 'string', 'max' => 255],


            [['gtm_head','gtm_body','ya_ver','go_ver'], 'string', 'max' => 1024],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название (трансл.)',
            'topdomain' => 'Type Domain (ru,kz)',
            'publish' => 'Открыт',
            'alias' => 'Псевдоним',
            'date_publish_start' => 'Дата публикации',
            'deliv_main_city_min' => 'Срок доставки в центральный город домена min',
            'deliv_main_city_max' => 'Срок доставки в центральный город домена max',
            'deliv_sub_city_min' => 'Срок доставки в областной город домена min',
            'deliv_sub_city_max' => 'Срок доставки в областной город домена max',
            'status' => 'Статус метрик',
            'price_politics' => 'Ценовая политика',
            'geo_domain' => 'Geo домен',
            'validation' => 'Валидация',

        ];
    }


    public function make_validation_geo(){

        $geo = Yii::$app->getModule('geo');
        $reqestUrl = $this->alias . '.' . Yii::$app->params['domain'] .'/';

        $res = $geo->getGeoDataApi($reqestUrl);

        if (! $res ) return false;
        try {
            $res = Json::decode( $res,true);
        } catch (\Exception $e) {
            return false;
        }
        return true;

    }


    public function afterDelete()
    {

        foreach ( Scripts::findAll(['domain_id'=>$this->id]) as $item ){
            $item->delete();
        }
        foreach ( Conditions::find()->andWhere([ 'like' , 'hash',$this->alias])->all() as $item ){
            $item->delete();
        }
        foreach ( UrlDomain::find()->andWhere([ 'domain_id' =>$this->id])->all() as $item ){
            $item->delete();
        }

        foreach ( Comments::find()->andWhere([ 'domain_id' =>$this->id])->all() as $item ){
            $item->delete();
        }

        foreach ( ProductKit::find()->andWhere([ 'domain_id' =>$this->id])->all() as $item ){
            $item->delete();
        }

        foreach ( ProductRelevantSet::find()->andWhere([ 'domain_id' =>$this->id])->all() as $item ){
            $item->delete();
        }

        foreach ( ProductRecomendSet::find()->andWhere([ 'domain_id' =>$this->id])->all() as $item ){
            $item->delete();
        }

        parent::afterDelete(); // TODO: Change the autogenerated stub
    }
}
