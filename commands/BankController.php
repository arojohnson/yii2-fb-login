<?php

/**
 * @author Arockia Johnson<johnson@arojohnson.tk>
 */

namespace app\commands;

use yii\console\Controller;

class BankController extends Controller {

    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionIndex($message = 'hello world') {
        echo $message . "\n";
    }

    /**
     * @action - Action to create new account
     */
    public function actionCreate(array $accounts) {
        
        echo '<pre>'; print_r($accounts); echo '</pre>'; die;
    }

}
