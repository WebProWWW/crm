<?php
/**
 * Author: Timur Valiev
 * Site: https://webprowww.github.io
 * 2019-01-09 21:56
 */

namespace components\user;

/**
 * Class Access
 * @package components\user
 */
class Access
{

    const ROLE_USER             = 'User';
    const ROLE_USER_DESC        = 'Пользователь';
    const ROLE_CUSTOMER         = 'Customer';
    const ROLE_CUSTOMER_DESC    = 'Заказчик';
    const ROLE_EXECUTOR         = 'Executor';
    const ROLE_EXECUTOR_DESC    = 'Исполнитель';
    const ROLE_ADMIN            = 'Admin';
    const ROLE_ADMIN_DESC       = 'Администратор';

    const STATUS_BLOCK          = 10;
    const STATUS_WAIT           = 20;
    const STATUS_ACTIVE         = 30;


    public static function statuses()
    {
        return [
            self::STATUS_BLOCK      => 'Заблокирован',
            self::STATUS_WAIT       => 'Активация',
            self::STATUS_ACTIVE     => 'Активен',
        ];
    }


    public static function roles()
    {
        return [
            self::ROLE_ADMIN        => self::ROLE_ADMIN_DESC,
            self::ROLE_CUSTOMER     => self::ROLE_CUSTOMER_DESC,
            self::ROLE_EXECUTOR     => self::ROLE_EXECUTOR_DESC,
            self::ROLE_USER         => self::ROLE_USER_DESC,
        ];
    }

}