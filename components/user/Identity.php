<?php

namespace components\user;

use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;

/**
 * Class Identity
 * @package components\user
 *
 * @property integer   $id
 * @property string    $username
 * @property string    $email
 * @property integer   $status
 * @property string    $role
 * @property string    $auth_key
 * @property string    $access_token
 * @property string    $password_hash
 * @property string    $password_reset_token
 * @property string    $email_confirm_token
 * @property integer   $created_at
 * @property integer   $updated_at
 *
 * @property string    $statusName
 * @property string    $roleName
 */
class Identity extends ActiveRecord implements IdentityInterface
{

    public $password_repeat = null;
    public $password = null;
    const SCENARIO_CREATE = 'ScenarioCreate';

    public static function tableName() { return 'user'; }

    public function rules()
    {
        return [
            [['username', 'email', 'status', 'role'], 'required'],
            [['status', 'created_at', 'updated_at'], 'integer'],
            [['username', 'email', 'role', 'password_hash', 'password_reset_token', 'email_confirm_token'], 'string', 'max' => 255],
            [['auth_key', 'access_token'], 'string', 'max' => 32],
            [['email'], 'trim'],
            [['email'], 'email'],
            [['email'], 'unique'],
            [['password_reset_token'], 'unique'],
            [['email_confirm_token'], 'unique'],
            [['password_repeat'], 'string'],
            [['password_repeat'], 'required', 'on' => self::SCENARIO_CREATE],
            [['password'], 'required', 'on' => self::SCENARIO_CREATE],
            [['password'], 'string', 'length' => [6, 60]],
            [['password'], 'compare', 'compareAttribute' => 'password_repeat'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Имя',
            'email' => 'Email',
            'status' => 'Статус',
            'role' => 'Роль',
            'auth_key' => 'Auth Key',
            'password_hash' => 'Password Hash',
            'password_reset_token' => 'Password Reset Token',
            'email_confirm_token' => 'Email Confirm Token',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлен',
            'statusName' => 'Статус',
            'roleName' => 'Роль',
            'password_repeat' => 'Повторите пароль',
            'password' => 'Пароль',
        ];
    }

    public function beforeSave($insert)
    {
        if ($this->password !== null) {
            $this->password_hash = Yii::$app->security->generatePasswordHash($this->password);
            $this->auth_key = Yii::$app->security->generateRandomString(32);
            $this->access_token = Yii::$app->security->generateRandomString(32);
        }
        return parent::beforeSave($insert);
    }

    /**
     * @return mixed
     */
    public function getStatusName()
    {
        return ArrayHelper::getValue(Access::statuses(), $this->status);
    }

    /**
     * @return mixed
     */
    public function getRoleName()
    {
        return ArrayHelper::getValue(Access::roles(), $this->role);
    }

    /**
     * @param string $password
     * @throws yii\base\Exception
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * @throws yii\base\Exception
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * @param string $password
     * @return bool
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * @param string $email
     * @return Identity|null
     */
    public static function findByEmail($email)
    {
        return self::findOne([
            'email' => $email,
            'status' => Access::STATUS_ACTIVE,
        ]);
    }

    /**
     * @throws yii\base\Exception
     */
    public function generateEmailVerificationToken()
    {
        $this->email_confirm_token = Yii::$app->security->generateRandomString();
    }

    /**
     * @param string $token
     * @return Identity|null
     */
    public static function findByVerificationToken($token)
    {
        return static::findOne([
            'email_confirm_token' => $token,
            'status' => Access::STATUS_ACTIVE,
        ]);
    }

    /**
     * @throws yii\base\Exception
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * remove password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    /**
     * @param string $token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = ArrayHelper::getValue(Yii::$app->params, 'user.passwordResetTokenExpire', 3600);
        return $timestamp + $expire >= time();
    }

    /**
     * @param string $token
     * @return Identity|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }
        return static::findOne([
            'password_reset_token' => $token,
            'status' => Access::STATUS_ACTIVE,
        ]);
    }


    /**
     * Identity Implements
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


    /**
     * @param int|string $id
     * @return Identity|IdentityInterface|null
     */
    public static function findIdentity($id)
    {
        return self::findOne([
            'id' => $id,
            'status' => Access::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param mixed $token
     * @param null $type
     * @return Identity|IdentityInterface|null
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return self::findOne([
            'access_token' => $token,
            'status' => Access::STATUS_ACTIVE,
        ]);
    }

    /**
     * @return int|mixed|string
     */
    public function getId()
    {
        return $this->primaryKey;
    }


    /**
     * @return string
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @param string $authKey
     * @return bool
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }
}