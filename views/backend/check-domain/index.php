<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\modules\varentity\models\Varentity;
/* @var $this yii\web\View */
/* @var $searchModel app\modules\domain\models\DomainSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Сео гео';
$this->params['breadcrumbs'][] = $this->title;
?>
<style type="text/css">
    .bs-example{
        margin: 20px;
    }
    .panel-title .glyphicon{
        font-size: 14px;
    }
</style>
<div class="domain-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>
    <div class="well well-lg">
        <p><span class="label label-primary checkedBtn">метатеги</span> у этого товара нет ГЕО метатегов (Title, description, keywords)</p>
        <p><span class="label label-success checkedBtn">гео-теги</span>  у этих товаров (осн и доп) нет ГЕО-абзацев в описании</p>
        <p><span class="label label-warning checkedBtn">все-отзывы</span> на этом товаре нет СЕО-страницы отзывов </p>
        <p><span class="label label-info checkedBtn">склонения</span> на этом товаре нет склонения </p>
        <p><span class="label label-danger checkedBtn">комплект</span> нету картинки 104_93 (комплект) </p>

    </div>

    <?php 
    Yii::$app->params['domain_set'] =
        \yii\helpers\ArrayHelper::map(\app\modules\domain\models\Domain::find()->all(),'id','name');
    ?>


    <div class="panel-group" id="accordion">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne"><span class="glyphicon glyphicon-plus"></span> 1. СЕО-параметры</a>
                </h4>
            </div>
            <div id="collapseOne" class="panel-collapse collapse in">
                <div class="panel-body">
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],


                            //     'id',
                            [
                                'attribute'=>'name',
                                   'label'=>'Продукт',
                                'format' => 'raw',
                                'value' => function($model) {

                                    return Html::a(

                                        $model['name'],
                                        \yii\helpers\Url::to(['/admin/product/default/update' ,'id'=>$model['id']]) ,
                                        [
                                            'style'=>'    text-transform: uppercase;',
                                            'target'=>'_blank'

                                            /* 'data-pjax' => '0' , */]
                                    );
                                },
                                /*     'filter' => Html::textInput('ProductSearch[name]',
                                         (isset(Yii::$app->request->queryParams['ProductSearch']['name'] )) ?
                                             Yii::$app->request->queryParams['ProductSearch']['name']  : ''
                                         ,[ 'class'=>'form-control']),
                                */

                            ],
                            /*
                                        [
                                            'attribute'=>'debug',
                                            'value'=> function($model){
                                                    if (is_array($model['debug'])){
                                                        return implode("",$model['debug']) ;
                                                    }
                                                    return $model['debug'];

                                            }
                                        ]
                            ,*/
                            [
                                'attribute'=>'Проверка',
                                'format'=>'raw',
                                'value' => function($model) {
                                    $str = '';

                                    if ($model['checkMetaTags'] == false){
                                        $link = ['/admin/varentity/default/create',
                                            'type_basic'=>'url'
                                            ,
                                            'id_type_basic'=>$model['product']->url_rr->id
                                        ];
                                        $str .=  '<span class="label label-primary checkedBtn">'.
                                            Html::a('метатеги' , \yii\helpers\Url::to($link),['target'=>'_blank','style'=>'color:white'])
                                            .
                                            '</span>';


                                    }
                                    if ($model['geo-tag']['geotag'] == false){
                                        $link = ['/admin/varentity/default/create'];
                                        if ($model['type'] == 'dop'){
                                            $link[ 'type_basic'] = 'prod_descr_dop_l_by_cat';
                                            $link[ 'id_type_basic'] = $model['product']->cat_id;

                                        }
                                        if ($model['type'] == 'main'){
                                            $link[ 'type_basic'] = 'prod_descr_main_l_by_cat';
                                            $link[ 'id_type_basic'] = $model['product']->cat_id;


                                            if (  ! ($model['geo-tag']['w1']   )){
                                                $link[ 'type_basic'] = 'prod_descr_main_f_by_cat';
                                                $link[ 'id_type_basic'] = $model['product']->cat_id;

                                            }elseif (  ! ($model['geo-tag']['w2']  )) {
                                                $link[ 'type_basic'] = 'prod_descr_main_l_by_cat';
                                                $link[ 'id_type_basic'] = $model['product']->cat_id;

                                            }

                                        }
                                        $str .= '<span  class="label label-success checkedBtn">'.
                                            Html::a('гео-теги' , \yii\helpers\Url::to($link),['target'=>'_blank','style'=>'color:white'])
                                            .
                                            '</span>';

                                    }

                                    if ($model['seopage'] == false){

                                        $link = ['/admin/comment/default/product-comment','id'=>$model['product']->id];

                                        $str .= '<span  class="label label-warning checkedBtn">'.
                                            Html::a('все-отзывы' , \yii\helpers\Url::to($link),['target'=>'_blank','style'=>'color:white'])
                                            .
                                            '</span>';

                                    }

                                    if ($model['nonamevar'] == false){

                                        $link = ['/admin/product/default/update','id'=>$model['product']->id];

                                        $str .= '<span  class="label label-info checkedBtn">'.
                                            Html::a('Нет склонения' , \yii\helpers\Url::to($link),['target'=>'_blank','style'=>'color:white'])
                                            .
                                            '</span>';

                                    }

                                    if ($model['img_104'] == false){

                                        $link = ['/admin/product/default/update','id'=>$model['product']->id];

                                        $str .= '<span  class="label label-danger checkedBtn">'.
                                            Html::a('Нет картинки 104_93' , \yii\helpers\Url::to($link),['target'=>'_blank','style'=>'color:white'])
                                            .
                                            '</span>';

                                    }


                                    return $str;

                                },

                                'filter' => Html::dropDownList('typecheck',

                                    (isset(Yii::$app->request->queryParams['typecheck'] )) ?
                                        Yii::$app->request->queryParams['typecheck']  : '',

                                    ['checkMetaTags'=>'метатеги','geotag'=>'гео-теги','seopage'=>'все отзывы',
                                        'nonamevar'=>'склонения','img_104'=>'комплект',
                                        ],

                                    ['prompt'=>'нет', 'class'=>'form-control']
                                ),


                            ],
                            /*  [
                                  'attribute'=>'Контент',
                                  'format'=>'raw',
                                  'value' => function($model) {
                                         foreach ( Yii::$app->params['domain_set'] as $domain){

                                          }
                                  }
                              ]
                            */


                        ],
                    ]); ?>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo"><span class="glyphicon glyphicon-minus"></span> 2. Контент</a>
                </h4>
            </div>
            <div id="collapseTwo" class="panel-collapse collapse">
                <div class="panel-body">
                    <?= GridView::widget([
                        'dataProvider' => $dataProviderC,
                        'filterModel' => $searchModelC,
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],


                            //     'id',
                            [
                                'attribute'=>'name',
                                'label' => 'Название'

                            ],

                            [
                                'attribute'=>'Проверка',
                                'format'=>'raw',
                                'value' => function($model) {

                                    $str = '<ul>';
                                    foreach ($model['check'] as  $id => $var){

                                        $str .= '<li>';

                                        $link = ['/admin/varentity/default/update/','id'=>$var->id];

                                        $label  = ' ' . Varentity::$arrTxtType[ $var->type_basic ];


                                        $str .=  '<span class="label label-default ">'.
                                            Html::a( $var->descrVar(), \yii\helpers\Url::to($link),
                                                ['target'=>'_blank','style'=>'color:white']).
                                            '</span>';


                                        $str .= $label;






                                        $str .=  Html::a( ' ' . $var->field_basic, \yii\helpers\Url::to($link),
                                            ['target'=>'_blank']);



                                        $str .= '</li>';


                                    }
                                    $str .= '</ul>';

                                    return $str;

                                },




                            ],


                        ],
                    ]); ?>
                </div>
            </div>
        </div>

    </div>





</div>
<script>
    $(document).ready(function(){
        // Add minus icon for collapse element which is open by default
        $(".collapse.in").each(function(){
            $(this).siblings(".panel-heading").find(".glyphicon").addClass("glyphicon-minus").removeClass("glyphicon-plus");
        });

        // Toggle plus minus icon on show hide of collapse element
        $(".collapse").on('show.bs.collapse', function(){
            $(this).parent().find(".glyphicon").removeClass("glyphicon-plus").addClass("glyphicon-minus");
        }).on('hide.bs.collapse', function(){
            $(this).parent().find(".glyphicon").removeClass("glyphicon-minus").addClass("glyphicon-plus");
        });
    });
</script>