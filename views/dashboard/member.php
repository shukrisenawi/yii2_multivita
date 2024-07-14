<?php

use app\components\Helper;

$user = Yii::$app->user->identity;
?>

<div class="row">
    <?php if (!Yii::$app->user->identity->maintain && date("Y-m") != date("Y-m", strtotime(Yii::$app->user->identity->created_at))) { ?>
    <div class="col-sm-12">
        <div class="alert alert-danger text-center"><strong>You must buy at least 1 multivita for get repurchase
                bonus</strong> </div>
    </div>
    <?php } ?>
    <div class="col-lg-4">
        <!--user info table start-->
        <div class="state-overview">
            <section class="card">
                <div class="symbol yellow">
                    <i class="fa fa-hand-holding-usd"></i>
                </div>
                <div class="value">
                    <h1 class=" count4">
                        <?= $user->ewallet ?>
                    </h1>
                    <p>E-Wallet</p>
                </div>
            </section>
        </div>


        <section class="card">
            <div class="card-body bg-danger">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="task-progress text-light">
                            <h1><a href="#" class="text-white">News</a></h1>

                        </div>
                    </div>
                </div>
            </div>
            <table class="table table-hover personal-task">
                <tbody>
                    <?php
                    if ($news) {
                        ?>
                    <?php
                        foreach ($news as $value) { ?>

                    <tr>
                        <td width="5px">
                            <i class=" fa fa-angle-double-right"></i>
                        </td>
                        <td style="text-align:left"><?= $value->title  ?></td>
                        <td><?= Helper::viewDate($value->created_at) ?></td>
                    </tr>
                    <?php if ($value->news) { ?>
                    <tr>
                        <td></td>
                        <td style="text-align:left" colspan="2">
                            <?= $value->news ?>
                        </td>
                    </tr>
                    <?php } ?>
                    <?php } ?>
                    <?php } else { ?>
                    <tr>
                        <td>
                            <i class=" fa fa-times"></i>
                        </td>
                        <td style="text-align:left">Empty</td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>
        <!--user info table end-->
    </div>
    <div class="col-lg-8">
        <!--work progress start-->
        <section class="card">
            <div class="card-body bg-success">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="task-progress">
                            <h1><a href="#" class="text-white">Transactions</a></h1>
                            <p class="text-white">10 latest transactions</p>

                        </div>
                    </div>
                </div>
            </div>
            <?php if (isset($transaction) && $transaction) { ?>
            <table class="table table-striped">
                <thead>
                    <?php
                        foreach ($transaction as $valueTransaction) {
                            ?>
                    <tr>
                        <td><?= $valueTransaction['remarks'] ?></td>
                        <td><?= Helper::convertMoney($valueTransaction['value']) ?></td>
                        <td style="text-align:right">
                            <?= Helper::viewDate($valueTransaction['date'], 'd-m-Y, h:iA') ?>
                        </td>
                    </tr>

                    <?php
                        } ?>
                </thead>
            </table>
            <?php } else { ?>
            <div class="m-list-timeline__item text-center" style="padding:20px">
                <span class="m-list-timeline__text">Empty</span>
            </div>
            <?php } ?>
        </section>
        <!--work progress end-->
    </div>
</div>
