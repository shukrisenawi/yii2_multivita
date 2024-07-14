<?php

namespace app\controllers;

use Yii;
use app\components\MemberController;
use app\models\User;
use app\models\SearchDateForm;

class ListStockistController extends MemberController
{
    public function init()
    {
        $session = Yii::$app->session;
        $session['subMenu'] = [];
        $session['subBtn'] = [];

        // $this->view->title = $session['subMenu'][$this->select];
    }

    public function actionIndex()
    {
        $state = User::find()->where('(level_id=2 OR level_id=3 OR level_id=4) AND UPPER(name)<>"HEADQUATERS" AND id<>1032')->groupBy('state')->all();
        return $this->render('index', ['state' => $state]);
    }
}
