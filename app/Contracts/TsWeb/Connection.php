<?php

namespace App\Contracts\TsWeb;

interface Connection
{
    /**
     * Run a command against the Redis database.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function command($method, array $parameters = []);
}
