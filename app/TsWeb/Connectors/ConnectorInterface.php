<?php

namespace App\TsWeb\Connectors;

interface ConnectorInterface
{
    /**
     * Establish a ts connection.
     *
     * @param  array  $config
     * @return \App\Contracts\TsWeb\Server
     */
    public function connect(array $config);
}
