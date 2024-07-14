<?php

namespace app\controllers;

use Yii;
use app\models\User;
use app\models\UserSearch;
use yii\web\NotFoundHttpException;
use app\components\MemberController;
use app\models\Fun_addWallet;
use app\models\Fun_deductWallet;
use dominus77\sweetalert2\Alert;
use app\models\Transaction;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends MemberController
{

    public function init()
    {
        $session = Yii::$app->session;
        if (Yii::$app->user->identity->isAdmin() || Yii::$app->user->identity->isStockistState()) {
            $session['subMenu'] = [
                5 => ['label' => 'Members', 'url' => ['/user/index', 'select' => 5]],
                4 => ['label' => 'Mobile Stockist', 'url' => ['/user/index', 'select' => 4]],
                3 => ['label' => 'Stockist', 'url' => ['/user/index', 'select' => 3]],
                2 => ['label' => 'State Stockist', 'url' => ['/user/index', 'select' => 2]]
            ];
        } else if (Yii::$app->user->identity->isStockist()) {
            $session['subMenu'] = [
                5 => ['label' => 'Members', 'url' => ['/user/index', 'select' => 5]],
                4 => ['label' => 'Mobile Stockist', 'url' => ['/user/index', 'select' => 4]],
                3 => ['label' => 'Stockist', 'url' => ['/user/index', 'select' => 3]],
            ];
        } else if (Yii::$app->user->identity->isMobile()) {
            $session['subMenu'] = [
                5 => ['label' => 'Members', 'url' => ['/user/index', 'select' => 4]],
                4 => ['label' => 'Mobile Stockist', 'url' => ['/user/index', 'select' => 3]]
            ];
        } else {
            $session['subMenu'] = [];
        }
        if (!Yii::$app->user->identity->isMember())
            $session['subBtn'] = [['label' => '<i class="fa fa-user"></i>   Register', 'url' => ['/user/create', 'select' => Yii::$app->request->get('select')]]];
        else
            $session['subBtn'] = [];

        // $this->view->title = $session['subMenu'][$this->select];
    }


    public function actionIndex()
    {
        $searchModel = new UserSearch();
        if (!Yii::$app->user->identity->isAdmin())
            $searchModel->upline_id = Yii::$app->user->id;
        $searchModel->level_id = $this->select;

        // $searchModel->dateFilter = '1-' . date('m') . '-' . date('Y') . ' - ' . date('t') . '-' . date('m') . '-' . date('Y');
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'menusub' => $this->menusub,
            'select' => $this->select,
            'subtitle' => $this->getSubtitle(),
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id, $edit = false)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id, 'username' => $model->username]);
        }

        return $this->render('view', [
            'menusub' => $this->menusub,
            'select' => $this->select,
            'subtitle' => $this->getSubtitle(),
            'model' => $model,
            'edit' => $edit
        ]);
    }


    private function createPassword($length = 8, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
    {
        return substr(str_shuffle($chars), 0, $length);
    }

    public function actionResetPassword($id)
    {
        $pass = "123456";
        $user = User::findOne($id);
        $user->setPassword($pass);
        $user->generateAuthKey();

        if ($user->save(false)) {
            Yii::$app->session->setFlash(Alert::TYPE_SUCCESS, 'Your password has been changed!');
            return $this->goBack();
        } else {
            $this->errorSummary($user);
        }
        echo 1;
    }

    public function actionCreate()
    {

        $conn = Yii::$app->db;
        $trans = $conn->beginTransaction();
        try {
            $settings = $this->setting;

            $session = Yii::$app->session;
            $session['subBtn'] = [];
            $model = new User();
            $model->scenario = 'register';
            $model->activated = 1;
            $model->created_at = date('Y-m-d H:i:s');
            $model->register_id = Yii::$app->user->id;
            $model->username = $this->createUsername();
            $model->level_id = $this->select;
            if ($model->level_id != 5) {
                $model->uplineUsername = Yii::$app->params['usernameAdmin'];
                $model->upline_id = Yii::$app->params['idAdmin'];
            }
            if ($model->load(Yii::$app->request->post())) {
                $model->setPassword($model->password);
                $model->generateAuthKey();

                if ($model->level_id == 2) {
                    $model->price = $settings['harga_stokis_negeri'];
                } else if ($model->level_id == 3) {
                    $model->price = $settings['harga_stokis'];
                } else if ($model->level_id == 4) {
                    $model->price = $settings['harga_mobile'];
                } else if ($model->level_id == 5) {
                    $model->price = $settings['harga_ahli'];
                }

                if ($model->save()) {
                    $uplineLevel = $model->upline_id;

                    $dataTxt['username'] = $model->username;
                    $dataTxt['register_username'] = Yii::$app->user->identity->username;
                    if ($model->level_id == 5) {
                        $this->bonusProgrammer("Daftar : " . $model->username);
                        $this->runBonusLevel($model);
                    }
                    // if ($settings['bonus_sponsor']) {
                    //     Transaction::createTransaction($model->upline_id, $model->id, 1, $settings['bonus_sponsor'], $dataTxt);
                    // }
                    $register = Transaction::createTransaction(Yii::$app->user->id, $model->id, 12, $model->price, $dataTxt);
                    if ($register && $model->level_id != 5) {
                        Transaction::createTransaction($model->id, $register, 15, $model->price, $dataTxt);
                    }

                    Yii::$app->session->setFlash(Alert::TYPE_SUCCESS, 'Ahli ' . $model->username . ' telah berjaya didaftarkan.');

                    $trans->commit();
                    return $this->refresh();
                } else {
                    $this->errorSummary($model);
                }
            }
        } catch (Exception $e) {

            $trans->rollback();
        }

        return $this->render('create', [
            'menusub' => $this->menusub,
            'select' => $this->select,
            'model' => $model,
        ]);
    }

    public function actionUpdate($id, $username)
    {
        $model = $this->findModel($id, $username);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id, 'username' => $model->username]);
        }

        return $this->render('update', [
            'menusub' => $this->menusub,
            'select' => $this->select,
            'subtitle' => $this->getSubtitle(),
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $model = User::find()->where(['id' => $id, 'upline_id' => Yii::$app->params['idAdmin']])->one();
        if ($model && $model->delete()) {

            return $this->redirect(['index']);
        } else {
            echo "Ahli gagal dipadam!";
        }
        exit;
    }

    protected function findModel($id)
    {
        if (($model = User::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    public function actionAddWallet()
    {
        if (Yii::$app->request->get()) {
            $conn = Yii::$app->db;
            $trans = $conn->beginTransaction();
            try {
                $model = new Fun_addWallet;
                if ($model->load(Yii::$app->request->get()) && $model->addWallet()) {
                    $trans->commit();
                    echo 1;
                } else {
                    echo $this->errorSummary($model, ['js' => true]);
                }
            } catch (Exception $e) {

                $trans->rollback();
            }
        }
    }
    public function actionDeductWallet()
    {
        if (Yii::$app->request->get()) {
            $conn = Yii::$app->db;
            $trans = $conn->beginTransaction();
            try {
                $model = new Fun_deductWallet;
                if ($model->load(Yii::$app->request->get()) && $model->deductWallet()) {
                    $trans->commit();
                    echo 1;
                } else {
                    echo $this->errorSummary($model, ['js' => true]);
                }
            } catch (Exception $e) {

                $trans->rollback();
            }
        }
    }

    public function actionDownline()
    {
        $session = Yii::$app->session;
        $session['subMenu'] = [];
        $session['subBtn'] = [];

        $searchModel = new UserSearch();
        $searchModel->upline_id = Yii::$app->user->id;

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('downline', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
}
