<?php
/**
 * Created by PhpStorm.
 * User: o.trushkov
 * Date: 03.08.18
 * Time: 9:19
 */

namespace app\modules\domain\service;


use app\modules\domain\models\Domain;
use app\modules\download\models\Download;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use yii\db\Transaction;
use yii\helpers\ArrayHelper;
use Yii;
use yii\httpclient\Client;

class AppDomainCreateConsole
{


    private $loadFile=[];

    private $needCreate= false;
    private $needCreateDomains = [];
    private $limitCreate = 3;

    private $domainCreateService;

    public $logs = [];

    public function __construct()
    {
        $settings = Yii::$app->getModule('settings');
        $sl = (int)$settings->getVar('domain_limit_create');

        $this->limitCreate  = $sl;


    }




    public function createDomainByExcel(){
        $domain = null;

        $settings = Yii::$app->getModule('settings');
        $name_list  = $settings->getVar('domain_list_excel_name');//'domain api list'

        if (! $name_list){
            $name_list = 'domain api list';
        }



        $this->readExcel($name_list);
        $this->filterDomanis();
        $this->needCreateDomain();




        if ($this->needCreate){

            $domain = $this->createDomainAndApplyOperations($this->needCreateDomains);
        }

        return $domain;

    }




    public function createApi(){
        $allDomains = Domain::findAll(['publish'=>Domain::P_OK,'status'=>Domain::ST_A_NO,'geo_domain'=>Domain::G_Y]);
        foreach ($allDomains as $domain){
            $this->domainApiCreate($domain);

        }
        return count($allDomains);
    }



///utils
    private function readExcel($name){
        $pd = Download::find()->
        where('download.name = :filename' , [ 'filename' =>$name])->
        one();

        $dir = Yii::$app->params['path_env'];


        if ($pd !== null ){
            $file = $dir .  'web/uploads/downloads/' . $pd->file_name;
            if (!  file_exists($file)){

                return false;
            }
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);

        $num = 0;
        $sheet = $spreadsheet->getActiveSheet();

        $spreadsheet->setActiveSheetIndex($num);

        $this->loadFile($sheet);


    }

    

    private function loadFile($sheet){



        for ($i = 1; $i <= $sheet->getHighestRow(); $i++) {

            $nColumn = Coordinate::columnIndexFromString(
                $sheet->getHighestColumn());

            $domain = ['name'=>'','testapi'=>''];
            for ($j = 1; $j <= $nColumn; $j++) {

                $value = $sheet->getCellByColumnAndRow($j, $i)->getValue();
                // if ($value === null) continue;

                if ($i > 2 ){

                    switch ($j){
                        case 4:
                            $name = trim( mb_strtolower( $value ) );
                            $domain['name'] = $name;
                            $domain['alias'] = $name;
                            break;
                        case 5:
                            if ($value !== null){
                                $alias = trim( mb_strtolower( $value ) );
                                $domain['alias'] = $alias;
                            }
                            break;
                    }

                }

            }//end col
            if ($domain['name'] /*&& $domain['testapi']*/){
                $this->loadFile[] = $domain;
            }




        }//end row



    }

    private function filterDomanis(){
        if ( ! ( is_array($this->loadFile) && count($this->loadFile))){
            return;
        }

        $old_domain = Domain::find()->all();

        $old_domain_name =  ArrayHelper::getColumn($old_domain,
            function ($data){
                return  trim( mb_strtolower( $data['name'] ));
            }
            ,
            'name');
        $old_domain_alias =  ArrayHelper::getColumn($old_domain,
            function ($data){
                return  trim( mb_strtolower( $data['alias'] ));
            }
            ,
            'name');



        foreach ($this->loadFile as $item){

            if ( ! in_array( $item['name'], $old_domain_name ) &&
                ! in_array( $item['alias'], $old_domain_alias )
            ){

                $this->needCreateDomains[] =  $item;
            }
        }



    }



    private function createDomainAndApplyOperations($list){

        $this->limitCreate  = 5;

        $this->domainCreateService = new DomainCreate();


        $cAdd = 0;

        foreach ($list as $num => $item){


            if ($cAdd >= $this->limitCreate){
                break;
            }

            $this->addLog('createDomainAndApplyOperations_'.$num,'Добавляю '.  $item['name']);

            $model = new Domain();
            $model->name = $item['name'];
            $model->alias = ( isset( $item['alias']) ) ? $item['alias']  : $item['name'] ;
            $model->publish = Domain::P_OK;
            $model->status = Domain::ST_A_NO;
            $model->deliv_main_city_min = (string) 3;
            $model->deliv_main_city_max = (string) 7;
            $model->deliv_sub_city_min = (string) 7;
            $model->deliv_sub_city_max = (string) 14;
            $model->date_publish_start = date( 'Y-m-d H:i:s' );
            $model->price_politics = 'site';
            $model->geo_domain = Domain::G_Y;
            $model->validation = Domain::V_Y;
            $model->topdomain = 'ru';


            $transaction = Yii::$app->db->beginTransaction(Transaction::SERIALIZABLE);

            try {
                if (! $this->domainCreateService->addDomain($model)){

                    $this->addLog('createDomainAndApplyOperations',print_r($model->getErrors()));
                    $transaction->rollBack();
                } else {
                    $transaction->commit();
                    $this->domainCreateService->checkUrl($model);

                    $cAdd ++;
                }

            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }


        }
        return null;
    }

    private function needCreateDomain(){

        $latest = (int) Domain::find()
            ->where( [ 'between' ,'date_publish_start',
                date('Y-m-d H:i:s',time() - ( ( 60 * 60 * 24 ) - 60 * 10 )   ),
                date('Y-m-d H:i:s' )])->andWhere(['geo_domain'=>Domain::G_Y])
            ->count();


        if ($latest >= $this->limitCreate ) {
            $this->addLog('needCreateDomain','сегодня добавляли');
            return;
        }



        $this->needCreate = true;
        $this->addLog('needCreateDomain','сегодня можно добавить');


    }

    private function addLog($fn,$msg){
        $this->logs[$fn] = $msg;
    }

    private function domainApiCreate($domain){

        $s = new DomainApiCreate();
        //$s->init_console();
        $s->init_console_v2();

        //  $domain = Domain::findOne(['name'=>'mahachkala']);

        $s->setDomain($domain);

        $domain->log = '';



//////////////
        $s->counterCreate(); //
        $s->goalsCreate();

        $s->gooAccountCreate(); //
        $s->gtmContainerCreate(); //



        $s->gtmVariablesImport(); //
        $s->gtmBuiltInVariablesImport();//
        $s->gtmContainerTriggersImport();//
        $s->gtmContainerTagImport();//

        $s->gtmContainerVersionCreate();//

        $s->gtmCodeUpdate();

        $s->snippetRefresh();


        $s->createYaSite(); //
        $s->yaSiteVerify(); //
        $s->yaAddSiteMap(); //



        $s->gooSiteAdd(); //
        $s->gooVerification(); //
        $s->gooAddSiteMap(); //

        $s->checkStatus();

        $domain->update(false,['log']);

        $this->logs['domainApiCreate'] = print_r( $s->logs );


    }







}