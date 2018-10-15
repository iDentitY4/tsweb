<?php

namespace App\TsWeb\Connectors;

use TeamSpeak3_Adapter_ServerQuery;
use TeamSpeak3_Node_Server;
use App\TsWeb\ServerQueryConnection;

class ServerQueryConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \App\Contracts\TsWeb\Connection
     */
    public function connect(array $config)
    {
        $config = $this->getDefaultConfiguration($config);

        $adapter = new TeamSpeak3_Adapter_ServerQuery($config);

        $node = $adapter->getHost();

        if(isset($config['username']) && isset($config['password']))
        {
            $node->login($config['username'], $config['password']);
        }

        if(isset($config["nickname"]))
        {
            $node->setPredefinedQueryName($config["nickname"]);
        }

        /*if($uri->getFragment() == "use_offline_as_virtual")
        {
            $node->setUseOfflineAsVirtual(TRUE);
        }
        elseif($uri->hasQueryVar("use_offline_as_virtual"))
        {
            $node->setUseOfflineAsVirtual($uri->getQueryVar("use_offline_as_virtual") ? TRUE : FALSE);
        }

        if($uri->getFragment() == "clients_before_channels")
        {
            $node->setLoadClientlistFirst(TRUE);
        }
        elseif($uri->hasQueryVar("clients_before_channels"))
        {
            $node->setLoadClientlistFirst($uri->getQueryVar("clients_before_channels") ? TRUE : FALSE);
        }

        if($uri->getFragment() == "no_query_clients")
        {
            $node->setExcludeQueryClients(TRUE);
        }
        elseif($uri->hasQueryVar("no_query_clients"))
        {
            $node->setExcludeQueryClients($uri->getQueryVar("no_query_clients") ? TRUE : FALSE);
        }*/

        if(isset($config["server_id"]))
        {
            $node = $node->serverGetById($config["server_id"]);
        }
        elseif(isset($config["server_uid"]))
        {
            $node = $node->serverGetByUid($config["server_uid"]);
        }
        elseif(isset($config["server_port"]))
        {
            $node = $node->serverGetByPort($config["server_port"]);
        }
        elseif(isset($config["server_name"]))
        {
            $node = $node->serverGetByName($config["server_name"]);
        }

        if($node instanceof TeamSpeak3_Node_Server)
        {
            if(isset($config["channel_id"]))
            {
                $node = $node->channelGetById($config["channel_id"]);
            }
            elseif(isset($config["channel_name"]))
            {
                $node = $node->channelGetByName($config["channel_id"]);
            }

            if(isset($config["client_id"]))
            {
                $node = $node->clientGetById($config["client_id"]);
            }
            if(isset($config["client_uid"]))
            {
                $node = $node->clientGetByUid($config["client_uid"]);
            }
            elseif(isset($config["client_name"]))
            {
                $node = $node->clientGetByName($config["client_name"]);
            }
        }

        return new ServerQueryConnection($node);
    }

    /**
     * Get the default configuration for serverquery.
     *
     * @param  array  $config
     * @return array
     */
    protected function getDefaultConfiguration(array $config)
    {
        return array_merge([
            'timeout' => '10',
            'blocking' => 1,
            'tls' => 0,
            'ssh' => 0
        ], $config);
    }
}
