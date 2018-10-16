<?php

namespace App\Contracts\TsWeb;

use App\Connection;

interface Factory
{
    public function connection(Connection $connection = null);
}