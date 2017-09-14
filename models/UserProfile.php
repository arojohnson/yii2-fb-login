<?php

/**
 * @author Arockia Johnson<johnson@arojohnson.tk>
 */

namespace app\models;

use Yii;
use yii\helpers\Security;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "{{%fb_user_profile}}".
 *
 * @property int $id
 * @property string $name
 * @property string $first_name
 * @property string $last_name
 * @property string $access_token
 * @property int $is_active
 * @property string $created_on
 * @property string $created_by
 * @property string $updated_on
 */
class UserProfile extends \yii\db\ActiveRecord implements IdentityInterface {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%fb_user_profile}}';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['is_active'], 'required'],
            [['is_active'], 'integer'],
            [['created_on', 'updated_on'], 'safe'],
            [['name', 'first_name', 'last_name'], 'string', 'max' => 100],
            [['created_by'], 'string', 'max' => 45],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'first_name' => Yii::t('app', 'First Name'),
            'last_name' => Yii::t('app', 'Last Name'),
            'access_token' => Yii::t('app', 'Access Token'),
            'is_active' => Yii::t('app', 'Is Active'),
            'created_on' => Yii::t('app', 'Created On'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_on' => Yii::t('app', 'Updated On'),
        ];
    }

    /**
     * 
     * @param $insert
     * @return boolean
     */
    public function beforeSave($insert) {
        if (!$this->isNewRecord) {
            $this->updated_on = date('Y-m-d H:i:s');
        }
        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    /* modified */
    public static function findIdentityByAccessToken($token, $type = null) {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey() {
        return $this->access_token;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey) {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::findOne(['fb_user_id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public function getId() {
        return $this->fb_user_id;
    }

}
