<?php
/**
 * Created by PhpStorm.
 * User: 54xiake
 * Date: 2019-10-16
 * Time: 09:44
 */

namespace App\Common;


use stdClass;

class DbContext
{
    private $container = [];

    private static $instance;

    public static function getInstance()
    {
        if(!isset(self::$instance)){
            self::$instance = new DbContext();
        }
        return self::$instance;
    }

    function dbCon()
    {
        $cid = \co::getCid();
        if(!isset($this->container[$cid])){
            $this->container[$cid] = new stdClass();
            defer(function (){
                $this->destroy();
            });
        }
        return $this->container[$cid];
    }

    function destroy()
    {
        $cid = \co::getCid();
        if(!isset($this->container[$cid])){
            unset($this->container[$cid]);
        }
    }
}