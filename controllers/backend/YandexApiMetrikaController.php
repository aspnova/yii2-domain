<?php

namespace app\modules\domain\controllers\backend;


use app\modules\domain\service\AuthHandlerYandexMetrika;
use app\modules\url\models\Url;
use app\modules\url\models\UrlDomain;
use Yandex\Metrica\Management\ManagementClient;
use yandex\webmaster\api\webmasterApi;
use Yii;
use app\modules\domain\models\Domain;
use app\modules\domain\models\DomainSearch;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Cookie;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use Yandex\OAuth\OAuthClient;

/**
 * Default controller for the `product` module
 * генерация счетчиков - получение кода
регистрация сайта в яндекс вебмастер
поддверждение прав на сайт

 */
class YandexApiMetrikaController extends Controller
{
    /**
     * @inheritdoc
     */
  /*  public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }*/


  public $clientId = '5f32629241544d1daa32f44720ad9a13';
  public $clientSecret = 'd9eb3b562b754f26926c0b7f57695240';


    public function actions()
    {
        return [
            'auth' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'onAuthSuccess'],
            ],


        ];
    }



    public function actionSummaryStatic(){

        $settings = Yii::$app->getModule('settings');
        $cookies = \Yii::$app->getRequest()->getCookies();
        $yaAccessToken = $cookies->getValue('yaAccessToken');



        $wmApi = webmasterApi::initApi( $yaAccessToken  );

    }


    public function actionIndex(){
        $client = new OAuthClient($this->clientId);
        $state = 'yandex-php-library';
        $client->authRedirect(true, OAuthClient::CODE_AUTH_TYPE, $state);


    }


    public function actionGoogleCallback(){
        $url = 'http://seokeys.it-06.aim/admin/domain/google-api/callback?';
      //  $url = 'http://gradushaus.ru/admin/domain/google-api/callback?';
        $url .= $_SERVER['QUERY_STRING'];
        header("Location: $url",true,301);
        exit;
    }



    public function actionCallback(){
        $session = Yii::$app->session;
        $session->open();


        $settings = Yii::$app->getModule('settings');


        $client = new OAuthClient($this->clientId,$this->clientSecret);

        if ( $session->get('yaAccessToken')) {
            $client->setAccessToken($session['yaAccessToken']);
            $settings->editVar('yaAccessToken',$session['yaAccessToken']);
            Yii::$app->session->setFlash('danger',htmlentities($client->getAccessToken()) );

        }



        if (isset($_REQUEST['code'])) {

            try {
                $client->requestAccessToken($_REQUEST['code']);
            } catch (\Yandex\OAuth\Exception\AuthRequestException $ex) {
                Yii::$app->session->setFlash('warning',$ex->getMessage() );

            }

            if ($client->getAccessToken()) {
                Yii::$app->session->setFlash('success',"PHP: Access token from server is " . $client->getAccessToken() );

                if (isset($_GET['state']) && $_GET['state']) {
                    Yii::$app->session->setFlash('info','PHP: State is "' . htmlentities($_GET['state'])  );

                }
                $cookie = new Cookie([
                    'name' => 'yaAccessToken',
                    'value' => $client->getAccessToken(),
                    'expire' => 0,
                ]);
                $cookie1 = new Cookie([
                    'name' => 'yaClientId',
                    'value' => $this->clientId,
                    'expire' => 0,
                ]);
                $settings->editVar('yaAccessToken',$client->getAccessToken());
                \Yii::$app->getResponse()->getCookies()->add($cookie);
                \Yii::$app->getResponse()->getCookies()->add($cookie1);
               /*
                setcookie('yaAccessToken', $client->getAccessToken(), 0, '/');
                setcookie('yaClientId', $this->clientId, 0, '/');*/
            }
        } elseif (isset($_GET['error'])) {
            Yii::$app->session->setFlash('warning',
                'PHP: Server redirected with error ' . htmlentities($_GET['error']));

            if (isset($_GET['error_description'])) {
                Yii::$app->session->setFlash('danger',
                ' and message "' . htmlentities($_GET['error_description']) );

            }

        }

       return $this->redirect('/admin');

    }


    public function onAuthSuccess($client)
    {

        (new AuthHandlerYandexMetrika($client))->handle();
    }



    protected function findModel($id)
    {

        if (($model = Domain::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }


}
