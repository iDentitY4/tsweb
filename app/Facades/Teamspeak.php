<?php namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Teamspeak extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'teamspeak';
    }
}