<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use app\modules\product\models\ProductPriceTypeApi;
/* @var $this yii\web\View */
/* @var $model app\modules\domain\models\Domain */
/* @var $form yii\widgets\ActiveForm */
use app\modules\domain\models\Domain;
?>

<div class="domain-form row">

    <div class="col-xs-12">
        <?php $form = ActiveForm::begin(); ?>

        <?= $form->errorSummary($model); ?>

        <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'alias')->textInput(['maxlength' => true])->hint('moscow->moskva') ?>

        <?= $form->field($model, 'topdomain')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'deliv_main_city_min')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'deliv_main_city_max')->textInput(['maxlength' => true]) ?>


        <?= $form->field($model, 'deliv_sub_city_min')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'deliv_sub_city_max')->textInput(['maxlength' => true]) ?>



        <?php
        $price_politics = ArrayHelper::getColumn( ProductPriceTypeApi::find()->groupBy('price_politics')->all(),
            function ($data){
                return trim( $data->price_politics);
            });

        echo  $form->field($model, 'price_politics')->dropDownList(
                array_combine($price_politics,$price_politics) + ['site'=>'site']
               ,['prompt'=>'']
        ) ?>

        <?php
        if ($model->isNewRecord ){
            $model->date_publish_start = time();  Yii::$app->formatter->asDate( time() , "d/m/Y");
        }
        echo $form->field($model, 'date_publish_start')->widget(

            \yii\jui\DatePicker::class, [
            'language' => 'ru',
            'dateFormat' => 'php:d-m-Y',
            'options'=>[
                'class'=>'form-control datepicker',
            ]
        ]);
        ?>

        <?php
        if  ( $model->isNewRecord) {
            $model->publish = Domain::P_OK;
            echo $form->field($model, 'publish')->radioList ( Domain::$arrTxtPublish );
        } else {
            echo $form->field($model,'publish')->radioList( Domain::$arrTxtPublish,
                ['value'=> $model->publish ]  );
        }
        ?>

        <?php
        if  ( $model->isNewRecord) {
            $model->status = Domain::ST_A_NO;
            echo $form->field($model, 'status')->radioList ( Domain::$arrTxtStatus );
        } else {
            echo $form->field($model,'status')->radioList( Domain::$arrTxtStatus,
                ['value'=> $model->status ]  );
        }
        ?>

        <?php
        if ($model->geo_domain === null){
            $model->geo_domain = 0;
        }
        if ($model->validation === null){
            $model->validation = 0;
        }
        ?>

        <?= $form->field($model, 'geo_domain')->radioList( Domain::$arrTxtStatusGeo ) ?>

        <?= $form->field($model, 'validation')->radioList( Domain::$arrTxtValidation ) ?>

        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? 'Добавить' : 'Обновить', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>

        <?php
        echo  '<div>' . $model->log . '</div>';

        ?>

        <?php ActiveForm::end(); ?>
    </div>

    <?php if ( ! $model->isNewRecord )  { ?>
    <div class="col-xs-12">
        <form method="get" action="<?= \yii\helpers\Url::to(['/admin/domain/default/api-create'])?>">
            <input type="hidden" name="domain_id" value="<?=$model->id?>">
            <input class="btn btn-success" type="submit" value="api.обновить">
        </form>
    </div>

    <?php  } ?>

</div>

<?php
    if ( ! $model->isNewRecord ){
       // echo $this->render('_apicommand',['model'=>$model]);
    }
?>
