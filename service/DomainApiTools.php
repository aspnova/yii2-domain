<?php
namespace  app\modules\domain\service;
use app\modules\domain\models\Domain;
use app\modules\helper\models\Helper;
use app\modules\scripts\models\Scripts;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\Credentials\ServiceAccountJwtAccessCredentials;
use Google\Auth\Middleware\AuthTokenMiddleware;
use GuzzleHttp\HandlerStack;
use Yandex\Metrica\Management\ManagementClient;
use Yandex\Metrica\Management\Models\CodeOptions;
use Yandex\Metrica\Management\Models\Counter;
use yandex\webmaster\api\webmasterApi;
use yii\db\Exception;
use yii\helpers\Html;
use Yii;
use yii\web\Cookie;

/**
 * Created by PhpStorm.
 * User: o.trushkov
 * Date: 23.07.18
 * Time: 11:07
 */
class DomainApiTools
{

    public $domain;
    public $siteName = 'perm.gradushaus.ru';
    public $prot_siteName = 'https://perm.gradushaus.ru';



    private $managementClient;
    private $googleClient;
    private $yaAccessToken;
    private $gooAccessToken;
    private $yaClientId;
    private $importGoalsId = '47655193';
    private $importGoals = [];
    private $currGoals = [];

    private $counter;

    private $tagmanagerService;
    private $webmastersService;
    private $siteVerificationService;


    private $currTr = [];
    private $currTag = [];
    private $currContainer;
    private $currAcc;
    private $currWorkspace;
    private $currVersion;

    private $currContainers = [];

    private $copyCont;
    private $copyWorkspace;


    private $wmApi;
    private $currYaHostId;

    public $logs;

    private $targets=[];


    private $trig_links = [
        'old' => [],
        'main' => []
    ];



    public function init_app(){
        $settings = Yii::$app->getModule('settings');
        $cookies = \Yii::$app->getRequest()->getCookies();
        $this->yaAccessToken = $cookies->getValue('yaAccessToken');
        $this->gooAccessToken = $cookies->getValue('gooAccessToken');



        $this->yaClientId = $cookies->getValue('yaClientId');
        $this->managementClient = new ManagementClient( $this->yaAccessToken );

        $this->googleClient = new \Google_Client();
        $this->googleClient->setAccessType('offline');
        $this->googleClient->setApprovalPrompt ("force");
        $this->googleClient->setAuthConfig('apikeys/client_secret_1012252813843-3ufm0qo3afufje22en3crm37h92se7js.apps.googleusercontent.com.json');
        $this->googleClient->addScope([\Google_Service_Webmasters::WEBMASTERS,
            \Google_Service_TagManager::TAGMANAGER_EDIT_CONTAINERS,
            \Google_Service_TagManager::TAGMANAGER_EDIT_CONTAINERVERSIONS,
            \Google_Service_SiteVerification::SITEVERIFICATION,
            \Google_Service_TagManager::TAGMANAGER_PUBLISH

        ]);




        if (is_array($this->gooAccessToken) && isset($this->gooAccessToken['created'])){




            $refreshtimediff = time() - (int)$this->gooAccessToken['created'];
             if($refreshtimediff > 2)
             {

                 $refresh_token = $settings->getVar('goo_refresh_token');
                 $newtoken = $this->googleClient->refreshToken($refresh_token);


                 $cookie = new Cookie([
                     'name' => 'gooAccessToken',
                     'value' => $newtoken,
                     'expire' => 0,
                 ]);

                 \Yii::$app->getResponse()->getCookies()->add($cookie);
                 $settings->editVar('goo_access_token',$newtoken['access_token']);
                 $this->gooAccessToken = $newtoken;

             }
             //if the refresh token hasn't expired, set token as the refresh token
             else
             {
                 $this->googleClient->setAccessToken($this->gooAccessToken);
             }

        }




        $this->tagmanagerService = new \Google_Service_TagManager($this->googleClient);


        $this->webmastersService = new \Google_Service_Webmasters($this->googleClient);

        $this->siteVerificationService = new \Google_Service_SiteVerification($this->googleClient);


        $this->wmApi = webmasterApi::initApi( $this->yaAccessToken );
    }

    public function init_console_v2(){
        $settings = Yii::$app->getModule('settings');
        $this->yaAccessToken = $settings->getVar('yaAccessToken');
        $this->gooAccessToken =$settings->getVar('gooAccessToken');
        $goo_access_token_created =$settings->getVar('goo_access_token_created');


        //$this->yaClientId = $cookies->getValue('yaClientId');
        $this->managementClient = new ManagementClient( $this->yaAccessToken );

        $this->googleClient = new \Google_Client();
        $this->googleClient->setAccessType('offline');
        $this->googleClient->setApprovalPrompt("force");
        $this->googleClient->setAuthConfig( Yii::$app->params['path_env'] . 'apikeys/client_secret_1012252813843-3ufm0qo3afufje22en3crm37h92se7js.apps.googleusercontent.com.json');
        $this->googleClient->addScope([\Google_Service_Webmasters::WEBMASTERS,
            \Google_Service_TagManager::TAGMANAGER_EDIT_CONTAINERS,
            \Google_Service_TagManager::TAGMANAGER_EDIT_CONTAINERVERSIONS,
            \Google_Service_SiteVerification::SITEVERIFICATION,
            \Google_Service_TagManager::TAGMANAGER_PUBLISH

        ]);




        $refreshtimediff = time() - (int)$goo_access_token_created;
        if($refreshtimediff > 2)
        {

            //get new token
            $refresh_token = $settings->getVar('goo_refresh_token');
            $newtoken = $this->googleClient->refreshToken($refresh_token);

            //save new token
            $settings->editVar('goo_access_token',$newtoken['access_token']);
            $settings->editVar('goo_access_token_created',$newtoken['created']);

            $this->gooAccessToken = $newtoken;

        }
        //if the refresh token hasn't expired, set token as the refresh token
        else
        {
            $this->googleClient->setAccessToken($this->gooAccessToken);
        }






        $this->tagmanagerService = new \Google_Service_TagManager($this->googleClient);


        $this->webmastersService = new \Google_Service_Webmasters($this->googleClient);

        $this->siteVerificationService = new \Google_Service_SiteVerification($this->googleClient);


        $this->wmApi = webmasterApi::initApi( $this->yaAccessToken );
    }



    public function setDomain($domain){

        $this->domain = $domain;

        $this->siteName = $domain->alias . '.' . \Yii::$app->params['domain'];

        $this->prot_siteName = 'https://' . $this->siteName;

    }



    public function gtmContainerVersionCreate(){


        $old_verisions = $this->tagmanagerService->accounts_containers_version_headers->
        listAccountsContainersVersionHeaders($this->currContainer->getPath());

        $c = count($old_verisions->getContainerVersionHeader());

        if ($c > 0) {
            $live = null; $lates = null;
            try{
                $live = $this->tagmanagerService->accounts_containers_versions->live($this->currContainer->getPath());
//                $this->currVersion = $live;
                $this->addLog('gtm container','загрузка live версии ' .$live->getPath());
                $this->targets[] = 'gtmContainerVersionCreate';
            }
            catch (
            \Google_Service_Exception $exception
            ){

            }

            if ($live){
                $this->currVersion = $live;
                $this->targets[] = 'gtmContainerPublish';
            } else {
                $id =   $this->tagmanagerService->
                accounts_containers_version_headers->latest($this->currContainer->getPath())->getContainerVersionId();


                if ($id){
                    $lates = $this->tagmanagerService->accounts_containers_versions->get($this->currContainer->getPath().'/versions/'.$id);
                    $this->addLog('gtm container','загрузка latest версии ' .$lates->getPath());
                    $this->targets[] = 'gtmContainerVersionCreate';
                    if ($id && $lates){
                        $this->currVersion = $lates;
                        $this->gtmContainerPublish();
                    }
                }

            }


        } else {
/*
            $v = new \Google_Service_TagManager_CreateContainerVersionRequestVersionOptions();
            $v->setName('new api version');

            $nv = $this->tagmanagerService->accounts_containers_workspaces->create_version($this->currWorkspace->getPath(),$v);
            $this->currVersion = $nv->getContainerVersion();

            $this->addLog('gtm container','создание версии ' .$this->currVersion->getPath());
            $this->targets[] = 'gtmContainerVersionCreate';
            $this->gtmContainerPublish();*/
        }




    }

    public function gtmContainerPublish(){


        $path = $this->currVersion->getPath();

        $pp = $this->tagmanagerService->accounts_containers_versions->publish($path);
        if ($pp->getCompilerError() === null){

            $this->addLog('gtm container','публикация контейнера ' . $pp->getContainerVersion()->getPath());
            $this->targets[] = 'gtmContainerPublish';

        }


    }




    public function getContainerPublishAllInAccount(){


        $parent = $this->currAcc->getPath();

        $listCont = $this->tagmanagerService->accounts_containers->listAccountsContainers($parent);



        $namePreseach = []; $errs = [];

        foreach ($listCont->getContainer() as $currContainer){

            try {

                $namePreseach[] = $currContainer->getName();

                $this->currContainer = $currContainer;
                $this->gtmContainerVersionCreate();
                $this->currVersion = null;


            } catch (\Exception $e) {
                $errs[] = $e;
                sleep(7);
            }

        }
        ex([$namePreseach,$errs]);


    }



    public function checkStatus(){
        $ch = ['counterCreate','gooAccountCreate','gtmContainerCreate',
            'gtmVariablesImport','gtmBuiltInVariablesImport','gtmContainerTriggersImport',
            'gtmContainerTagImport','gtmContainerVersionCreate','createYaSite','yaSiteVerify','yaAddSiteMap',
            'gooSiteAdd','gooVerification','gooAddSiteMap','gtmContainerPublish'];

        foreach ($ch as $num => $item){
            if (in_array($item,$this->targets) ){
                unset($ch[$num]);
            }
        }

        var_dump($ch);
        if (  count($ch) == 0) {
            $this->domain->status = Domain::ST_A_O;
            $this->domain->update(false,['status']);
        } else {
            $this->domain->status = Domain::ST_A_NO;
            $this->domain->update(false,['status']);

            $this->addLog('checkStatus',print_r($ch));
        }

    }

    private function addLog($func,$ms='',$st='ok'){

        $this->logs[$func]= ['status'=>$st,'msg'=>$ms];

        /*$this->domain->log .= $func . ' : ' . $ms .'<br>';
        $this->domain->update(false,['log']);*/

    }

    public function gooAccountCreate(){

        $listCont = $this->tagmanagerService->accounts->listAccounts();

        foreach ($listCont->getAccount() as $item){
            $this->currAcc = $item;
            $this->addLog('gtm account','загрузка  аккаунта ' . $this->currAcc->getPath());
            $this->targets[] = 'gooAccountCreate';
        }


    }



}