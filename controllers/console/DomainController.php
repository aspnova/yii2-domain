<?php

namespace app\modules\domain\controllers\console;


use app\modules\comment\models\Comments;
use app\modules\domain\models\Domain;

use app\modules\domain\models\DomainEnt;
use app\modules\domain\service\AppDomainCreateConsole;
use app\modules\domain\service\DomainApiTools;
use app\modules\download\models\Download;
use app\modules\download\models\ProductDownload;
use app\modules\helper\models\Helper;
use app\modules\image\services\AttImg;
use app\modules\product\models\ProductKit;
use app\modules\product\models\ProductRecomendSet;
use app\modules\product\models\ProductRelevantSet;
use app\modules\product\service\ProductGenerateCombItemsForKitsByCatAllDomains;
use app\modules\product\service\ProductGenerateCombItemsForRelsByCatAllDomains;
use app\modules\url\models\UrlDomain;
use Yandex\Metrica\Management\ManagementClient;
use Yandex\Metrica\Management\Models\CountersParams;
use yii\console\Controller;
use yii\console\Exception;
use yii\db\Transaction;
use yii\helpers\Console;
use app\modules\image\models\Img;
use Yii;
/**
 * Interactive console image manager
 */
class DomainController extends Controller
{


    // /opt/php72/bin/php yii domain_console/domain/delete
    public function actionDelete(){
        $all = Domain::find()->where(['>','id',235])->limit(1000)->all();

        foreach ($all as $item){


            $transaction = Yii::$app->db->beginTransaction(Transaction::SERIALIZABLE);


            try {




                $url_domains = UrlDomain::findAll(['domain_id'=>$item->id]);
                foreach ($url_domains as $url_domain){
                    $url_domain->delete();
                }

                $comments = Comments::findAll(['domain_id'=>$item->id]);
                foreach ($comments as $comment){
                    $comment->delete();
                }

                $domain_ents = DomainEnt::findAll(['domain_id'=>$item->id]);
                foreach ($domain_ents as $domain_ent){
                    $domain_ent->delete();
                }

                $product_recomend_sets = ProductRecomendSet::findAll(['domain_id'=>$item->id]);
                foreach ($product_recomend_sets as $product_recomend_set){
                    $product_recomend_set->delete();
                }


                $product_relevant_sets = ProductRelevantSet::findAll(['domain_id'=>$item->id]);
                foreach ($product_relevant_sets as $product_relevant_set){
                    $product_relevant_set->delete();
                }


                $product_kits = ProductKit::findAll(['domain_id'=>$item->id]);
                foreach ($product_kits as $product_kit){
                    $product_kit->delete();
                }


                $transaction->commit();
                $this->log($item->name .  ' delete');

                //$transaction->rollBack();



            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }

        }

    }


    // /opt/php72/bin/php yii domain_console/domain/create
    //php yii crone/crone/create
    // /opt/php72/bin/php yii crone/crone/product-generate-comb-items-for-kits-by-cat-all-domains 1,3,4 4


    public function actionCreate(){

        $settings = Yii::$app->getModule('settings');
        $domain_create_off  = (int) $settings->getVar('domain_create_off');
        if ($domain_create_off != 0){
            Yii::$app->end();
        }



        $appDomainCreate = new AppDomainCreateConsole();
        $appDomainCreate->createDomainByExcel();


        foreach ($appDomainCreate->logs as $key =>  $value){
            $this->log($key . ' ' . $value);
        }



    }



    // /opt/php72/bin/php yii domain_console/domain/createapi
    //php yii  crone/crone/createapi

    public function actionCreateapi(){

        $settings = Yii::$app->getModule('settings');
        $domain_create_off  = (int) $settings->getVar('domain_create_off');

        if ($domain_create_off != 0){
            Yii::$app->end();
        }


        $appDomainCreate = new AppDomainCreateConsole();
        $appDomainCreate->createApi();



        foreach ($appDomainCreate->logs as $key =>  $value){
            $this->log($key . ' ' . $value);
        }


        Yii::$app->end();
    }






    // /opt/php72/bin/php yii crone/crone/publish-all
    //php yii  crone/crone/publish-all

    public function actionPublishAll(){

        $domainApiTools = new DomainApiTools();
        $domainApiTools->init_console_v2();
        $domainApiTools->gooAccountCreate();
        $domainApiTools->getContainerPublishAllInAccount();




        Yii::$app->end();
    }



    // /opt/php72/bin/php yii domain_console/domain/test_goal
    public function actionTest_goal(){
        foreach ( Domain::find()->where(['geo_domain'=>Domain::G_Y])->all() as $domain){
            $url = $domain->alias  . '.gradushaus.ru/pivovarenie/suslo/svetloe?test_goals=1';
            $this->log($url);
            Helper::curl_get($url,[]);

        }
    }



// /opt/php72/bin/php yii domain_console/domain/remove_goal
    public function actionRemove_goal(){

        //$list_goals = [ 'Заказ оформлен - 2'];

        $list_goals = [ 'Контактные данные отправлены',
            'Купить в 1 клик','Заказ обратного звонка','basketadd','Нажата кнопка "купить в 1 клик"','Заказ звонка'];

        $settings = Yii::$app->getModule('settings');
        $yaAccessToken = $settings->getVar('yaAccessToken');
        $managementClient = new ManagementClient( $yaAccessToken );
        $counters = $managementClient->counters()->getCounters(new CountersParams())->getCounters();



        foreach ( $counters as $counter ){

            $goals = $managementClient->goals()->getGoals($counter->getId());

            foreach ($goals as $goal){

                if ( in_array($goal->getName(),$list_goals) ){

                    $managementClient->goals()->deleteGoal($goal->getId(),$counter->getId());
                }


            }

        }
    }

    // /opt/php72/bin/php yii domain_console/domain/remove-goal
    public function actionRemove_double_goals(){


        $settings = Yii::$app->getModule('settings');
        $yaAccessToken = $settings->getVar('yaAccessToken');
        $managementClient = new ManagementClient( $yaAccessToken );
        $counters = $managementClient->counters()->getCounters(new CountersParams())->getCounters();



        foreach ( $counters as $counter ){

            $goals = $managementClient->goals()->getGoals($counter->getId());

            $name_goals_counter = [];

            foreach ($goals as $goal){

                if (in_array($goal->getName(),$name_goals_counter)){
                    //remove
                    $managementClient->goals()->deleteGoal($goal->getId(),$counter->getId());
                } else {
                    $name_goals_counter[] = $goal->getName();
                }


            }

        }
    }

    // /opt/php72/bin/php yii domain_console/domain/remove_update_goal
    public function actionUpdate_goal(){
        $cu = 0;
        $settings = Yii::$app->getModule('settings');
        $yaAccessToken = $settings->getVar('yaAccessToken');
        $managementClient = new ManagementClient( $yaAccessToken );
        $counters = $managementClient->counters()->getCounters(new CountersParams())->getCounters();

        foreach ( $counters as $counter ){
            /*
                        if ( $counter->getId() != 50289832 ){
                            continue;
                        }
            */
            $goals = $managementClient->goals()->getGoals($counter->getId());


            foreach ($goals as $goal){


                $conditions = $goal->getConditions();
                if ($conditions === null){
                    continue;
                }


                $need_upd = false;
                foreach ( $conditions as $num_c => $condition){

                    if ( $condition->getType() == 'exact' && $condition->getUrl() == 'basket-submit' ){
                        $condition->setUrl('basket_submit');
                        $need_upd = true;
                    }
                }
                if ($need_upd){
                    $goal->setConditions($conditions);
                    $managementClient->goals()->updateGoal($goal->getId(),$counter->getId(),$goal);
                    $cu ++;

                    /*if ($cu > 5){
                        ex($goal->toArray());
                    }
                    */
                }
            }



        }



    }

    // /opt/php72/bin/php yii domain_console/domain/add_goal
    public function actionAddGoal(){

        $settings = Yii::$app->getModule('settings');
        $yaAccessToken = $settings->getVar('yaAccessToken');
        $managementClient = new ManagementClient( $yaAccessToken );
        $counters = $managementClient->counters()->getCounters(new CountersParams())->getCounters();

        foreach ( $counters as $counter ){
            $goals_target_name = [];
            $goals = $managementClient->goals()->getGoals($counter->getId());
            foreach ($goals as $goal){
                $conditions = $goal->getConditions();
                if ($conditions === null){
                    continue;
                }
                foreach ( $conditions as $condition){
                    if ( $condition->getType() == 'exact' ){
                        $u = $condition->getUrl();
                        $goals_target_name[] = $u;
                    }
                }
            }

            if (! in_array('basket_submit',$goals_target_name)){

                $new_conditions = new Conditions();
                $new_conditions->add(['type'=>'exact','url'=>'basket_submit']);

                $new_goal = new Goal();
                $new_goal->setName('Заказ оформлен 2');
                $new_goal->setType('action');
                $new_goal->setConditions($new_conditions);

                $managementClient->goals()->addGoal($counter->getId(),$new_goal);


            }

        }



    }





    /**
     * @param bool $success
     */
    private function log($msg,$success=true)
    {
        if ($success) {
            $this->stdout($msg, Console::FG_GREEN, Console::BOLD);
        } else {
            $this->stderr($msg, Console::FG_RED, Console::BOLD);
        }
        $this->stdout(PHP_EOL);
    }


}