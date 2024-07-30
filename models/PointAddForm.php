<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\Transaction;
use app\models\User;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class PointAddForm extends Model
{

    public $id;
    public $amount;
    public $remark;

    public function rules()
    {
        return [
            [['id', 'amount', 'remark'], 'required'],
            ['remark', 'string'],
            ['amount', 'number', 'max' => 5000],
            [['id'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'id User',
            'remark' => 'Remark',
            'amount' => 'Point',
        ];
    }

    public function submit()
    {
        if ($this->validate()) {
            $user = User::find()->select(['id', 'username'])->where(['id' => $this->id])->asArray()->one();

            if ($user) {
                $data1 = ['username' => $user['username'], 'remark' => $this->remark];
                $data2 = ['name' => Yii::$app->user->identity->name];

                Transaction::createTransaction($this->id, Yii::$app->user->id, 28, $this->amount, $data2);
                $adminAmount = $this->amount / 0.8;
                Transaction::createTransaction(Yii::$app->user->id, $this->id, 25, $adminAmount, $data1);
            }
            return true;
        } else {
            return false;
        }
    }
}
