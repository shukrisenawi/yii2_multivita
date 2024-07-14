<?php

namespace app\controllers;

use Yii;
use app\components\MemberController;
use app\models\User;
use app\models\SearchDateForm;

class StockistController extends MemberController
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
        $model = new SearchDateForm;
        $model->load(Yii::$app->request->post());
        if (!Yii::$app->request->post()) {
            $model->from =  date('Y-m-01');
            $model->to =  date('Y-m-d');
            $model->limit =  10;
        }

        $users = User::find()->select('COUNT(register_id) as total, register_id')->where('register_id > 0 AND created_at>=:from AND created_at<=:to', [':from' => $model->from . " 00:00:00", ':to' => $model->to . " 23:59:59"])->groupBy('register_id')->orderBy('COUNT(register_id) desc')->limit($model->limit)->all();

        return $this->render('index', ['users' => $users, 'model' => $model]);
    }
}
