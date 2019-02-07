<?php

namespace aspn\domain;

use aspn\domain\models\Domain;
use aspn\domain\models\DomainEnt;
use aspn\geo\app\MagazineCollection;
use Yii;
use yii\db\ActiveRecord;

/**
 * domain module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */

    public $isDomain = false;
    public $currDomain = null;

   // public $controllerNamespace = 'app\modules\domain\controllers';

    public $offlineStore = false;


    private $def_domain = null;

    public $domainListRules ='all';

    public $url_domain = null;
    public $url = null;



    /**
     * @inheritdoc
     */
    public function init()
    {


        parent::init();
        $this->checkDomain();
        // custom initialization code goes here

    }


    public function domainListDefForUrl(){
        $datadata = [];
        $domain_def = Domain::find()->where(['geo_domain'=>Domain::G_Y]);
        if ($this->domainListRules == 'main'){
             $domain_def->where(['name'=>Yii::$app->params['domain_name']]);
        }

        $domain_def = $domain_def->all();

        foreach ($domain_def as $link){
            $datadata[] = [
                'name'=> $link->name,
                'value'=>$link->id,
            ];
        }

        return $datadata;

    }

    private function checkDomain(){

        $hn = \Yii::$app->request->hostName;

        $domain_name = explode('.',$hn);

        $topdomain = end($domain_name);



        $name = $domain_name[0];

        if ( YII_ENV_DEV ){
            if ( count($domain_name) == 3 ){
                $name = \Yii::$app->params['domain_name'];  //def domain
            }
            $topdomain = Yii::$app->params['topdomain'];
        }




        $this->currDomain = Domain::findOne(['alias'=>$name,'topdomain'=>$topdomain]);

        if ($this->currDomain   !== false ){
            $this->isDomain  = true;

            $magazineCollection = new MagazineCollection();
            if (isset($magazineCollection->magazines[$name])){
                $this->offlineStore = true;
            }
        }


        $this->def_domain = Domain::findOne(['name'=>\Yii::$app->params['domain_name'],'topdomain'=>$topdomain]);


    }

    public function rewriteEnt($type, ActiveRecord $ent){


        $domain_id = $this->getDomainId();

        $vars = DomainEnt::find()->where(['type_basic'=>$type,'id_type_basic'=>$ent->id,'domain_id'=>$domain_id])->all();

        foreach ( $vars as $var){

            if (! isset($ent->{$var->field_basic}) || ! $var->new_value ) continue;

            $ent->{$var->field_basic} = $var->new_value;
        }


    }

    public function getPricePolitics(){
        if ($this->currDomain !== null && $this->currDomain->price_politics){
            return $this->currDomain->price_politics;
        } else {
            return 'site';
        }
    }


    public function rewritePrice($product_id_crm,$price,$type){

        if ($this->currDomain !== null && $this->currDomain->price_politics && $product_id_crm != null/* != 'site'*/ ){
            $new_price = ProductPriceTypeApi::find()->where([
                'product_id'=>$product_id_crm,
                'price_politics'=>$this->currDomain->price_politics,
                'type'=>$type
                ])
                ->one();
            //->createCommand()->rawSql;

            //ex($new_price);




            if ($new_price !== null){
                return $new_price->price;
            }


            $new_price = ProductPriceTypeApi::findOne([
                'product_id'=>$product_id_crm,
                'price_politics'=>'site',
                'type'=>$type
            ]);

            if ($new_price !== null){
                return $new_price->price;
            }
        }
        return $price;
    }

    public function rewritePriceC($product_id_crm,$price,$type){

        if ($this->currDomain !== null && $this->currDomain->price_politics && $product_id_crm != null ){
            if (isset( Yii::$app->params['price_cache'][$product_id_crm . '_' . $this->currDomain->price_politics . '_' . $type] ) ){
                return Yii::$app->params['price_cache'][$product_id_crm . '_' . $this->currDomain->price_politics . '_' . $type];
            }
            if (isset( Yii::$app->params['price_cache'][$product_id_crm . '_site_' . $type] ) ){
                return Yii::$app->params['price_cache'][$product_id_crm . '_site_' . $type];
            }
        }


        return $price;

    }



    public function getDomainList(){
         $domain = Domain::find()->all();
         return $domain;
    }

    public function getDomainId(){
        if ($this->currDomain !== null){

            return $this->currDomain->id;
        }
        return null;
    }

    public function getDomainName(){
        if ($this->currDomain !== null){
            return $this->currDomain->name;
        }
        return null;
    }

    public function getDomainTopName(){
        if ($this->currDomain !== null){
            return $this->currDomain->topdomain;
        }
        return null;
    }


    public function getAlias(){
        if ($this->currDomain !== null){
            return $this->currDomain->alias;
        }
        return null;
    }



    public function isActive(){
        if ($this->currDomain !== null){
            return (boolean)  $this->currDomain->publish == Domain::P_OK;
        }
        return false;
    }

    public function isDefDomain(){
//upd add to table domain flag  -def_domain
        return (boolean) $this->isActive() && ($this->getDomainName() == Yii::$app->params['domain_name']) && ($this->getDomainTopName() == 'ru');
    }

    public function getHostName(){
        if ($this->isDefDomain()){
            return  Yii::$app->params['domain_name'] . '.' .$this->getDomainTopName() ;
        } else {
            return  $this->getDomainName() . '.' . Yii::$app->params['domain_name'] . '.' .$this->getDomainTopName() ;
        }
    }

    public function getDefDomain(){
        return $this->def_domain;
    }

    public function linksByUrl($url_id){
        $alldom = Domain::find()->all();
        foreach ($alldom as $dom){
            $l = new UrlDomain();
            $l->url_id = $url_id;
            $l->domain_id = $dom->id;
            $l->save();
        }
    }




}
