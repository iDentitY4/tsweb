<?php

namespace App\Contracts\TsWeb;


interface Factory
{
    public function connection($name = null);
}