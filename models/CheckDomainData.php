<?php

namespace aspn\domain\models;

use app\modules\blockdescr\models\BlockDescr;
use app\modules\image\models\ImgLinks;
use app\modules\product\models\Product;
use app\modules\varentity\models\Conditions;
use app\modules\varentity\models\Varentity;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\domain\models\Domain;
use yii\data\ArrayDataProvider;

/**
 * DomainSearch represents the model behind the search form about `app\modules\domain\models\Domain`.
 */
class CheckDomainData
{

    public $data = [];
    public $domain_id;

    public $etal_prod = [];
    public $geoMetaProduct = [];
    public $content_data = [];




    public function getSearchModel(){
        $typecheck = Yii::$app->request->getQueryParam('typecheck', '');
        return ['id' => null, 'typecheck' => $typecheck];
    }

    public function getSearchModelContent(){
        $domain_id = Yii::$app->request->getQueryParam('domain_id', '');
        return ['id' => null, 'domain_id' => $domain_id];
    }
    
    private function makeVars($id_type_basic,$type){
        return  Varentity::find()->where(['type_basic'=>$type,'id_type_basic'=>$id_type_basic])->all();
    }
    
    private function rewriteGr($gr_name,$gr_id,$ent){


        $vars = (int) Varentity::find()->where(['type_basic'=>$gr_name,'id_type_basic'=>$gr_id])->count();

        return $vars;


    }


    public function geoMetaProduct(){

        $varentity = Yii::$app->getModule('varentity');



        $products  =  Product::find()->
        joinWith(['url_rr'=>function($q){$q->domain();}],true,'INNER JOIN')->
        all();


        foreach ($products as $product){

            $prod_stat = [];$checkMetaTags= [];$urlA = true;
            $type = ($product->prod_dop == Product::ST_PROD_DOP) ? 'dop' : 'main';


            if (  is_object($product->url_rr)) {
                $url = $product->url_rr;

                $vars = $this->makeVars($url->id,Varentity::T_Url);
                
 

                if (count($vars)){
                    foreach ($vars as $var){
                            $prod_stat[$var->field_basic] = $var->new_value;
                    }

                }

                $vars = $this->makeVars($product->cat_id,Varentity::T_Url_Cat_Prod);

                if (count($vars)){
                    foreach ($vars as $var){
                        if (isset( $prod_stat[$var->field_basic])){

                        } else {
                            $prod_stat[$var->field_basic] = $var->new_value;
                        }
                    }
                }


                $checkMetaTags =
                    array_key_exists('title',$prod_stat) &&
                    array_key_exists('description_meta',$prod_stat);



            } else{
                $urlA = false;
            }

            $seop = false;
            if (  is_object($product->urlComment_r)) {
                $seop = is_object( $product->urlComment_rr );
            }

            $nonamevar =   (boolean) $product->name_var;





                $blocks = BlockDescr::find()->where(['status'=>BlockDescr::ST_OK,
                'entity_id'=>$product->id,
                'entity_type'=>BlockDescr::BT_Pr])->orderBy('ord')->all();


            /////////
            $last_ind =  count($blocks) - 1;

            $geotag = true;  $w1 = false;
            $w2 = false; $debug = null;



            foreach (  $blocks as $num =>  $block) {

                $w = false;

                $varsB = $this->makeVars($block->id,'prod_descr'); //compose vars by env vars


                if (  ! count($varsB)){

                    if ($product->prod_dop == Product::ST_PROD_DOP){ // дополнительный

                        if ($num == $last_ind){
                            $w = $this->rewriteGr(Varentity::T_Pr_Des_D_L_Cat,$product->cat_id,$blocks[$last_ind]);
                            $geotag = $w;

                        }
                    }

                    if ($product->prod_dop == Product::ST_PROD_MAIN){ // основной

                        if ($num == 0){

                            $vars = Varentity::find()->where(['type_basic'=>Varentity::T_Pr_Des_M_F_Cat,
                                'id_type_basic'=>$product->cat_id])->all();
                            $w = count($vars);

                            $w1 = $w;

                        }

                        if ($num == $last_ind){

                            $vars = Varentity::find()->where(['type_basic'=>Varentity::T_Pr_Des_M_L_Cat,
                                'id_type_basic'=>$product->cat_id])->all();
                            $w = count($vars);

                            $w2 = $w;
                            $debug = 'w1 ' . $w1 . ' w2 ' . $w2 . ' ';

                            if (! ($w1 && $w2)){
                                $geotag = false;
                            }


                        }

                    }

                }

                if (! $w){
                    $varentity->hideFields($block,['desc']);
                }


//end blocks
            }




            $img_104 = ImgLinks::find()->innerJoin('img','img.id=img_links.id_image and img_links.type ="product" and img_links.id_type=:id_type',['id_type'=>$product->id])
                    ->andWhere(['width'=>104])->one() !== null;


            $this->geoMetaProduct[] = [
                'id'=>$product->id,
                'name'=>$product->name,
                'product'=>$product,
                'stat'=>$prod_stat,
                'checkMetaTags'=>$checkMetaTags,'url'=>$urlA,
                'type'=>$type,
                'geo-tag'=>[ 'geotag' => $geotag,'w1'=>$w1 , 'w2'=>$w2 ] ,
                'seopage' => $seop,
                'debug'=> $debug,
                'nonamevar' => $nonamevar,
                'img_104'=>$img_104
            ];


        }



        $typecheck = Yii::$app->request->getQueryParam('typecheck', '');

        $this->geoMetaProduct = array_filter($this->geoMetaProduct, function ($item) use ($typecheck) {

            if (strlen($typecheck) > 0) {
                //ex($item);
                if ($item['checkMetaTags'] == false && $typecheck == 'checkMetaTags'){
                    return true;
                }elseif ( is_array($item['geo-tag']) && ($item['geo-tag']['geotag'] == false ) && $typecheck == 'geotag'){
                    return true;
                }elseif ($item['seopage'] == false && $typecheck == 'seopage'){
                    return true;
                }elseif ($item['nonamevar'] == false && $typecheck == 'nonamevar'){
                    return true;
                }elseif ($item['img_104'] == false && $typecheck == 'img_104'){
                    return true;
                }else{
                    return false;
                }

            } else {
                return true;
            }
        });

        $provider = new ArrayDataProvider([
            'allModels' => $this->geoMetaProduct,
            'pagination' => [
                'pageSize' => 150,
            ],

            'sort' => [
                'attributes' => ['id', 'name'],
            ],
        ]);
        return $provider;


    }


    public function geoContentData(){




        $allDomain = Domain::find()->all();



        foreach ($allDomain as $domain){

            $check = [];

            $cond = Conditions::find()->where(['like','hash',$domain->name])->one();

            if ($cond === null){
                continue;
            }

            $vars = Varentity::find()->where((['condition_id'=>$cond->id]))
                ->orderBy('type_basic')
                ->all();

            /*
            foreach ($vars as $var){
                $check[$var->id] = [
                    $var
                ]; Varentity::$arrTxtType[ $var->type_basic ] . ' ' . $var->descrVar() . ' ' . $var->field_basic;
            }
            */



            $item  = [
                'id'=>$domain->id,
                'name'=>$domain->name,
                'domain'=>$domain,
                'check' => $vars
            ];
            $this->content_data[] = $item;

        }



        $provider = new ArrayDataProvider([
            'allModels' => $this->content_data,
            'pagination' => [
                'pageSize' => 150,
            ],

            'sort' => [
                'attributes' => ['id', 'name'],
            ],
        ]);
        return $provider;


    }

}
