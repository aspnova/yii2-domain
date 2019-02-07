<?php

namespace app\modules\domain\controllers\backend;


use app\modules\domain\service\AuthHandlerYandexMetrika;
use app\modules\url\models\Url;
use app\modules\url\models\UrlDomain;
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


class GoogleApiController extends Controller
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

    public function actionIndex(){
        $client = new OAuthClient($this->clientId);
        $state = 'yandex-php-library';
        $client->authRedirect(true, OAuthClient::CODE_AUTH_TYPE, $state);


    }

    public function actionGoogle(){

        $client = new \Google_Client();
        $client->setAuthConfig('apikeys/client_secret_1012252813843-3ufm0qo3afufje22en3crm37h92se7js.apps.googleusercontent.com.json');
        $client->setAccessType("offline");        // offline access
        $client->setApprovalPrompt ("force");
        //  $client->setIncludeGrantedScopes(true);   // incremental auth
        //$client->addScope(\Google_Service_Webmasters::WEBMASTERS);
        $client->addScope([\Google_Service_Webmasters::WEBMASTERS,
            \Google_Service_TagManager::TAGMANAGER_EDIT_CONTAINERS,
            \Google_Service_TagManager::TAGMANAGER_EDIT_CONTAINERVERSIONS,
            \Google_Service_SiteVerification::SITEVERIFICATION,
            \Google_Service_TagManager::TAGMANAGER_PUBLISH,
        ]);

        //$client->setApprovalPrompt('auto');

        $client->setRedirectUri('https://gradushaus.ru/admin/domain/google-api/callback');
        // $client->setRedirectUri('https://gradushaus.ru/admin/domain/yandex-api-metrika/google-callback');

        //$client->setRedirectUri('http://seokeys.it-06.aim/admin/domain/google-api/callback');
        $auth_url = $client->createAuthUrl();
        header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
        exit;

        /*  $client = ne08il2010w \Google_Client();
          $client->setAuthConfig('uploads/manage-gtm-663b8162a783.json');
          $client->addScope(\Google_Service_Webmasters::WEBMASTERS);

          $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
          $client->setRedirectUri($redirect_uri);

          if (isset($_GET['code'])) {
              $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
          }*/



    }


    // live cb
    public function actionCallback(){


        $client = new \Google_Client();
        $client->setAuthConfig('apikeys/client_secret_1012252813843-3ufm0qo3afufje22en3crm37h92se7js.apps.googleusercontent.com.json');
        $client->setAccessType("offline");        // offline access
       // $client->setIncludeGrantedScopes(true);   // incremental auth
        $client->setApprovalPrompt ("force");
        $client->addScope([\Google_Service_Webmasters::WEBMASTERS,
            \Google_Service_TagManager::TAGMANAGER_EDIT_CONTAINERS,
            \Google_Service_TagManager::TAGMANAGER_EDIT_CONTAINERVERSIONS,
            \Google_Service_SiteVerification::SITEVERIFICATION,
            \Google_Service_TagManager::TAGMANAGER_PUBLISH

        ]);

        $t = $client->authenticate($_GET['code']);

        //$access_token = $client->getAccessToken();

        $token = $client->getAccessToken();



        $cookie = new Cookie([
            'name' => 'gooAccessToken',
            'value' => $token,
            'expire' => 0,
        ]);
        \Yii::$app->getResponse()->getCookies()->add($cookie);

        $settings = \Yii::$app->getModule('settings');
        $settings->editVar('goo_access_token',$token['access_token']);
        $settings->editVar('goo_refresh_token',$token['refresh_token']);
        $settings->editVar('goo_access_token_created',$token['created']);

        return  $this->redirect('/admin');
        exit;























        $session = Yii::$app->session;
        $session->open();



        $client = new OAuthClient($this->clientId,$this->clientSecret);

        if ( $session->get('yaAccessToken')) {
            $client->setAccessToken($session['yaAccessToken']);
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
