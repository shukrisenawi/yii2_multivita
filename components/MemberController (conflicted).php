<?php

namespace app\components;

use yii\web\Controller;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use app\models\Settings;
use app\models\Transaction;
use Yii;
use app\models\User;
use dominus77\sweetalert2\Alert;
use app\widgets\Menu;
use yii\filters\AccessControl;

class MemberController extends Controller
{

    protected $user;
    public $menusub;
    public $select;
    protected $setting;
    protected $allow;
    protected $actionAllow;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => in_array($this->id, $this->allow),
                        'actions' => $this->actionAllow,
                        'roles' => ['@'],
                    ],

                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        $menu = new Menu;
        $this->allow =  $menu->checkAllow();
        $this->actionAllow = "";

        if ($this->id == "dashboard") {

            if (Yii::$app->user->identity->isAdmin())
                $this->actionAllow = ['index', 'admin'];
            else if (Yii::$app->user->identity->isProgrammer())
                $this->actionAllow = ['index', 'programmer'];
            else if (!Yii::$app->user->identity->isMember())
                $this->actionAllow = ['index', 'stockist'];
            else
                $this->actionAllow = ['index', 'member'];
        } else if ($this->id == "user") {
            if (Yii::$app->user->identity->isMember())
                $this->actionAllow = ['downline'];
        } else if ($this->id == "withdrawal") {
            if (!Yii::$app->user->identity->isAdmin())
                $this->actionAllow = ['index', 'create'];
        }


        date_default_timezone_set(Yii::$app->params['utc']);
        $session = Yii::$app->session;
        $this->user = Yii::$app->user->getIdentity();
        $getSelect = Yii::$app->request->get('select');
        $this->menusub = $session['subMenu'];
        if ($this->menusub) {
            reset($this->menusub);
            $this->select = $getSelect && array_key_exists($getSelect, $this->menusub) ? $getSelect : ((isset($this->menusub[key($this->menusub)]) ? key($this->menusub) : null));
        }

        $menuSelect = isset($session['subMenu'][$this->select]) ? $session['subMenu'][$this->select] : null;
        if ($menuSelect)
            $this->view->title = isset($menuSelect['title']) ? $menuSelect['title'] : strtoupper($menuSelect['label']);
        else
            $this->view->title = null;


        $this->setting = Settings::value();

        return parent::beforeAction($action);
    }

    public function goHome()
    {
        if (!Yii::$app->user->isGuest) {
            if ($this->user->isAdmin() && $this->action->id != 'admin') {
                return $this->redirect(['dashboard/admin']);
            } else if ($this->user->isStockistState() && $this->action->id != 'member') {
                return $this->redirect(['dashboard/stockist']);
            } else if ($this->user->isStockist() && $this->action->id != 'stockist') {
                return $this->redirect(['dashboard/stockist']);
            } else if ($this->user->isMobile() && $this->action->id != 'stockist') {
                return $this->redirect(['dashboard/stockist']);
            } else if ($this->user->isMember() && $this->action->id != 'member') {
                return $this->redirect(['dashboard/member']);
            } else if ($this->user->isProgrammer() && $this->action->id != 'programmer') {
                return $this->redirect(['dashboard/programmer']);
            }
        } else {
            return $this->redirect(['site/index']);
        }
    }



    protected function createUsername($length = 6)
    {
        if ($this->select == 1)
            $first = 'AD';
        else if ($this->select == 2)
            $first = 'STN';
        else if ($this->select == 3)
            $first = 'STK';
        else if ($this->select == 4)
            $first = 'STM';
        else
            $first = 'M';

        $username = '';
        $check = true;
        $str = '';

        while ($check) {
            $rand = rand(0, 999999);
            $lengtNo = strlen($rand);
            for ($i = 0; $i <= ($length - $lengtNo); $i++) {
                $str .= '0';
            }
            $username = $first . $str . $rand;
            $check = User::find()->where(['username' => $username])->exists();
        }
        return $username;
    }
    protected function getSubtitle()
    {
        return ($this->select && array_key_exists($this->select, $this->menusub)) ? $this->menusub[$this->select]['label'] : null;
    }

    protected function errorSummary($models, $options = [])
    {
        $header = isset($options['header']) ? $options['header'] : Yii::t('yii', 'Please fix the following errors:');
        $footer = ArrayHelper::remove($options, 'footer', '');
        $encode = ArrayHelper::remove($options, 'encode', true);
        unset($options['header']);

        $onlyFirst = isset($options['onlyFirst']) ? $options['onlyFirst'] : true;
        unset($options['onlyFirst']);

        $lines = [];
        if (!is_array($models)) {
            $models = [$models];
        }
        foreach ($models as $model) {
            /* @var $model Model */
            if ($onlyFirst === true) {
                foreach ($model->getFirstErrors() as $error) {
                    $lines[] = $encode ? Html::encode($error) : $error;
                }
            } else {
                foreach ($model->getErrors() as $attribute => $errors) {
                    foreach ($errors as $error) {
                        $lines[] = $encode ? Html::encode($error) : $error;
                    }
                }
            }
        }

        if (!isset($options['js']) || !$options['js']) {
            if (empty($lines)) {
                // still render the placeholder for client-side validation use
                $content = '<ul></ul>';
                $options['style'] = isset($options['style']) ? rtrim($options['style'], ';') . '; display:none' : 'display:none';
            } else {
                $content = '<ul><li style="font-weight:bold;text-align:left">' . implode("</li>\n<li style='font-weight:bold;text-align:left'>", $lines) . '</li></ul>';
            }
            $html = Html::tag('div', $header . $content . $footer, $options);
            Yii::$app->session->setFlash(Alert::TYPE_ERROR, ['options' => ['html' => $html]]);
        } else {
            return $header . "\n" . implode("\n ", $lines) . "\n" . $footer;
        }
    }

    protected function printData($data)
    {
        print("<pre>" . print_r($data, true) . "</pre>");
    }

    protected function runBonusLevel($model, $quantity = 1, $maintain = 0)
    {
        if (!$maintain) {
            $uplineLevel = $model->upline_id;
            $settings = $this->setting;
            for ($i = 1; $i <= $settings['max_level']; $i++) {

                $dataTxt['level'] = null;
                $dataTxt['username'] = $model->username;

                if ($settings['bonus_level' . $i] && $uplineLevel && $i <= User::maxUplineDownline($uplineLevel)) {
                    $dataTxt['level'] = $i . ($maintain ? " (maintain)" : "");
                    Transaction::createTransaction($uplineLevel, $model->id, 2, $settings['bonus_level' . $i] * $quantity, $dataTxt);
                }
                $checkUpline[$i] = User::find()->where(['id' => $uplineLevel])->select('upline_id')->asArray()->one();
                $uplineLevel = $checkUpline[$i]['upline_id'];
            }
        }
    }

    protected function bonusProgrammer($username)
    {
        $data['remark'] = $username;
        Transaction::createTransaction(Yii::$app->params['idProgrammer'], Yii::$app->params['idAdmin'], 19, Yii::$app->params['bonusProgrammer'], $data);
    }
}
