<?php

/**
 * @author Arockia Johnson<johnson@arojohnson.tk>
 * The controller describes the basic Facebook callback and profile view 
 */

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use Facebook\Facebook;
use app\models\UserProfile;

//use Facebook\Facebook;

class SiteController extends Controller {

    /**
     * @const - Facebook App ID
     * @var String 
     */
    CONST appId = '';//'1494774084136099';

    /**
     * @const - Facebook APP Secret
     * @var String 
     */
    CONST appSecret = '';

    /**
     *
     * @const - Facebook Call Back URL while OAuth 
     */
    CONST fbCallBackUrl = '';//'https://90129a69.ngrok.io/test/yii2-fb-login/web/site/fbcallback';

    /**
     * @const - Facebook app Version
     */
    CONST fbAppVersion = 'v2.4';

    /**
     *
     * @var String to show some alert message to the users
     */
    private $errMsg = 'Something went wrong while processing your request, Sorry for the inconvenience :(';

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions() {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * 
     * @return Facebook Instance
     */
    private function getFacebook() {
        return new Facebook([
            'app_id' => self::appId,
            'app_secret' => self::appSecret,
            'default_graph_version' => self::fbAppVersion,
        ]);
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex() {
        //Check if the user has already logged into the system
        if (!Yii::$app->user->isGuest) {
            $this->redirect(\yii\helpers\Url::to(['site/profile?id=' . md5(Yii::$app->user->id)]));
        }
        //Facebook instance
        $helper = $this->getFacebook()->getRedirectLoginHelper();
        $permissions = ['email'];
        $loginUrl = $helper->getLoginUrl(self::fbCallBackUrl, $permissions);
        return $this->render('index', ['loginUrl' => $loginUrl]);
    }

    /**
     * Handles the Facebook call back operations
     */
    public function actionFbcallback() {
        $accessToken = null;
        $helper = $this->getFacebook()->getRedirectLoginHelper();
        $error_message = Yii::$app->request->get('error_message');
        try {
            $accessToken = $helper->getAccessToken();
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            Yii::error('Graph returned an error: ' . $e->getMessage());
            return $this->render('error', ['message' => ($error_message !== '') ? $error_message : $this->errMsg]);
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            Yii::error('Facebook SDK returned an error: ' . $e->getMessage());
            return $this->render('error', ['message' => ($error_message !== '') ? $error_message : $this->errMsg]);
        }
        if ($accessToken) {
            $profile = $this->upsertUserProfile($accessToken);
            //Save the profile
            $profile->save();
        } else {
            Yii::error('Invalid Access Token Error');
            return $this->render('error', ['message' => 'Invalid Access Token Error ' . $this->errMsg]);
        }
        return $this->redirect('profile?id=' . md5($profile->fb_user_id));
    }

    /**
     * 
     * @param Facebook User Object $res
     */
    private function upsertUserProfile($accessToken) {
        try {
            $response = $this->getFacebook()->get('/me?fields=id,name,picture,first_name,last_name', $accessToken);
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            Yii::error('Graph returned an error: ' . $e->getMessage());
            return $this->render('error', ['message' => $this->errMsg]);
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            Yii::error('Facebook SDK returned an error: ' . $e->getMessage());
            return $this->render('error', ['message' => $this->errMsg]);
        }
        $user = $response->getGraphUser();
        $isUserProfileExists = UserProfile::findOne(['fb_user_id' => $user['id']]);
        $userProfile = $isUserProfileExists ? $isUserProfileExists : new UserProfile();
        $userProfile->name = $user['name'];
        $userProfile->fb_user_id = $user['id'];
        $userProfile->first_name = $user['first_name'];
        $userProfile->last_name = $user['last_name'];
        $userProfile->access_token = $accessToken->getValue();
        $userProfile->picture = $user['picture']['url'];
        $userProfile->is_active = 1;
        return $userProfile;
    }

    /**
     * 
     * @param type $id
     */
    public function actionProfile($id = null) {
        //User profile page to show the user details
        $profile = UserProfile::findOne(['md5(fb_user_id)' => $id]);
        if (Yii::$app->user->isGuest) {
            Yii::$app->user->login($profile);
        }
        if ($profile && Yii::$app->user->isGuest && !Yii::$app->user->login($profile)) {
            return $this->render('error', ['message' => '403 Access Forbidden', 'name' => 'Access Forbidden']);
        } else {
            return $this->render('profile', ['profile' => $profile]);
        }
    }

    /**
     * Login action.
     *
     * @return Response
     */
    public function actionLogin() {
        return $this->redirect(['index']);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout() {
        Yii::$app->user->logout();
        return $this->redirect(['index']);
    }

    /**
     * Facebook callback Deauthorize action
     * @return Response
     */
    public function actionDeauthorize() {
        $fbParsedData = self::parseHashedFBRequest();
        if ($fbParsedData === false) {
            throw new \yii\web\HttpException('500', 'There was a problem with the request format.', 500);
        } else {
            //Saving the User active status to the table
            $userProfile = UserProfile::findOne(['fb_user_id' => $fbParsedData['user_id']]);
            $userProfile->is_active = 0;
            $userProfile->save();
        }
    }

    /**
     * 
     * @return boolean
     */
    private static function parseHashedFBRequest() {
        $fb_req_hashed = Yii::$app->request->post('signed_request');
        if (isset($fb_req_hashed)) {
            list($fb_encoded_hash, $payload) = explode('.', $fb_req_hashed, 2);

            // decode the data
            $sig = self::base64_url_decode($fb_encoded_hash);
            $data = json_decode(self::base64_url_decode($payload), true);
            //Verifying the hash SHA256
            if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
                Yii::error('Invalid Hashing');
                return false;
            }
            // Adding the verification of the fb_encoded_hash below
            $isHashVerified = hash_hmac('sha256', $payload, Yii::app()->facebook->secret, $raw = true);
            if ($sig !== $isHashVerified) {
                Yii::error('Invalid JSON Hashing');
                return false;
            }
            return $data;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param String $input
     * @return String
     */
    private static function base64_url_decode($input) {
        return base64_decode(strtr($input, '-_', '+/'));
    }

}
