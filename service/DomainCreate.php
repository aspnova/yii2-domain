<?php
/**
 * Created by PhpStorm.
 * User: o.trushkov
 * Date: 01.08.18
 * Time: 15:02
 */

namespace app\modules\domain\service;


use app\modules\comment\services\CommentGeo;
use app\modules\domain\models\Domain;
use app\modules\domain\models\DomainEnt;
use app\modules\helper\models\Helper;
use app\modules\product\service\ProductGenerateCombItemsForKitsByCatAllDomains;
use app\modules\product\service\ProductUniq;
use app\modules\url\models\Url;
use app\modules\url\models\UrlDomain;
use app\modules\varentity\models\Conditions;
use yii\httpclient\Client;
use Yii;

class DomainCreate
{

    public  $logs=[];

    public function addDomain($model){

        if (! $model->save()){
            return null;
        }


        $this->addUrl($model->id);

       // $this->addConditions($model->name,$model->alias);


        $prodUniq = new ProductUniq();
       // $prodUniq->initProdUniqByDomain($model);


        if ($model->geo_domain == Domain::G_Y){
            $commentGen = new CommentGeo();
            $commentGen->addGeo($model);
        }



        $this->genLm($model->id);

       // $this->checkUrl($model);



        $this->genKitByComb($model->id);
        $this->genRelByComb($model->id);


        return true;
    }


    private function genKitByComb($domain_id){

        $settings = \Yii::$app->getModule('settings');
        $cat_ex = [];

        $list_product_cat_by_comb =  $settings->getVar( 'list_product_cat_by_comb');

        if ($list_product_cat_by_comb){
            $cat_ex = explode(',',$list_product_cat_by_comb);
        }

        if (! count($cat_ex)){
            return;
        }


        $cmd = '; /opt/php72/bin/php yii product_console/product/generatekitbycatallexs "'.implode(',',$cat_ex).'" '.$domain_id.'';

        Helper::runConsole('cd ' . Yii::$app->params['path_env'].$cmd);

        /*
        $app = new  ProductGenerateCombItemsForKitsByCatAllDomains($cat_ex);
        $app->gc->k = 4;
        $app->domains_ids = [$domain_id];
        $app->gen_all_exs();
        */

    }

    private function genRelByComb($domain_id){

        $settings = \Yii::$app->getModule('settings');
        $cat_ex = [];

        $list_product_cat_by_comb =  $settings->getVar('list_product_cat_by_rel');

        if ($list_product_cat_by_comb){
            $cat_ex = explode(',',$list_product_cat_by_comb);
        }

        if (! count($cat_ex)){
            return;
        }

        $cmd = '; /opt/php72/bin/php yii product_console/product/generaterelbycatallexs "'.implode(',',$cat_ex).'" '.$domain_id.'';

        Helper::runConsole('cd ' . Yii::$app->params['path_env'].$cmd);

        /*
        $app = new  ProductGenerateCombItemsForKitsByCatAllDomains($cat_ex);
        $app->gc->k = 4;
        $app->domains_ids = [$domain_id];
        $app->gen_all_exs();
*/


    }




    public function checkUrl($model){
        //check url
        $url = 'https://' . $model->alias . '.' . Yii::$app->params['domain'];
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('get')
            ->setUrl($url)
            ->send();
    }


    public function addConditions($name,$alias){
        $cond_dom = new Conditions();
        $cond_dom->name = 'домен ' . $name;
        $cond_dom->hash = 'domain='. $alias;
        $cond_dom->save();
    }

    public function addUrl($id){
        $domain = Domain::findOne(['id'=>$id]);
        if ($domain !== null){
            $urls = Url::find()
                ->andWhere([ '!=' ,'type_url','compare'])
                ->andWhere([ '!=' ,'type_url','blogcat'])
                ->andWhere([ '!=' ,'type_url','blog'])
                ->andWhere([ '!=' ,'type_url','brand'])
                ->andWhere([ '!=' ,'type_url','brandcat'])
                ->andWhere([ '!=' ,'action','allbrands'])
                ->andWhere([ '!=' ,'type_url','servcenter'])
                ->all();

            $nums = 0;

            foreach ($urls as $url){

                if ( ! (
                    is_object($url) &&
                    (   $url->type_url == 'catalog' ||
                        $url->type_url == 'textpage' ||
                        $url->type_url == 'action' ||
                        $url->type_url == 'geo' ||
                        $url->type_url == 'product' ||
                        $url->type_url == 'basket'
                    )
                    /*
                    &&
                    (
                        $url->condition_id == 0  ||
                        $url->condition_id == 8
                    )
                    */
                    )
                )
                {
                    continue;
                }

                $l = new UrlDomain();
                $l->url_id = $url->id;
                $l->domain_id = $domain->id;
                if ( $l->save()){
                    $nums ++;
                }

            }
            if ($nums){
                $this->logs['addUrl'] = 'Добавлено url ' . $nums;
                //Yii::$app->session->setFlash('danger', 'Добавлено url ' . $nums);
               return $nums;
            }



        }
        return null;
    }

    public function genLm($id){
        $domain = Domain::findOne(['id'=>$id]);

        $count_update = 0;
        if ($domain !== null){

            //textpage
            //catalog

            $links_catalog = UrlDomain::find()->where(['domain_id'=>$domain->id])->all();

            foreach ($links_catalog as $link){
                $url = $link->url_r;
                if ( is_object($url) && (

                    $url->type_url == 'catalog' ||
                    $url->type_url == 'textpage' ||
                    $url->type_url == 'action' ||
                    $url->type_url == 'geo' ||
                    $url->type_url == 'product' ||
                    $url->type_url == 'basket'

                    )
                    //&& $url->condition_id == 0
                ){

                    $this->updateEntDomain($domain->id,['id_type_basic'=>$link->url_id,
                        'type_basic'=>'url','field_basic'=>'last_mod'],$domain->date_publish_start);
                    $count_update  ++;
                }
            }

        }

        $this->logs['genLm'] = 'last_mod обновлено ' .$count_update;


        //Yii::$app->session->setFlash('info', 'last_mod обновлено ' .$count_update);

        return $count_update;
    }


    public function updateEntDomain($domain_id,
                                    $ent_info=['id_type_basic'=>0,'type_basic'=>'product',
                                        'field_basic'=>'count_order'],$newval){

        $de = DomainEnt::find()->where(['domain_id'=>$domain_id]+$ent_info)->one();

        if ( $de === null){

            $de = new DomainEnt();
            $de->domain_id = $domain_id;
            $de->id_type_basic = $ent_info['id_type_basic'];
            $de->type_basic =  $ent_info['type_basic'];
            $de->field_basic =  $ent_info['field_basic'];
            $de->new_value = (string)$newval;
            if (! $de->save()){
                // ex($de->getErrors());
            }

        } else {
            $de->new_value = (string)$newval;
            $de->update(false,['new_value']);
        }


    }


}