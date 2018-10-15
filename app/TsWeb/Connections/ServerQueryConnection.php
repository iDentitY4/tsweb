<?php

namespace App\TsWeb;

use TeamSpeak3_Node_Abstract;
use App\Contracts\TsWeb\Connection;

class ServerQueryConnection implements Connection
{
    /**
     * The connection name for the queue.
     *
     * @var string
     */
    protected $connectionName;

    protected $serverNode;

    public function __construct(TeamSpeak3_Node_Abstract $serverNode)
    {
        $this->serverNode = $serverNode;
    }

    public function command($method, array $parameters = [])
    {
        return $this->serverNode->{$method}($parameters);
    }

    public function __call($name, $arguments)
    {
        $this->command($name, $arguments);
    }

    public function getNode()
    {
        return $this->serverNode;
    }
}