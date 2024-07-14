<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Transaction;
use app\components\Helper;
use Yii;

class TransactionSearch extends Transaction
{

    public $level_id;
    public $withdrawal;
    public $transfer;
    public $dateFilter;
    public $userFilter;
    public $buy;

    public function rules()
    {
        return [
            [['id', 'user_id', 'type_id', 'related_id', 'withdrawal'], 'integer'],
            [['remarks', 'date', 'date_success', 'updated_at', 'transfer'], 'safe'],
            [['userFilter', 'dateFilter'], 'string'],
            [['value', 'buy'], 'number'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        if (Yii::$app->user->identity->isAdmin() && !$this->buy)
            $query = Transaction::find()->where('user_id<>:idAdmin', [':idAdmin' => Yii::$app->params['idAdmin']]);
        else
            $query = Transaction::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }
        //$query->andFilterWhere(['like', 'value', $this->value]);
        // grid filtering conditions

        $query->andFilterWhere(['like', 'remarks', $this->remarks]);
        $query->andFilterWhere([
            'user_id' => Yii::$app->user->identity->isAdmin() ? ($this->userFilter ? $this->userFilter : $this->user_id) : Yii::$app->user->id,
            'type_id' => $this->type_id,
            'related_id' => $this->related_id,
            'date' => $this->date,
            'date_success' => $this->date_success,
        ]);

        if ($this->withdrawal == 1) {
            $query->andWhere('type_id=7 AND date_success IS NULL');
        } else if ($this->withdrawal == 2) {
            $query->andWhere('(type_id=7 AND date_success IS NOT NULL)');
        }

        if ($this->transfer) {
            $query->andWhere('(type_id=5 OR type_id=6)');
        }

        if (isset($this->dateFilter) && $this->dateFilter != '') {
            $date_explode = explode(" - ", $this->dateFilter);
            $date1 = Helper::dateToOri(trim($date_explode[0]), false);
            $date2 = Helper::dateToOri(trim($date_explode[1]), false) . ' 23:59:59';

            $query->andFilterWhere(['between', 'date', $date1, $date2]);
        }
        $query->orderBy('id DESC');
        return $dataProvider;
    }
}
