<?php

namespace App\TsWeb\Events;

class ClientList
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The job instance.
     *
     * @var \Illuminate\Contracts\Queue\Job
     */
    public $clients;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    public function __construct($connectionName, $clients)
    {
        $this->clients = $clients;
        $this->connectionName = $connectionName;
    }
}
