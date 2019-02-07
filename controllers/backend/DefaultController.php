<?php

namespace app\modules\domain\controllers\backend;



use app\modules\domain\models\DomainEnt;
use app\modules\domain\service\DomainApiCreate;
use app\modules\domain\service\DomainCreate;

use Yii;
use app\modules\domain\models\Domain;
use app\modules\domain\models\DomainSearch;
use yii\db\Exception;
use yii\db\Transaction;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * Default controller for the `product` module
 */
class DefaultController extends Controller
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
        $searchModel = new DomainSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCheckGeo(){

        if ( YII_ENV_DEV ){

        }
        Helper::runConsole('cd ' . Yii::$app->params['path_env'].'; php yii crone/crone/check-active-domain');
        Helper::runConsole('cd ' . Yii::$app->params['path_env'].'; /opt/php72/bin/php yii crone/crone/check-active-domain');
        Yii::$app->session->setFlash('success','Проверка запущена');
        return $this->redirect('index');
    }

    public function actionGetListFormat($query = null){
        $domains = Domain::find()->all();
        $df = [];

        foreach ($domains as $domain){

            $df[] = [
                                    'name'=> $domain->name,
                                    'value'=>$domain->id,
                                ];
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        return $df ;
    }



    public function actionGenLm($id){
        $domain = new DomainCreate();


        Yii::$app->session->setFlash('info', 'last_mod обновлено: ' . $domain->genLm($id));

        return $this->redirect('index');
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

        if ($model->load(Yii::$app->request->post())) {

            $domain = new DomainCreate();

            $transaction = Yii::$app->db->beginTransaction(Transaction::SERIALIZABLE);

            try {

                if (! $domain->addDomain($model)){

                    $transaction->rollBack();

                } else {
                    $transaction->commit();
                    $msgs = [];
                    foreach ( $domain->logs as $k => $v){
                        $msgs[] = $k.':'.$v;
                    }
                    Yii::$app->session->setFlash('success',$msgs);
                    return $this->redirect(['index', 'id' => $model->id]);
                }



            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }




        }
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionApiCreate(){


        $domain_id  = (int)Yii::$app->request->get('domain_id');
        $domain = Domain::findOne(['id'=>$domain_id]);
        if ($domain === null){
            return $this->redirect('index');
        }



        $s = new DomainApiCreate();
        $s->init_app();
        $s->setDomain($domain);

        $domain->log = '';



//////////////
        $s->counterCreate();
        $s->goalsCreate();

        $s->gooAccountCreate();
        $s->gtmContainerCreate();



        $s->gtmVariablesImport();
        $s->gtmBuiltInVariablesImport();
        $s->gtmContainerTriggersImport();
        $s->gtmContainerTagImport();

        $s->gtmContainerVersionCreate();

        $s->gtmCodeUpdate();

        $s->snippetRefresh();


        $s->createYaSite();
        $s->yaSiteVerify();
        $s->yaAddSiteMap();



        $s->gooSiteAdd();
        $s->gooVerification();
        $s->gooAddSiteMap();



        $domain->update(false,['log']);


        return $this->redirect( \yii\helpers\Url::to("/admin/domain/$domain_id/update"));

    }


    public function actionCheckDomain(){

    }

    public function actionGenRun($id){

        $cmd = '; /opt/php72/bin/php yii product_console/product/generate_kit_rec_rel ' . $id;


        Helper::runConsole('cd ' . Yii::$app->params['path_env'].$cmd);
        Yii::$app->session->setFlash('success','генерация запущена ' . $cmd);
        return $this->redirect('index');
    }

    public function actionGenClear($id){

        $cmd = '; /opt/php72/bin/php yii product_console/product/clear_kit_rec_rel ' . $id;


        Helper::runConsole('cd ' . Yii::$app->params['path_env'].$cmd);
        Yii::$app->session->setFlash('success','генерация запущена ' . $cmd);
        return $this->redirect('index');
    }

    public function actionAddUrl($id){
        $domain = new DomainCreate();
        $domain->addUrl($id);

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



        /*$domainStateServ = new DomainStateEx();
        $domainStateServ->updateAllStates($model);*/


        if ($model->load(Yii::$app->request->post()) ) {

            if (! $model->save()){
                ex($model->getErrors());
            }


            return $this->redirect(['index', 'id' => $model->id]);
        } else {


            //$model->loadStates();
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
