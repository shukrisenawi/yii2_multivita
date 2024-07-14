<?php

use app\models\User;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>
<div class="row">
    <div class="col-md-12">
        <section class="card">
            <div class="revenue-head" style="background-color:#400040">
                <span style="background-color:#2b002b">
                    <i class="fa fa-user-secret"></i>
                </span>
                <h3>Senarai Top Stokis</h3>
            </div>

            <?php $form = ActiveForm::begin(); ?>
            <div class="row">
                <div class="col-lg-4"> <?= $form->field($model, 'from')->textInput(['type' => "date"]) ?></div>
                <div class="col-lg-4"><?= $form->field($model, 'to')->textInput(['type' => "date"]) ?></div>
                <div class="col-lg-2"><?= $form->field($model, 'limit')->textInput(['type' => "number"]) ?></div>
                <div class="col-lg-2">
                    <?= Html::submitButton(Yii::t('app', '<i class="fa fa-search"></i> Search'), ['class' => 'btn btn-primary', 'style' => 'margin-top:25px']) ?>
                </div>

            </div>
            <?php ActiveForm::end(); ?>

            <div class="card-body">
                <table class="table table-hover personal-task">
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($users as $value) {
                            $stockist[$i] = User::findIdentity($value->register_id); ?>
                        <tr>
                            <td><?= $i ?>.</td>
                            <td>
                                <?= $stockist[$i]->username ?>
                            </td>
                            <td>
                                <?= $stockist[$i]->name ?>
                            </td>
                            <td>
                                <?= $stockist[$i]->hp ?>
                            </td>
                            <td>
                                <span
                                    class="badge badge-pill badge-primary"><?= isset($value->total) ? $value->total : "" ?></span>
                            </td>
                        </tr>
                        <?php if ($stockist[$i]->address1) { ?>
                        <tr>
                            <td></td>
                            <td colspan="4" class="text-left">
                                <?= $stockist[$i]->address1 . ($stockist[$i]->address1 ? "<br>" . $stockist[$i]->address1 : "") ?>
                            </td>
                        </tr>
                        <?php } ?>
                        <?php
                            $i++;
                        } ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</div>
