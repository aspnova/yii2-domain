<?php

namespace app\modules\domain\controllers\frontend;


use app\modules\url\models\Url;
use app\modules\url\models\UrlDomain;
use Yii;
use app\modules\domain\models\Domain;
use app\modules\domain\models\DomainSearch;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * Default controller for the `product` module
 */
class ApiController extends Controller
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
    public function actionListDomain()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $domain = Yii::$app->getModule('domain');
        if(Yii::$app->request->get('secret_key') === 'fdsf34fn34rfafds32'){
            $list = $domain->getDomainList();
            return [ 'response' => ['status'=>'success','data'=>ArrayHelper::getColumn($list,'name')] ,];
        }

        return [ 'response' => ['status'=>'fail','data'=>[]] ,];


    }

    public function actionGetListFormat($query = null){
        $domains = Domain::find()->all();
        $df = [];

        foreach ($domains as $domain){

            $df[] = [
                                    'name'=> $domain->alias,
                                    'value'=>$domain->id,
                                ];
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        return $df ;
    }

    /**
     * Displays a single Domain model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Domain model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Domain();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }


    public function actionCheckDomain(){

    }

    public function actionAddUrl($id){

        $domain = Domain::findOne(['id'=>$id]);
        if ($domain !== null){
            $urls = Url::find()->all();
            $nums = 0;
            foreach ($urls as $url){
                $l = new UrlDomain();
                $l->url_id = $url->id;
                $l->domain_id = $domain->id;
                if ( $l->save()){
                    $nums ++;
                }

            }
            if ($nums){
                Yii::$app->session->setFlash('danger', 'Добавлено ' . $nums);
            }
        }
        return $this->redirect('index');
    }

    public function actionRemoveUrl($id){

        $domain = Domain::findOne(['id'=>$id]);
        if ($domain !== null){
            $nums = UrlDomain::deleteAll(['domain_id'=>$domain->id]);
            if ($nums){
                Yii::$app->session->setFlash('danger', 'Удалено ' . $nums);
            }
        }

        return $this->redirect('index');
    }


    /**
     * Updates an existing Domain model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Domain model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Domain model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Domain the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Domain::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }


}
