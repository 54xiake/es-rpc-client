<?php
/**
 * Created by PhpStorm.
 * User: 54xiake
 * Date: 2019-10-16
 * Time: 09:43
 */

namespace App\Common;


class Db
{
    protected static $instance;
    protected $dbCon;

    function __construct()
    {
        /*
         * 我们这里用stdclass来模拟一个数据库连接
         */
        $this->dbCon = new \stdClass();
    }

    public static function getInstance()
    {
        if(!isset(self::$instance)){
            self::$instance = new db();
        }
        return self::$instance;
    }

    function dbCon()
    {
        return $this->dbCon;
    }
}

