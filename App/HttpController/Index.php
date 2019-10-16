<?php
/**
 * Created by PhpStorm.
 * User: 54xiake
 * Date: 2019-10-11
 * Time: 18:20
 */

namespace App\HttpController;


use App\Common\Db;
use EasySwoole\Component\Context\ContextManager;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Log\Logger;
use EasySwoole\Rpc\Config;
use EasySwoole\Rpc\NodeManager\RedisManager;
use EasySwoole\Rpc\Response;
use EasySwoole\Rpc\Rpc;
use Swoole\Coroutine\Channel;

class Index extends controller
{
    public $result = [];

    function index()
    {
        $config = new Config();
        $nodeManager = new RedisManager('127.0.0.1', 6379);
        $config->setNodeManager($nodeManager);
        $rpc = new Rpc($config);

        go(function () use ($rpc) {
            $client = $rpc->client();
            print_r($client);
            $client->addCall('UserService', 'register', ['arg1', 'arg2'])
                ->setOnFail(function (Response $response) {
                    print_r($response->toArray());
                })
                ->setOnSuccess(function (Response $response) {
                    print_r($response->toArray());
                });

            $client->exec();
        });
    }

    function car()
    {
        $channel = new Channel(CHANNEL_SIZE);
        $config = new Config();
        $nodeManager = new RedisManager('127.0.0.1', 6379);
        $config->setNodeManager($nodeManager);
//        $client = (new Rpc($config))->client();
        $client = Rpc::getInstance($config)->client();

        go(function () use ($client, $channel) {
            $client->addCall('EasySwoole.CarService', 'getCarInfo', ['arg1', 'arg2'], '2.0')
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
        $this->response()->withHeader('Content-type','application/json;charset=utf-8');
        $this->response()->write(json_encode($result));
    }

    function onException(\Throwable $throwable): void
    {
        print_r($throwable->getMessage());
    }

    function db() {
        $con = Db::getInstance()->dbCon();
        $con->key = 'new';
        $this->response()->write($con->key);
    }
}