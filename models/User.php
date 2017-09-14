<?php

namespace app\models;

use yii\helpers\Security;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "tbl_user".
 *
 * @property string $userid
 * @property string $username
 * @property string $password
 */
class User extends \yii\db\ActiveRecord implements IdentityInterface {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'users';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['username', 'password'], 'required'],
            [['username'], 'match', 'pattern' => '/^[a-z0-9_.]+$/', 'message' => $this->getAttributeLabel('username') . ' is invalid, Only alpha numeric and ._ characters are allowed'],
            [['username', 'password'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'userid' => 'Userid',
            'username' => 'Username',
            'password' => 'Password',
            'access_token' => 'Access Token'
        ];
    }

    /** INCLUDE USER LOGIN VALIDATION FUNCTIONS* */

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::findOne($id);
    }

    /**
     * @inheritdoc
     */
    /* modified */
    public static function findIdentityByAccessToken($token, $type = null) {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * Finds user by username
     *
     * @param  string      $username
     * @return static|null
     */
    public static function findByUsername($username) {
        return static::findOne(['username' => $username]);
    }

    /**
     * Finds user by password reset token
     *
     * @param  string      $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token) {
        $expire = \Yii::$app->params['user.passwordResetTokenExpire'];
        $parts = explode('_', $token);
        $timestamp = (int) end($parts);
        if ($timestamp + $expire < time()) {
            // token expired
            return null;
        }

        return static::findOne([
                    'password_reset_token' => $token
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getId() {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey() {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey) {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password) {
        return $this->password === sha1($password);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->password_hash = Security::generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey() {
        $this->auth_key = Security::generateRandomKey();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken() {
        $this->password_reset_token = Security::generateRandomKey() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken() {
        $this->password_reset_token = null;
    }

    public function beforeSave($insert) {
        parent::beforeSave($insert);
        $this->password = sha1($this->password);
        return true;
    }

}
