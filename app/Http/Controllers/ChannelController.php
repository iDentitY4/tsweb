<?php

namespace App\Http\Controllers;

use Teamspeak;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $withClients = $request->input('show_clients', false);

        $channels = Teamspeak::channelList();
        $channels = collect($channels)->map(function($channel) use ($withClients) {
            return $this->nodeChannelToArray($channel, $withClients);
        });

        return response()->json($channels->values()->all());
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
    public function show(Request $request, $id)
    {
        $withClients = $request->input('show_clients', false);

        try {
            $channel = Teamspeak::channelGetById($id);
        }
        catch(\TeamSpeak3_Adapter_ServerQuery_Exception $e) {
            return response()->json([
                'message' => 'Channel not found'
            ], 404);
        }

        return response()->json(self::nodeChannelToArray($channel, $withClients));
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
        $validated = $request->validate([
            'channel_name' => ''
        ]);

        try {
            $channel = Teamspeak::channelGetById($id);
        }
        catch(\TeamSpeak3_Adapter_ServerQuery_Exception $e) {
            return response()->json([
                'message' => 'Channel not found'
            ], 404);
        }

        $channel->modify($validated);

        return response()->json([
            'message' => 'Update successful'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $channel = Teamspeak::channelGetById($id);
        }
        catch(\TeamSpeak3_Adapter_ServerQuery_Exception $e) {
            return response()->json([
                'message' => 'Channel not found'
            ], 404);
        }

        $channel->delete();
        return response('', 204);
    }

    /**
     * @param \TeamSpeak3_Node_Channel $channel
     * @param bool $withClients
     * @return array
     */
    public static function nodeChannelToArray(\TeamSpeak3_Node_Channel $channel, bool $withClients)
    {
        $array = [];

        $array['id'] = $channel->getId();
        $array['name'] = $channel->channel_name->toString();
        $array['url'] = route('channels.show', $array['id']);
        $array['icon'] = $channel->isSpacer() ? 0 : $channel->channel_icon_id;
        $array['path'] = trim($channel->getPathway());
        $array['topic'] = strlen($channel->channel_topic) ? trim($channel->channel_topic) : null;
        $array['codec'] = $channel->channel_codec;
        if($withClients) {
            $clients = $channel->clientList();
            $clients = collect($clients)->map(function($client) {
                return ClientController::nodeClientToArray($client);
            });
            $array['clients'] = $clients->values()->all();
        }
        $array['total_clients'] = $channel->total_clients == -1 ? 0 : $channel->total_clients;
        $array['max_clients'] = $channel->channel_maxclients == -1 ? 0 : $channel->channel_maxclients;
        $array['total_clients_family'] = $channel->total_clients_family == -1 ? 0 : $channel->total_clients_family;
        $array['max_clients_family'] = $channel->channel_maxfamilyclients == -1 ? 0 : $channel->channel_maxfamilyclients;
        $array['spacer'] = self::getSpacerType($channel);
        
        $array['flags'] = 0;
        $array['flags'] += $channel->channel_flag_default           ? 1   : 0;
        $array['flags'] += $channel->channel_flag_password          ? 2   : 0;
        $array['flags'] += $channel->channel_flag_permanent         ? 4   : 0;
        $array['flags'] += $channel->channel_flag_semi_permanent    ? 8   : 0;
        $array['flags'] += ($array['codec'] == 3 || $array['codec'] == 5) ? 16  : 0;
        $array['flags'] += $channel->channel_needed_talk_power != 0 ? 32  : 0;
        $array['flags'] += $channel->total_clients != -1            ? 64  : 0;
        $array['flags'] += $channel->isSpacer()                     ? 128 : 0;

        return $array;
    }

    /**
     * @param \TeamSpeak3_Node_Channel $channel
     * @return string
     */
    public static function getSpacerType(\TeamSpeak3_Node_Channel $channel)
    {
        $type = "";

        if(!$channel->isSpacer())
        {
            return "none";
        }

        switch($channel->spacerGetType())
        {
            case (string) \TeamSpeak3::SPACER_SOLIDLINE:
                $type .= "solidline";
                break;

            case (string) \TeamSpeak3::SPACER_DASHLINE:
                $type .= "dashline";
                break;

            case (string) \TeamSpeak3::SPACER_DASHDOTLINE:
                $type .= "dashdotline";
                break;

            case (string) \TeamSpeak3::SPACER_DASHDOTDOTLINE:
                $type .= "dashdotdotline";
                break;

            case (string) \TeamSpeak3::SPACER_DOTLINE:
                $type .= "dotline";
                break;

            default:
                $type .= "custom";
        }

        if($type == "custom")
        {
            switch($channel->spacerGetAlign())
            {
                case \TeamSpeak3::SPACER_ALIGN_REPEAT:
                    $type .= "repeat";
                    break;

                case \TeamSpeak3::SPACER_ALIGN_CENTER:
                    $type .= "center";
                    break;

                case \TeamSpeak3::SPACER_ALIGN_RIGHT:
                    $type .= "right";
                    break;

                default:
                    $type .= "left";
            }
        }

        return $type;
    }
}
