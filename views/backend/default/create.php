<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\domain\models\Domain */

$this->title = 'Добавить';
$this->params['breadcrumbs'][] = ['label' => 'Поддомены', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="domain-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
