<?php

namespace App\Http\Controllers;

use Teamspeak;
use Illuminate\Http\Request;

class ServergroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $withClients = $request->input('show_clients', false);
        $withPermissions = $request->input('show_permissions', false);

        $groups = Teamspeak::serverGroupList();
        $groups = collect($groups)->map(function($group) use($withClients, $withPermissions) {
            return self::nodeServergroupToArray($group, $withClients, $withPermissions);
        });

        return response()->json($groups->values()->all());
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
        $withPermissions = $request->input('show_permissions', false);

        try {
            $group = Teamspeak::serverGroupGetById($id);
        }
        catch(\TeamSpeak3_Adapter_ServerQuery_Exception $e) {
            return response()->json([
                'message' => 'Servergroup not found'
            ], 404);
        }

        return response()->json(self::nodeServergroupToArray($group, $withClients, $withPermissions));
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
    
    
    public static function nodeServergroupToArray(\TeamSpeak3_Node_Servergroup $group, bool $withClients = false, bool $withPermissions = false)
    {
        $array = [];

        $array['id'] = $group->getId();
        $array['name'] = $group->name->toString();
        $array['url'] = route('servergroups.show', $array['id']);
        $array['icon'] = $group->iconid < 0 ? pow(2, 32)-($group->iconid*-1) : $group->iconid;
        $array['order'] = $group->sortid;
        $array['n_map'] = $group->n_member_addp;
        $array['n_mrp'] = $group->n_member_removep;

        $array['flags'] = 0;
        $array['flags'] += $group->namemode;
        $array['flags'] += $group->type == 2                             ? 4  : 0;
        $array['flags'] += $group->type == 0                             ? 8  : 0;
        $array['flags'] += $group->savedb                                ? 16 : 0;
        $array['flags'] += $group instanceof TeamSpeak3_Node_Servergroup ? 32 : 0;

        if($withClients) {
            $clients = $group->clientList();
            $clients = collect($clients)->map(function($client) {
                return [
                    'id' => $client['cldbid'],
                    'uniqueid' => $client['client_unique_identifier']->toString(),
                    'client_nickname' => $client['client_nickname']->toString(),
                    'client_url' => route('clients.show',  $client['cldbid'])
                ];
            });
            $array['clients'] = $clients->values()->all();
        }

        if($withPermissions) {
            $perms = $group->permList();
            $perms = collect($perms)->map(function($perm) {
                return [
                    'id' => $perm['permid'],
                    'value' => $perm['permvalue'],
                    'negated' => $perm['permnegated'],
                    'skip' => $perm['permskip']
                ];
            });
            $array['permissions'] = $perms->values()->all();
        }

        return $array;
    }
}
