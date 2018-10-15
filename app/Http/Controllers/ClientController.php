<?php

namespace App\Http\Controllers;

use Teamspeak;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'offset' => 'numeric',
            'limit' => 'numeric'
        ]);

        $clients = Teamspeak::clientListDb(
            $request->input('offset', 0),
            $request->input('limit', 25)
        );
        $clients = collect($clients)->map(function($client) {
            return [
                'id' => $client['cldbid'],
                'uniqueid' => $client['client_unique_identifier']->toString(),
                'nickname' => $client['client_nickname']->toString(),
                'url' => route('clients.show',  $client['cldbid']),
                'created_at' => $client['client_created'],
                'last_connected' => $client['client_lastconnected'],
                'total_connections' => $client['client_totalconnections'],
                'description' => $client['client_description'],
                'last_ip' => $client['client_lastip'] ? $client['client_lastip']->toString() : null
            ];
        });

        return response()->json($clients->values()->all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $client = Teamspeak::clientGetById($id);
        }
        catch(\TeamSpeak3_Adapter_ServerQuery_Exception $e) {
            return response()->json([
                'message' => 'Client not found'
            ], 404);
        }

        return response()->json(self::nodeClientToArray($client));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * @param \TeamSpeak3_Node_Client $client
     * @return array
     * @throws \TeamSpeak3_Node_Exception
     */
    public static function nodeClientToArray(\TeamSpeak3_Node_Client $client) {
        $array = [];

        $array['id'] = $client->getId();
        $array['uniqueid'] = $client->getUniqueId();
        $array['nickname'] = $client->client_nickname->toString();
        $array['url'] = route('clients.show', $array['id']);
        $array['icon'] = $client->client_icon_id < 0 ? pow(2, 32)-($client->client_icon_id*-1) : $client->client_icon_id;
        $array['version'] = $client->client_version;
        $array['platform'] = $client->client_platform->toString();
        $array['country'] = strlen($client->client_country) ? trim($client->client_country) : null;
        $array['awaymessage'] = strlen($client->client_away_message) ? trim($client->client_away_message) : null;

        $memberof = [];
        foreach($client->memberOf() as $num => $group)
        {
            $memberof[$num]['name'] = trim($group->name);
            $memberof[$num]['icon'] = $group->iconid < 0 ? pow(2, 32)-($group->iconid*-1) : $group->iconid;
            $memberof[$num]['order'] = $group->sortid;
            $memberof[$num]['flags'] = 0;

            $memberof[$num]['flags'] += $group->namemode;
            $memberof[$num]['flags'] += $group->type == 2 ? 4 : 0;
            $memberof[$num]['flags'] += $group->type == 0 ? 8 : 0;
            $memberof[$num]['flags'] += $group->savedb ? 16 : 0;
            $memberof[$num]['flags'] += $group instanceof \TeamSpeak3_Node_Servergroup ? 32 : 0;
        }
        $array['memberof'] = $memberof;

        $array['badges'] = $client->getBadges();

        $array['flags'] = 0;
        $array['flags'] += $client->client_away ? 1 : 0;
        $array['flags'] += $client->client_is_recording ? 2 : 0;
        $array['flags'] += $client->client_is_channel_commander ? 4 : 0;
        $array['flags'] += $client->client_is_priority_speaker ? 8 : 0;
        $array['flags'] += $client->client_is_talker ? 16 : 0;
        $array['flags'] += $client->channelGetById($client->cid)->channel_needed_talk_power > $client->client_talk_power && !$client->client_is_talker ? 32  : 0;
        $array['flags'] += $client->client_input_muted || !$client->client_input_hardware ? 64  : 0;
        $array['flags'] += $client->client_output_muted || !$client->client_output_hardware ? 128 : 0;

        return $array;
    }
}
