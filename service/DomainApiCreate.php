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
class DomainApiCreate
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


    public function init_console(){
        $scopes = [\Google_Service_Webmasters::WEBMASTERS,
            \Google_Service_TagManager::TAGMANAGER_EDIT_CONTAINERS,
            \Google_Service_TagManager::TAGMANAGER_EDIT_CONTAINERVERSIONS,
            \Google_Service_SiteVerification::SITEVERIFICATION,
            \Google_Service_TagManager::TAGMANAGER_PUBLISH,
            \Google_Service_TagManager::TAGMANAGER_MANAGE_ACCOUNTS
        ];
        $key_file_location = \Yii::$app->params['path_env'] . 'web/apikeys/manage-gtm-c9736471f256.json';


        $this->googleClient = new \Google_Client();

        $this->googleClient->setAuthConfig($key_file_location);
        $this->googleClient->setScopes($scopes);
        $this->googleClient->setAccessType ('offline');

        if ( $this->googleClient->isAccessTokenExpired()){
            $this->googleClient->fetchAccessTokenWithAssertion();
        }

        $this->tagmanagerService = new \Google_Service_TagManager($this->googleClient);

        $this->googleClient->setDeveloperKey('AIzaSyC9B9KVl7H6M7oSm9poIXcjZks5Cw7q6Gc');

        $this->webmastersService = new \Google_Service_Webmasters($this->googleClient);

        $this->siteVerificationService = new \Google_Service_SiteVerification($this->googleClient);

        $settings = \Yii::$app->getModule('settings');
        $yaAccessToken = $settings->getVar('yaAccessToken');

        $this->managementClient = new ManagementClient( $yaAccessToken );
        $this->wmApi = webmasterApi::initApi( $yaAccessToken );
    }

    public function setDomain($domain){

        $this->domain = $domain;

        $this->siteName = $domain->alias . '.' . \Yii::$app->params['domain'];

        $this->prot_siteName = 'https://' . $this->siteName;

    }


    public function createYaSite(){
        $getHosts = $this->wmApi->getHosts();

        if(empty($getHosts->error_code))
        {
            foreach ($getHosts->hosts as $item){
                if ($item->host_id == 'https:'.$this->siteName.':443'){
                    $this->currYaHostId = 'https:'.$this->siteName.':443';
                }
            }
        }


        if (! $this->currYaHostId){

            $r = $this->wmApi->addHost(  'https://'.$this->siteName);

            if (empty($r->error_code)){
                $this->currYaHostId = 'https:'.$this->siteName.':443';
                $this->addLog('я.вебмастер','добавлен поддомен ' . 'https://'.$this->siteName);
                $this->targets[] = 'createYaSite';

            } else {
                $this->addLog('я.вебмастер addHost',$r->error_message);
            }


            // siteName 'kazan.gradushaus.ru';
            // host_id = https:kazan.gradushaus.ru:443

        } else {
            $this->targets[] = 'createYaSite';
        }

    }

    public function yaSiteVerify(){

        if ( ! $this->currYaHostId ) return;

        $sm = $this->wmApi->checkVerification($this->currYaHostId); //1 request code
        if (empty($sm->error_code)){


            if ( $sm->verification_state == 'NONE' ||
                $sm->verification_state == 'VERIFICATION_FAILED' ||
                $sm->verification_state == 'INTERNAL_ERROR'
            ){

                // 2 place token on site
                $this->domain->ya_ver = "<meta name=\"yandex-verification\" content=\"$sm->verification_uin\" />";
                $this->domain->update(false,['ya_ver']);
                $this->snippetRefresh();
                $this->addLog('я.вебмастер',' сохранен верификациаонный код ' . $sm->verification_uin);
            }



            if ( $sm->verification_state == 'VERIFIED' || $sm->verification_state == 'IN_PROGRESS' ){
                $this->targets[] = 'yaSiteVerify';
                $this->addLog('я.вебмастер',' сайт подтвержден ' . $this->currYaHostId);
            } else {
                $this->addLog('я.вебмастер',' запрос на верификацию ' . $this->currYaHostId);
                $this->targets[] = 'yaSiteVerify';
                try {
                    $vf = $this->wmApi->verifyHost($this->currYaHostId,'META_TAG'); //3 ask to verify site

                } catch (\Exception $e) {
                    $smm = $this->wmApi->checkVerification($this->currYaHostId); //1 request code
                    if (!in_array($smm->verification_state, ['VERIFIED', 'IN_PROGRESS'])) {
                        $vf = $this->wmApi->verifyHost($this->currYaHostId, 'META_TAG'); //3 ask to verify site
                        if (!empty($vf->error_code) && empty($vf->error_code)) {
                            $this->addLog('я.вебмастер', ' статус верификации ' . $vf->verification_state);
                        }


                    }

                }



            }

            //ex($sm);


        } else {
            if (! empty($sm->error_message)){
                $this->addLog('я.вебмастер checkVerification',$sm->error_message);
            }
        }

    }

    public function yaAddSiteMap(){


        $sm = $this->wmApi->checkVerification($this->currYaHostId);

        if (empty($sm->error_code)) {

            if ($sm->verification_state == 'VERIFIED'
                || $sm->verification_state == 'IN_PROGRESS'
            ) {
                $sm_url = $this->prot_siteName.'/sitemap.xml';

                $exs = false;
                $smc = $this->wmApi->getHostSitemaps($this->currYaHostId);
                if (empty($smc->error_code)){
                    foreach ($smc->sitemaps as $item){
                        if ( $item->sitemap_url == $sm_url){
                            $exs = true;
                        }
                    }
                }
                $smc = $this->wmApi->getHostUserSitemaps($this->currYaHostId);
                if (empty($smc->error_code)){
                    foreach ($smc->sitemaps as $item){
                        if ( $item->sitemap_url == $sm_url){
                            $exs = true;
                        }
                    }
                }

                if (! $exs){
                    $sm = $this->wmApi->addSitemap($this->currYaHostId,$sm_url);
                    if (empty($sm->error_code)){
                        $this->addLog('я.вебмастер','sitemap добавлен ' . $this->prot_siteName.'/sitemap.xml');
                        $this->targets[] = 'yaAddSiteMap';
                    } else {
                        $this->addLog('я.вебмастер','addSitemap ' . $sm->error_message);
                    }
                } else {
                    $this->targets[] = 'yaAddSiteMap';
                }
            }

        } else {
            if (! empty($sm->error_message)){
                $this->addLog('я.вебмастер yaAddSiteMap.checkVerification ',$sm->error_message);
            }
        }


    }


    public function gooSiteAdd(){

        //$sites = $webmastersService->sites->listSites(array());

        $oldSites = [];

        foreach ( $this->webmastersService->sites->listSites()->getSiteEntry() as $item){
            $oldSites[$item->siteUrl] = $item->siteUrl;
        }

        if (! array_key_exists($this->prot_siteName .'/',$oldSites)){
            $r = $this->webmastersService->sites->add($this->prot_siteName);
            $this->addLog('search console','добавление сайта ' . $this->prot_siteName);
            $this->targets[] = 'gooSiteAdd';
        } else {
            $this->targets[] = 'gooSiteAdd';
        }


    }

    public function snippetRefresh(){
        $script_head = Scripts::findOne(['entity_type'=>Scripts::T_H,'domain_id'=>$this->domain->id]);
        if ($script_head === null){
                $script_head = new Scripts();
                $script_head->domain_id = $this->domain->id;
                $script_head->entity_type = Scripts::T_H;
                $script_head->content =
                    '<script>window.dataLayer = window.dataLayer || [];</script>' .
                    $this->domain->gtm_head . $this->domain->ya_ver . $this->domain->go_ver;
                $script_head->save();

        } else {
            $script_head->content =
                '<script>window.dataLayer = window.dataLayer || [];</script>' .
                $this->domain->gtm_head . $this->domain->ya_ver . $this->domain->go_ver;
            $script_head->update(false,['content']);
        }



        $script_head = Scripts::findOne(['entity_type'=>Scripts::T_B,'domain_id'=>$this->domain->id]);
        if ($script_head === null){
            $script_head = new Scripts();
            $script_head->domain_id = $this->domain->id;
            $script_head->entity_type = Scripts::T_B;
            $script_head->content = $this->domain->gtm_body;
            $script_head->save();

        } else {
            $script_head->content = $this->domain->gtm_body;
            $script_head->update(false,['content']);
        }

    }

    public function gooVerification(){

        $old_verif_list = $this->siteVerificationService->webResource->listWebResource();

        $old_verif = [];
        foreach ($old_verif_list->getItems() as $item){
            $old_verif[$item->getSite()->identifier] = $item->getSite()->identifier;
        }


        if (! array_key_exists($this->prot_siteName .'/',$old_verif)){

            $wr = new \Google_Service_SiteVerification_SiteVerificationWebResourceGettokenRequest();
            $wr->setVerificationMethod('META');

            $site = new \Google_Service_SiteVerification_SiteVerificationWebResourceGettokenRequestSite();
            $site->setIdentifier($this->prot_siteName .'/');
            $site->setType('SITE');
            $wr->setSite($site);


            $t = $this->siteVerificationService->webResource->getToken($wr)->getToken(); //1 get token
            $this->addLog('search console',' получение токена верификации ' . $this->prot_siteName);

            $this->domain->go_ver = $t;

            $this->domain->update(false,['go_ver']);
            $this->snippetRefresh();

            $this->addLog('search console',' сохранен верификационный код ' . $this->prot_siteName);

            ///

            try {
                $vs = new \Google_Service_SiteVerification_SiteVerificationWebResourceResource();
                $vss = new \Google_Service_SiteVerification_SiteVerificationWebResourceResourceSite();
                $vss->setType('SITE');
                $vss->setIdentifier($this->prot_siteName .'/');
                $vs->setSite($vss);


                $rv = $this->siteVerificationService->webResource->insert('META',$vs);
                $this->addLog('search console',' запрос подтверждения верификации ' . $this->prot_siteName);
                $this->targets[] = 'gooVerification';
            } catch (\Google_Service_Exception $e ){
                $this->addLog('search console',' сайт подтвержден ' . $this->prot_siteName . $e->getMessage());

            }


        } else {
            $this->targets[] = 'gooVerification';
        }


    }

    public function gooAddSiteMap(){

        $sm_url = $this->prot_siteName.'/sitemap.xml';

        $old_sm = [];

        foreach ($this->webmastersService->sitemaps->listSitemaps($this->prot_siteName)->getSitemap() as $item){
            $old_sm[$item->getPath()] = $item->getPath();
        }

        if (! array_key_exists($sm_url,$old_sm)){
            $sm = $this->webmastersService->sitemaps->submit($this->prot_siteName,$sm_url);
            $this->addLog('search console',' добавление sitemap ' . $this->prot_siteName);
            $this->targets[] = 'gooAddSiteMap';
        } else {
            $this->targets[] = 'gooAddSiteMap';
        }

    }



    public function gtmCodeUpdate(){

        $public_id = $this->currContainer->getPublicId();
        if ( ! $public_id ) return;

        $head_code = "<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','$public_id');</script>
<!-- End Google Tag Manager -->";

        $body_code = "<!-- Google Tag Manager (noscript) -->
<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=$public_id\"
height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->";



        /*
        $this->domain->updateVerifyCode($head_code,Scripts::T_H,'<!-- Google Tag Manager -->','<!-- End Google Tag Manager -->');
        $this->domain->updateVerifyCode($body_code,Scripts::T_B,'<!-- Google Tag Manager (noscript) -->','<!-- End Google Tag Manager (noscript) -->');
*/
        $this->domain->gtm_head = $head_code;
        $this->domain->gtm_body = $body_code;
        $this->domain->update(false,['gtm_head','gtm_body']);

        $this->addLog('gtm container','обновление сниппета с кодом ' . $this->prot_siteName);



    }

    public function gtmContainerPublish(){


        $path = $this->currVersion->getPath();

        $pp = $this->tagmanagerService->accounts_containers_versions->publish($path);
        if ($pp->getCompilerError() === null){
            $this->addLog('gtm container','публикация контейнера ' . $pp->getContainerVersion()->getPath());

            $this->targets[] = 'gtmContainerPublish';
        }



    }


    private function addLog($func,$ms='',$st='ok'){

        $this->logs[$func]= ['status'=>$st,'msg'=>$ms];

        $this->domain->log .= $func . ' : ' . $ms .'<br>';
        $this->domain->update(false,['log']);

    }





    public function gooAccountCreate(){

        $listCont = $this->tagmanagerService->accounts->listAccounts();

        foreach ($listCont->getAccount() as $item){
            $this->currAcc = $item;
            $this->addLog('gtm account','загрузка  аккаунта ' . $this->currAcc->getPath());
            $this->targets[] = 'gooAccountCreate';
        }


    }



    public function goalsCreate(){



        $this->importGoals = $this->managementClient->goals()->getGoals($this->importGoalsId);
        $currGoals =  $this->managementClient->goals()->getGoals($this->counter->getId());




        if ($currGoals instanceof \Traversable) {
            foreach ($currGoals as $goal) {

                $this->currGoals[$goal->getName()] = $goal;

            }
            if ( count($this->currGoals)){
                $this->addLog('я.метрика','загрузка целей ' . $this->counter->getId());
            }
        }



        $c = 0;
        if ($this->importGoals instanceof \Traversable) {
            foreach ($this->importGoals as $goal) {

                if (! array_key_exists($goal->getName(),$this->currGoals)){
                    $r = $this->managementClient->goals()->addGoal($this->counter->getId(),$goal);
                    $this->currGoals[$goal->getName()] = $r;
                    $c ++;

                }
            }

            if ($c){
                $this->addLog('я.метрика','импорт целей с ' . $this->importGoalsId);
            }
        }


    }

    public function counterCreate(){

        $counterName = $this->domain->alias  . '.' . \Yii::$app->params['domain'];

        $counterSite = $counterName;

        //POST /counters
        /**
         * @see http://api.yandex.ru/metrika/doc/ref/reference/add-counter.xml
         */

        $paramsObj = new \Yandex\Metrica\Management\Models\CountersParams();
        $paramsObj
            ->setType(\Yandex\Metrica\Management\AvailableValues::TYPE_SIMPLE)
            ->setField('goals,mirrors,grants,filters,operations');


        try {
            $counters = $this->managementClient->counters()->getCounters($paramsObj)->getCounters();

            if ($counters instanceof \Traversable) {
                foreach ($counters as $counter) {
                    if ( $counter->getName() == $counterName){

                        $this->counter = $counter;
                        $this->addLog('я.метрика','загрузка счетчика ' . $this->counter->getId());
                        $this->targets[] = 'counterCreate';
                        return $this->counter;

                    }
                }
            }


        } catch (\Exception $ex) {
            $errorMessage = $ex->getMessage();
            if ($errorMessage === 'PlatformNotAllowed') {
                $errorMessage = '<p>Возможно, у приложения нет прав на доступ к ресурсу. Попробуйте  авторизироваться (футер)';
            }
        }


        $counterPostRequest = new Counter();
        $counterPostRequest
            ->setName($counterName)
            ->setSite($counterSite);

        $codeOptions = new CodeOptions();
        $codeOptions
            ->setAsync(1)
            ->setTrackHash(1)
            ->setVisor(1)
            ->setInOneLine(1);

        $counterPostRequest->setCodeOptions($codeOptions);

        $result = $this->managementClient
            ->counters()
            ->addCounter($counterPostRequest);

        $this->counter = $result;

        $this->addLog('я.метрика','создание счетчика ' . $this->counter->getId());
        $this->targets[] = 'counterCreate';


        return $result;
    }

    public function gtmContainerCreate(){
        

        $parent = $this->currAcc->getPath();

        $listCont = $this->tagmanagerService->accounts_containers->listAccountsContainers($parent);

        foreach ($listCont->getContainer() as $item){
            $key = mb_strtolower($item->getName());
            $this->currContainers[$key] = $item;
            if ($key == 'moskva.gradushaus'){
                $this->copyContainerCreate($item);
            }
        }



        if ( ! array_key_exists($this->siteName,$this->currContainers) ){
            $newCont = new \Google_Service_TagManager_Container();
            $newCont->name = $this->siteName;
            $newCont->domainName = $this->siteName;
            $newCont->accountId = $this->currAcc->getAccountId();

            $newCont->usageContext = ['web'];
            $newCont = $this->tagmanagerService->accounts_containers->create($this->currAcc->getPath(),$newCont);
            $this->addLog('gtm container','создание контейнера ' . $this->counter->getId());
            $this->targets[] = 'gtmContainerCreate';
            $this->currContainer = $newCont;


        } else {
            $this->currContainer = $this->currContainers[$this->siteName];
            $this->addLog('gtm container','загрузка контейнера ' . $this->counter->getId());
            $this->targets[] = 'gtmContainerCreate';
        }

        $l = $this->tagmanagerService->accounts_containers_workspaces->listAccountsContainersWorkspaces( $this->currContainer->getPath());

        foreach ($l->getWorkspace() as $item){

            $this->currWorkspace = $item;
            $this->addLog('gtm container','загрузка workspace ' .$this->currWorkspace->getPath());
        }


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

            $v = new \Google_Service_TagManager_CreateContainerVersionRequestVersionOptions();
            $v->setName('new api version');

            $nv = $this->tagmanagerService->accounts_containers_workspaces->create_version($this->currWorkspace->getPath(),$v);
            $this->currVersion = $nv->getContainerVersion();

            $this->addLog('gtm container','создание версии ' .$this->currVersion->getPath());
            $this->targets[] = 'gtmContainerVersionCreate';
            $this->gtmContainerPublish();
        }




    }

    public function gtmContainerTagImport(){

        $path = $this->currWorkspace->getPath();
        $path_ntg = $this->copyWorkspace->getPath();


        $list_copy = $this->tagmanagerService->accounts_containers_workspaces_tags->listAccountsContainersWorkspacesTags($path_ntg);
        $list_curr = $this->tagmanagerService->accounts_containers_workspaces_tags->listAccountsContainersWorkspacesTags($path);




        $c = 0;
        foreach ($list_curr->getTag() as $key=>$value){
            $this->currTag[$value->name] = $value;
            $c ++;
        }
        if ($c){
            $this->addLog('gtm container','теги загрузка' );
            $this->targets[] = 'gtmContainerTagImport';
        }



        $c = 0;
        foreach ($list_copy->getTag() as $key=>$value){

            if ( ! array_key_exists($value->name,$this->currTag) ){

                $new_set = [];
                foreach($value->firingTriggerId as $id){

                    $old_trigger_id = (int)$id;

                    if (! isset($this->trig_links['old'][$old_trigger_id])){
                        continue;
                    }

                    $word = $this->trig_links['old'][$old_trigger_id];
                    $main_id_trigger = $this->trig_links['main'][$word];

                    $new_set[] = $main_id_trigger;

                }



                if (count($new_set)){
                    $value->firingTriggerId = $new_set;
                    $this->rewriteTriggerParams($value);
                }

                if ($value->name == 'YM-PAGEVIEW'){
                    $this->rewriteTriggerParams($value);
                }

                try {
                    $r = $this->tagmanagerService->
                    accounts_containers_workspaces_tags->create($path,$value);
                    $c ++;


                    $this->currTag[$r->name]  = $r;

                } catch (\Google_Service_Exception $e) {
                  //  ex([$value,$new_set,$this->trig_links]);
                    $this->addLog('gtm container','импорт тега исключение' . $value->name);
                }

            }

        }
        if ($c){
            $this->addLog('gtm container','импорт тегов' );
            $this->targets[] = 'gtmContainerTagImport';
        }

    }

    public function gtmContainerTriggersImport(){


        $path = $this->currWorkspace->getPath();
        $path_ntg = $this->copyWorkspace->getPath();


        $list_copy = $this->tagmanagerService->accounts_containers_workspaces_triggers->listAccountsContainersWorkspacesTriggers($path_ntg);
        $list_curr = $this->tagmanagerService->accounts_containers_workspaces_triggers->listAccountsContainersWorkspacesTriggers($path);



        $c =  0;
        foreach ($list_curr->getTrigger() as $key=>$value){
            $this->currTr[$value->name] = $value;
            $this->trig_links['main'][ $value->name] = $value->triggerId ;
            $c ++;
        }

        if ($c){
            $this->addLog('gtm container','загрузка триггеров ' );
            $this->targets[] = 'gtmContainerTriggersImport';
        }




        $c =  0;
        foreach ($list_copy->getTrigger() as $key=>$value){


            if ( ! array_key_exists($value->name,$this->currTr) ){

                $r = $this->tagmanagerService->
                accounts_containers_workspaces_triggers->create($path,$value);

                $c ++;
                $this->currTr[$r->name]  = $r;

                $this->trig_links['main'][$r->name] = $r->triggerId; //

            }

            $this->trig_links['old'][$value->triggerId] = $value->name;

        }

        if ($c){
            $this->addLog('gtm container','импорт триггеров' );
            $this->targets[] = 'gtmContainerTriggersImport';
        }




    }

    public function gtmVariablesImport(){
        $path = $this->currWorkspace->getPath();
        $path_ntg = $this->copyWorkspace->getPath();


        $list = $this->tagmanagerService->accounts_containers_workspaces_variables->listAccountsContainersWorkspacesVariables($path_ntg);
        $list_old = $this->tagmanagerService->accounts_containers_workspaces_variables->listAccountsContainersWorkspacesVariables($path);
        $old_vars = [];

        $c = 0;
        foreach ($list_old->getVariable() as $key=>$value){
            $old_vars[$value->name] = $value;
            $c ++;
        }
        if ($c){
            $this->addLog('gtm container','загрузка  переменных' );
            $this->targets[] = 'gtmVariablesImport';

        }

        $c = 0;
        foreach ($list->getVariable() as $key=>$value){


            if ( ! array_key_exists($value->name,$old_vars) ){

                $value->accountId = $this->currAcc->getAccountId();
                $value->path = $path_ntg;

                $r = $this->tagmanagerService->accounts_containers_workspaces_variables->create($path,$value);
                $c ++;
            }
        }
        if ($c){
            $this->addLog('gtm container','импорт переменных ' );
            $this->targets[] = 'gtmVariablesImport';
        }

    }

    public function gtmBuiltInVariablesImport(){

        $path = $this->currWorkspace->getPath();
        $path_ntg = $this->copyWorkspace->getPath();


        $list = $this->tagmanagerService->accounts_containers_workspaces_built_in_variables->listAccountsContainersWorkspacesBuiltInVariables($path_ntg);
        $list_old = $this->tagmanagerService->accounts_containers_workspaces_built_in_variables->listAccountsContainersWorkspacesBuiltInVariables($path);
        $old_vars = [];
        $c = 0;
        foreach ($list_old->getBuiltInVariable() as $key=>$value){
            $old_vars[$value->name] = $value;
            $c ++;
        }
        if ($c){
            $this->addLog('gtm container','загрузка польз переменных' );
            $this->targets[] = 'gtmBuiltInVariablesImport';
        }

        $c = 0;
        foreach ($list->getBuiltInVariable() as $key=>$value){


            if ( ! array_key_exists($value->name,$old_vars) ){

                $r = $this->tagmanagerService->
                accounts_containers_workspaces_built_in_variables->create($path,['type'=>$value->type]);
                $c ++;
            }


        }
        if ($c){
            $this->addLog('gtm container','импорт пользовательских переменных  ' );
            $this->targets[] = 'gtmBuiltInVariablesImport';
        }

    }



    private function copyContainerCreate($item){

        $this->copyCont = $item;

        //load workspace copy
        $l = $this->tagmanagerService->accounts_containers_workspaces->listAccountsContainersWorkspaces( $this->copyCont->getPath());
        foreach ($l->getWorkspace() as $item){
            $this->copyWorkspace = $item;
        }

    }

    private function tempGoal($value,$name_event){

        foreach ($value->getParameter() as $parameter){
            if ($parameter->key == 'html' && $parameter->type == 'template' && $parameter->value ){
                $idc = $this->counter->getId();
                $parameter->value =
                    '<script>' . "yaCounter" . $idc .".reachGoal('$name_event')" .  '</script>';
            }
        }

    }

    private function tempCounter($value){

        foreach ($value->getParameter() as $parameter){
            if ($parameter->key == 'html' && $parameter->type == 'template' && $parameter->value ){
                $idc = $this->counter->getId();
                $parameter->value =
                    '<!-- Yandex.Metrika counter -->
<script type="text/javascript" >
    (function (d, w, c) {
        (w[c] = w[c] || []).push(function() {
            try {
                w.yaCounter'.$idc.' = new Ya.Metrika({
                    id:'.$idc.',
                    clickmap:true,
                    trackLinks:true,
                    accurateTrackBounce:true,
                    webvisor:true
                });
            } catch(e) { }
        });

        var n = d.getElementsByTagName("script")[0],
            s = d.createElement("script"),
            f = function () { n.parentNode.insertBefore(s, n); };
        s.type = "text/javascript";
        s.async = true;
        s.src = "https://mc.yandex.ru/metrika/watch.js";

        if (w.opera == "[object Opera]") {
            d.addEventListener("DOMContentLoaded", f, false);
        } else { f(); }
    })(document, window, "yandex_metrika_callbacks");
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/48771719" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->';
            }
        }

    }

    private function rewriteTriggerParams($value){
        if ($value->name  == 'Заказ звонка'){
            $this->tempGoal($value,'callback_submit');

        }
        if ($value->name  == 'Добавить в корзину'){
            $this->tempGoal($value,'basket-add');

        }
        if ($value->name  == 'Заказ оформлен'){
            $this->tempGoal($value,'basket_submit');

        }
        if ($value->name  == 'Заказать_отправка контактных данных'){
            $this->tempGoal($value,'zakaz-submit');
        }
        if ($value->name  == 'Купить в 1_клик'){
            $this->tempGoal($value,'fast-order-click');

        }
        if ($value->name  == 'YM-PAGEVIEW'){
            $this->tempCounter($value);
        }
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


}