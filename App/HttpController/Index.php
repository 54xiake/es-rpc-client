<?php
/**
 * Created by PhpStorm.
 * User: 54xiake
 * Date: 2019-10-11
 * Time: 18:20
 */

namespace App\HttpController;


use App\Common\Db;
use App\Common\DbContext;
use EasySwoole\Component\Context\ContextManager;
use EasySwoole\EasySwoole\Config;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Log\Logger;
use EasySwoole\Rpc\NodeManager\RedisManager;
use EasySwoole\Rpc\Response;
use EasySwoole\Rpc\Rpc;
use Swoole\Coroutine\Channel;

class Index extends controller
{
    public $result = [];

    function index()
    {
        $result = $this->getRpcResponse('UserService', 'register', ['arg1', 'arg2']);
        $this->response()->withHeader('Content-type','application/json;charset=utf-8');
        $this->response()->write(json_encode($result));
    }

    function car()
    {
        $result = $this->getRpcResponse('EasySwoole.CarService', 'getCarInfo', ['arg1', 'arg2'], '2.0');
        $this->response()->withHeader('Content-type','application/json;charset=utf-8');
        $this->response()->write(json_encode($result));

    }

    private function getRpcResponse($service, $action, $params, $version='1.0') {
        $channel = new Channel(CHANNEL_SIZE);
        $config = new \EasySwoole\Rpc\Config();
        $redisConfig = Config::getInstance()->getConf('REDIS');
        $nodeManager = new RedisManager($redisConfig['host'], $redisConfig['port']);
        $config->setNodeManager($nodeManager);
//        $client = (new Rpc($config))->client();
        $client = Rpc::getInstance($config)->client();

        go(function () use ($client, $channel,$service, $action, $params, $version) {
            $client->addCall($service, $action, $params, $version)
                ->setOnFail(function (Response $response) use($channel) {
                    $result = $response->toArray();
                    $channel->push($result);
                })
                ->setOnSuccess(function (Response $response) use($channel) {
                    $result = $response->toArray();
                    $channel->push($result);
                });
            $client->exec();
        });

        $result = $channel->pop();
        return $result;
    }


    function onException(\Throwable $throwable): void
    {
        print_r($throwable->getMessage());
    }

    function db() {
        $con = Db::getInstance()->dbCon();
        $con->key = 'new';
        $this->response()->write($con->key);

        //协程2的数据污染到了协程1的数据
        go(function (){
            go(function (){
                Db::getInstance()->dbCon()->key = 'one';
                //假设这sql执行了1s
                \co::sleep(1);
                var_dump(Db::getInstance()->dbCon()->key);
            });
            go(function (){
                Db::getInstance()->dbCon()->key = 'two';
                //假设这sql执行了0.1s
                \co::sleep(0.1);
                var_dump(Db::getInstance()->dbCon()->key);
            });
        });

        //上下文管理器
        //用每个协程的id,来作为每个协程栈的数据token,
        //用了defer方法，实现了每个协程退出的时候的数据自动清理，从而避免了内存泄露
        go(function (){
            go(function (){
                DbContext::getInstance()->dbCon()->key = 'one';
                //假设这sql执行了1s
                \co::sleep(1);
                var_dump(DbContext::getInstance()->dbCon()->key);
            });
            go(function (){
                DbContext::getInstance()->dbCon()->key = 'two';
                //假设这sql执行了0.1s
                \co::sleep(0.1);
                var_dump(DbContext::getInstance()->dbCon()->key);
            });
        });
    }
}