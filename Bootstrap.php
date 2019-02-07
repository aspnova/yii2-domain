<?php

namespace aspnova\domain;

use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $app->setModule('domain', 'aspnova\domain\Module');
    }
}