<?php

namespace App\Http\Controllers;

use Teamspeak;
use Illuminate\Http\Request;

class PokeController extends Controller
{

    protected $server;

    public function __construct()
    {
        $this->server = Teamspeak::factory('serverquery://serveradmin:AwxRnhwR@sksystems.de:10011/?server_port=9987');
    }

    public function poke()
    {
        try
        {
            return view('clients', ['clients' => $this->server->clientList()]);
        }
        catch(TeamSpeak3_Exception $e)
        {
            // print the error message returned by the server
            return "Error " . $e->getCode() . ": " . $e->getMessage();
        }
    }
}
