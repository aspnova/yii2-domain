<?php

namespace app\modules\domain\controllers\backend;


use app\modules\domain\models\CheckDomainData;
use app\modules\domain\models\ProductsGeoSearch;
use Yii;
use app\modules\domain\models\Domain;
use app\modules\domain\models\DomainSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * Default controller for the `product` module
 */
class CheckDomainController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }




    /**
     * Lists all Domain models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CheckDomainData();

      /*  if (Yii::$app->request->get('domain_id')){
            $searchModel->domain_id = (int) Yii::$app->request->get('domain_id');
            $searchModel->geoMetaProduct();
        }*/
       ;

        return $this->render('index', [
            'dataProvider' =>   $searchModel->geoMetaProduct(),
            'searchModel' =>   $searchModel->getSearchModel(),

            'dataProviderC' =>   $searchModel->geoContentData(),
            'searchModelC' =>   $searchModel->getSearchModelContent(),

        ]);
    }




}
