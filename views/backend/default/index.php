<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\modules\domain\models\Domain;
/* @var $this yii\web\View */
/* @var $searchModel app\modules\domain\models\DomainSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Домены';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="domain-index row">
    <div class="col-xs-12">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>
    <div class="col-xs-12">
        <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
        <?php //echo Html::a('Check Geo', ['check-geo'], ['class' => 'btn btn-default']) ?>
    </div>

    <div class="col-xs-12">
        <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            //'id',

            [
                'attribute'=>'name',
                //   'label'=>'Params',
                'format' => 'raw',
                'value' => function($model) {

                    return Html::a(

                        $model->name,
                        \yii\helpers\Url::to(['update' ,'id'=>$model->id]) ,
                        [
                            'style'=>'    text-transform: uppercase;'
                            /* 'data-pjax' => '0' ,'target'=>'_blank' */]
                    );
                },

            ],
            /*'deliv_main_city',
            'deliv_sub_city',*/
            'alias',
            [
                'attribute'=>'date_publish_start',
                'format' =>  ['date', 'dd.MM.Y'],
                'value' => function($model) {
                    return $model->date_publish_start;
                },

            ],
            [
                'label'=>'Состояние',
                'format'=>'raw',
                'value'=>function ($model){
                    $colors = ['primary','success','info','warning','danger','default'];
                    $urls = \app\modules\url\models\UrlDomain::find()->where(['domain_id'=>$model->id])->count();
                    $count_comments = \app\modules\comment\models\Comments::find()
                        ->where(['domain_id'=>$model->id])->count();

                    $w = ''; $g = '';
                    if ($model->validation == Domain::V_N){
                        $w = '<div class="col-xs-4">
                            <button style="margin-left: 0.8em;margin-top: 0.5em" class="btn btn-xs btn-danger" type="button">
                            валидация '.  Domain::$arrTxtValidation[ $model->validation ] .'
                           </button>
                        </div>';
                    }
                    if ($model->geo_domain == Domain::G_Y){
                        $g = '<div class="col-xs-4">
                            <button style="margin-left: 0.8em;margin-top: 0.5em" class="btn btn-xs btn-default" type="button">
                            гео поддомен </button>
                        </div>';
                    }

                    $s = '<div class="row">
                        <div class="col-xs-4">
                            <a href="/admin/comment/default/generate-geo?select_domain='.$model->id.'&red=domain">
                            <button style="margin-left: 0.8em;margin-top: 0.5em" class="btn btn-xs btn-success" type="button">
                            geo-отзывы <span class="badge">'.$count_comments.'</span>
                            </button></a>
                        </div>
                        <div class="col-xs-4">
                            <button style="margin-left: 0.8em;margin-top: 0.5em" class="btn btn-xs btn-info" type="button">
                            скрипты <span class="badge">'.$model->count_scripts.'</span></button>
                        </div>
                        <div class="col-xs-4">
                            <button style="margin-left: 0.8em;margin-top: 0.5em" class="btn btn-xs btn-warning" type="button">
                            url <span class="badge">'.$urls.'</span></button>
                        </div>
                         <div class="col-xs-4">
                            <button style="margin-left: 0.8em;margin-top: 0.5em" class="btn btn-xs btn-primary" type="button">
                            api <span class="badge">'.  Domain::$arrTxtStatus[ $model->status ] .'</span></button>
                        </div>
                        
                         '.$g . $w.'
                        
                    </div>';

                        return $s;
                }
            ],
            [
                'label'=>'Операции',
                'format'=>'raw',
                'headerOptions'=>['style'=>'width:180px'],
                'value'=>function ($model){
                    if (is_object($model)) {

                        $ret = '<div class="btn-group" style="" role="group" aria-label="Операции">';

                        $url = \yii\helpers\Url::to(['delete','id'=>$model->id]);
                        $ret .= ' <div class="btn-group" role="group">';
                        $ret .= '<a href="'.$url.'" 
                                    title="Удалить" class="btn btn-default" aria-label="Удалить" 
                                    data-method="POST"
                                    data-pjax="0" 
                                    data-confirm="Вы уверены, что хотите удалить этот элемент?" >
                                    <span class="glyphicon glyphicon-trash"></span></a>';
                        $ret .= '</div>';


                        $url = \yii\helpers\Url::to(['gen-lm','id'=>$model->id]);
                        $ret .= ' <div class="btn-group" role="group">';
                        $ret .= '<a href="'.$url.'" 
                                    title="Сгенерировать last_modifidet для страниц - не продуктов, for  url`s" class="btn btn-default" aria-label="Добавить urls" 
                                    data-method="POST"
                                    data-pjax="0" 
                                    >
                                    <span class="glyphicon glyphicon-hourglass"></span></a>';
                        $ret .= '</div>';


                        $url = \yii\helpers\Url::to(['add-url','id'=>$model->id]);
                        $ret .= ' <div class="btn-group" role="group">';
                        $ret .= '<a href="'.$url.'" 
                                    title="Добавить urls" class="btn btn-default" aria-label="Добавить urls" 
                                    data-method="POST"
                                    data-pjax="0" 
                                    >
                                    <span class="glyphicon glyphicon-plus"></span></a>';
                        $ret .= '</div>';

                        $url = \yii\helpers\Url::to(['remove-url','id'=>$model->id]);
                        $ret .= ' <div class="btn-group" role="group">';
                        $ret .= '<a href="'.$url.'" 
                                    title="Удалить urls" class="btn btn-default" aria-label="Удалить urls" 
                                    data-method="POST"
                                    data-pjax="0" 
                                    data-confirm="Вы уверены, что хотите удалить этоти элементы?" >
                                    <span class="glyphicon glyphicon-minus"></span></a>';
                        $ret .= '</div>';


                        $ret .= ' <div class="btn-group" role="group">';
                        $url = \yii\helpers\Url::to(['update','id'=>$model->id]);
                        $ret .= '<a
                        href="'.$url.'"
title="Обновить" data-idimg="'.$model->id.'"  class="updateImg btn btn-default"
 aria-label="Обновить" data-pjax="0" 
><span class="glyphicon glyphicon-edit"></span></a>';
                        $ret .= '</div>';




                        $ret .= ' <div class="btn-group" role="group">';
                        $url = \yii\helpers\Url::to(['gen-run','id'=>$model->id]);
                        $ret .= '<a
                        href="'.$url.'"
title="Запустить ген. блоков" data-idimg="'.$model->id.'"  class="updateImg btn btn-default"
 aria-label="Обновить" data-pjax="0" 
><span class="glyphicon glyphicon-cog"></span></a>';
                        $ret .= '</div>';




                        $ret .= ' <div class="btn-group" role="group">';
                        $url = \yii\helpers\Url::to(['gen-clear','id'=>$model->id]);
                        $ret .= '<a
                        href="'.$url.'"
title="Удалить все блоки ген. блоков" data-idimg="'.$model->id.'"  class="deleteBlocks btn btn-default"
 aria-label="Удалить все блоки ген. блоков" data-pjax="0" ><span class="glyphicon glyphicon-unchecked"></span></a>';
                        $ret .= '</div>';


                        $ret .= '</div>';
                        return $ret;

                    }

                }
            ],
        ],
    ]); ?>
    </div>
</div>
