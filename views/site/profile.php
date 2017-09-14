<?php
/**
 * @author Arockia Johnson<johnson@arojohnson.tk>
 * @view - fbcallback
 * @controller - SiteController
 * @action - FbCallBack
 * @var $profile yii\web\View - Profile Object
 */
$this->title = $profile['name'] . ' - Everything is Social Media!';
?>
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12 col-md-offset-3">
        <div class="table-responsive">
            <table class="table table-bordered table-condensed table-striped">
                <tr>
                    <th rowspan="4" class="text-center">
                            <img src="<?= $profile['picture'] ?>" class="img-circle">
                    </th>
                </tr>
                <tr>
                    <th>Name </th>
                    <td><?= $profile['name'] ?></td>
                </tr>
                <tr>
                    <th>First Name </th>
                    <td><?= $profile['first_name'] ?></td>
                </tr>
                <tr>
                    <th>Last Name </th>
                    <td><?= $profile['last_name'] ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>