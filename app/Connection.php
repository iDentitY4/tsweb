<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    public function host()
    {
        return $this->belongsTo(Host::class);
    }

    public function server()
    {
        return $this->belongsTo(VirtualServer::class);
    }

    public static function default()
    {
        return new static();
    }
}
