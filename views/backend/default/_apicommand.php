<?php
/**
 * Created by PhpStorm.
 * User: o.trushkov
 * Date: 20.07.18
 * Time: 13:57
 */
use Yandex\Metrica\Management\ManagementClient;


$counters = array();
$errorMessage = false;

$cookies = \Yii::$app->getRequest()->getCookies();

$yaAccessToken = $cookies->getValue('yaAccessToken');
$yaClientId = $cookies->getValue('yaClientId');

if (!$yaAccessToken|| !$yaClientId) {
    return '';
}
//$value = $cookies->getValue('my_cookie');

//Is auth
if ( $yaAccessToken && $yaClientId) {

    try {
        $managementClient = new ManagementClient($yaAccessToken);

        $paramsObj = new \Yandex\Metrica\Management\Models\CountersParams();
        $paramsObj
            /**
             * Тип счетчика. Возможные значения:
             * simple ― счетчик создан пользователем в Метрике;
             * partner ― счетчик импортирован из РСЯ.
             */
            ->setType(\Yandex\Metrica\Management\AvailableValues::TYPE_SIMPLE)

            /**
             * Один или несколько дополнительных параметров возвращаемого объекта
             */
            ->setField('goals,mirrors,grants,filters,operations');

        /**
         * @see http://api.yandex.ru/metrika/doc/beta/management/counters/counters.xml
         */
        $counters = $managementClient->counters()->getCounters($paramsObj)->getCounters();

    } catch (\Exception $ex) {

        $errorMessage = $ex->getMessage();
        if ($errorMessage === 'PlatformNotAllowed') {
            $errorMessage .= '<p>Возможно, у приложения нет прав на доступ к ресурсу. Попробуйте '
                . '<a href="' . rtrim(str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__), "/") . '/../OAuth/' . '">авторизироваться</a> и повторить.</p>';
        }
    }
}
?>
<div class="row">
    <p>api</p>
    <div class="col-xs-12">


<?php
if ($errorMessage) {
    ?>
    <div class="alert alert-danger"><?= $errorMessage ?></div>
    <?php
} else {
    ?>
    <div>
        <h3>Счетчики:</h3>
        <table id="countersTable" class="table table-striped table-bordered table-hover">
            <thead>
            <tr>
                <td>ID</td>
                <td>Статус</td>
                <td>Название</td>
                <td>Сайт</td>
                <td>Тип</td>
                <td>Владелец</td>
                <td>Права</td>
                <td>Действия</td>
                <td>Дополнения</td>
            </tr>
            </thead>
            <tbody>
            <?php
            if ($counters instanceof Traversable) {
                foreach ($counters as $counter) {
                    if ( strpos($counter->getSite(),$model->name . '.' ) === false) continue
                    ?>
                    <tr data-counter-id="<?= $counter->getId() ?>">
                        <td><?= $counter->getId() ?></td>
                        <td><?= $counter->getCodeStatus() ?></td>
                        <td><?= $counter->getName() ?></td>
                        <td><?= $counter->getSite() ?></td>
                        <td><?= $counter->getType() ?></td>
                        <td><?= $counter->getOwnerLogin() ?></td>
                        <td><?= $counter->getPermission() ?></td>
                        <td style="text-align: center">

                            <button type="button"
                                    class="btn btn-info showCounter">
                                <span title="Открыть" class="glyphicon glyphicon-eye-open"></span>
                            </button>

                            <button type="button"
                                    class="btn btn-warning updateCounter">
                                        <span title="Изменить"
                                              class="glyphicon glyphicon-edit"></span>
                            </button>
                            <button type="button" class="btn btn-danger deleteCounter">
                                            <span title="Удалить"
                                                  class="glyphicon glyphicon-trash"></span>
                            </button>
                        </td>
                        <td>
                            <a href="/examples/Metrica/Management/filters.php?counter-id=<?= $counter->getId(
                            ) ?>"
                               class="btn btn-primary">Фильтры</a><br/>
                            <a href="/examples/Metrica/Management/grants.php?counter-id=<?= $counter->getId(
                            ) ?>"
                               class="btn btn-success">Разрешения</a><br/>
                            <a href="/examples/Metrica/Management/operations.php?counter-id=
                                       <?= $counter->getId() ?>"
                               class="btn btn-info">Операции</a><br/>
                            <a href="/examples/Metrica/Management/goals.php?counter-id=<?= $counter->getId() ?>"
                               class="btn btn-warning">Цели</a>
                        </td>
                    </tr>

                    <?php
                }
            }
            ?>
            </tbody>
        </table>
        <button id="openAddCounterModal" type="button" class="btn btn-success">
                        <span title="Создать счетчик"
                              class="glyphicon glyphicon-plus"> Создать счетчик</span>
        </button>
    </div>
    <?php
}
?>





    </div>
</div>
