<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\Url;
use app\components\Helper;

/* @var $this yii\web\View */
/* @var $searchModel app\Models\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = "Members Listing";
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="row">
    <div class="col-sm-12">
        <section class="card">
            <section class="card-body">
                <div class="adv-table">

                    <?php Pjax::begin(['id' => 'list-data', 'timeout' => false, 'enablePushState' => false]); ?>

                    <?=
                        GridView::widget([
                            'dataProvider' => $dataProvider,
                            'filterModel' => $searchModel,
                            'bordered' => true,
                            'striped' => false,
                            'responsive' => false,
                            'floatHeader' => false,
                            'containerOptions' => ['style' => 'overflow: auto'], // only set when $responsive = false
                            'headerRowOptions' => ['class' => 'kartik-sheet-style'],
                            'filterRowOptions' => ['class' => 'kartik-sheet-style'],
                            'persistResize' => false,
                            'responsiveWrap' => false,
                            'columns' => [
                                ['class' => 'kartik\grid\SerialColumn'],
                                'username',
                                'name',
                                // [
                                //     'attribute' => 'state',
                                //     'filter' => Html::activeDropDownList($searchModel, 'state', ArrayHelper::map(User::find()->asArray()->groupBy('state')->where('state IS NOT NULL AND state<>"" AND level_id=:levelId', ['levelId' => $select])->all(), 'state', 'state'), ['class' => 'form-control', 'prompt' => 'Semua']),
                                // ],
                                [
                                    'attribute' => 'ewallet',
                                    'visible' => Yii::$app->user->identity->isAdmin() && $select == 5,
                                    'value' => function ($model) {
                                        return Helper::convertMoney($model->ewallet);
                                    }
                                ],
                                [
                                    'attribute' => 'pinwallet',
                                    'visible' => Yii::$app->user->identity->isAdmin() && $select != 5,
                                    'value' => function ($model) {
                                        return Helper::convertMoney($model->pinwallet);
                                    }
                                ],
                                [
                                    'attribute' => 'dateFilter',
                                    'label' => 'Date',
                                    'value' => function ($model) {
                                        if ($model->created_at && $model->created_at != Yii::$app->params['dateNull'])
                                            return date("d-m-Y h:iA", strtotime($model->created_at));
                                        else
                                            return '-';
                                    },
                                    'options' => [
                                        'format' => 'DD-MM-YYYY',
                                    ],
                                    'filterType' => GridView::FILTER_DATE_RANGE,
                                    'filterWidgetOptions' => ([
                                        'attribute' => 'dateFilter',
                                        'presetDropdown' => true,
                                        'convertFormat' => false,
                                        'pluginOptions' => [
                                            'separator' => ' - ',
                                            'format' => 'DD-MM-YYYY',
                                            'locale' => [
                                                'format' => 'DD-MM-YYYY'
                                            ],
                                        ],
                                        'pluginEvents' => [
                                            "apply.daterangepicker" => "function() { apply_filter('created_at') }",
                                        ],
                                    ])
                                ],
                                [
                                    'class' => 'kartik\grid\ActionColumn',
                                    'visible' => Yii::$app->user->identity->isAdmin(),
                                    'options' => ['style' => 'width:170px'],
                                    'template' => '{view} {update} {tree} {resetPass} {addWallet} {deductWallet} {transaction} {delete}', // the default buttons + your custom button
                                    'buttons' => [
                                        'view' => function ($url, $model, $key) {
                                            $url = Url::to(['user/view', 'select' => $model->level_id, 'id' => $model->id]);
                                            return Html::a('<i class="fa fa-eye success"></i>', $url, ['title' => 'Papar']);
                                        },
                                        'update' => function ($url, $model, $key) {
                                            $url = Url::to(['user/view', 'id' => $model->id, 'select' => $model->level_id, 'edit' => 1, 'username' => $model->username]);
                                            return Html::a('<i class="fa fa-user-edit"></i>', $url, ['title' => 'Kemaskini']);
                                        },
                                        'tree' => function ($url, $model, $key) {
                                            $url = Url::to(['network/index', 'id' => $model->id]);
                                            return Html::a('<i class="fa fa-project-diagram"></i>', $url, [
                                                'title' => 'Downline',
                                                'hidden' => !$model->isMember()
                                            ]);
                                        },
                                        'resetPass' => function ($url, $model, $key) {
                                            $url = Url::to(['reset-password', 'id' => $model->id]);
                                            return Html::a('<i class="fa fa-key"></i>', $url, [
                                                'title' => 'reset katakunci',
                                                'onclick' => "
                                                if (confirm('Anda pasti ingin menghapuskan katakunci ahli ini?')) {

                                                        $.ajax({
                                                            url:'$url',
                                                            //type: 'POST',
                                                            data:{'id':$model->id}
                                                        }).done(function(data) {
                                                            if(data==1){
                                                                alert('Katakunci ahli ini telah berjaya ditukar!');
                                                                $.pjax.reload({container: '#list-data', async: false});
                                                            }else{
                                                                alert(data);
                                                            }
                                                        });
                                                    }
                                                return false;",
                                            ]);
                                        },
                                        'addWallet' => function ($url, $model) {
                                            $url = Url::to(['user/add-wallet']);
                                            return Html::a('<i class="fa fa-plus-circle"></i>', '#', [
                                                'title' => Yii::t('yii', 'Tambah Pin Wallet'),
                                                'hidden' => $model->isMember(),
                                                'onclick' => "
                                var amount;
                                if (amount = prompt('Jumlah :','')) {
                                    if(amount&&amount!=''){

                                        $.ajax({
                                            url:'$url',
                                            //type: 'POST',
                                            data:{'Fun_addWallet[amount]':amount,'Fun_addWallet[user_id]':$model->id}
                                        }).done(function(data) {
                                            if(data==1){
                                                alert('Pin Wallet $model->username telah berjaya ditambah.');
                                                $.pjax.reload({container: '#list-data', async: false});
                                            }else{
                                                alert(data);
                                            }
                                        });
                                    }else{
                                        alert('Tiada jumlah yang dimasukkan.');
                                    }
                                }
                                return false;
                            ",
                                            ]);
                                        },
                                        'deductWallet' => function ($url, $model) {
                                            $url = Url::to(['user/deduct-wallet']);
                                            return Html::a('<i class="fa fa-minus-circle"></i>', '#', [
                                                'title' => Yii::t('yii', 'Potong Pin Wallet'),
                                                'hidden' => $model->isMember(),
                                                'onclick' => "
                                var amount=prompt('Jumlah Potongan :','');
                                var remark=prompt('Tujuan Potongan :','');
                                    if(amount&&amount!=''){
                                        $.ajax({
                                            url:'$url',
                                           //  type: 'POST',
                                            data:{'Fun_deductWallet[amount]':amount,'Fun_deductWallet[remark]':remark,'Fun_deductWallet[user_id]':$model->id}
                                        }).done(function(data) {
                                            if(data==1){
                                                alert('Pin Wallet $model->username telah berjaya dipotong.');
                                                $.pjax.reload({container: '#list-data', async: false});
                                            }else{
                                                alert(data);
                                            }
                                        });
                                    }else{
                                        alert('Tiada jumlah yang dimasukkan.');
                                    }
                                return false;
                            ",
                                            ]);
                                        },
                                        'transaction' => function ($url, $model, $key) {
                                            $url = Url::to(['transaction/index', 'id' => $model->id]);
                                            return Html::a('<i class="fa fa-outdent"></i>', $url, ['title' => 'Senarai Tansaksi']);
                                        },
                                        'delete' => function ($url, $model, $key) {
                                            $url = Url::to(['delete']);
                                            return Html::a('<i class="fa fa-trash-alt"></i>', $url, [
                                                'title' => 'Padam',
                                                'onclick' => "
                                                if (confirm('Anda pasti ingin membuang ahli ini?')) {

                                                        $.ajax({
                                                            url:'$url',
                                                            //type: 'POST',
                                                            data:{'id':$model->id}
                                                        }).done(function(data) {
                                                            if(data==1){
                                                                alert('Ahli telah berjaya dipadam.');
                                                                $.pjax.reload({container: '#list-data', async: false});
                                                            }else{
                                                                alert(data);
                                                            }
                                                        });
                                                    }
                                                return false;",
                                                'hidden' => !($model->upline_id == Yii::$app->params['idAdmin']) || $model->id == Yii::$app->params['idProgrammer']
                                            ]);
                                        },
                                    ]
                                ],
                            ],
                        ]);
                    ?>
                    <?php Pjax::end() ?>
                </div>
            </section>
    </div>
</div>
