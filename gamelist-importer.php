
Route::get('/gamelist-importer', function (Request $request) {
    // Origin target right now 1 of 3, for demo games best is bets.io as being fastest & probably most accurate
    // Use duxcasino.com or arlekincasino.com (or search dama N.V. casino on google to find centrifuge casino's)
    if(!$request->origin_target) {
        return response()->json([
            'message' => 'Origin target not specified'
        ], 400);        
    }

    // Variables for origin target, these should be stored locally instead as they won't change and regardless if they do it would need manual intervention anyway, the goal anyways is that you should build maybe 10 centrifuge/session sources so that way you are safe and system can automatically select games/source that is working
    if(str_contains('bets.io', $request->origin_target)) {
        $originTarget = 'bets.io';
        $apiLocationGamelist = 'https://bets.io/api/games/allowed_desktop';
        $storageGamelist = 'https://gitlab.freedesktop.org/ryan-gate-2/casino-montik/-/raw/main/games255__2_.json';
    } elseif(str_contains('duxcasino.com', $request->origin_target)) {
        $originTarget = 'duxcasino.com';
        $apiLocationGamelist = 'https://www.duxcasino.com/api/games/allowed_desktop';
        $storageGamelist = 'https://gitlab.freedesktop.org/ryan-gate-2/casino-montik/-/raw/main/gamesdux.json'; 
    } elseif(str_contains('bitstarz.com', $request->origin_target)) {
        $originTarget = 'bitstarz.com';
        $apiLocationGamelist = 'https://www.bitstarz.com/api/games/allowed_desktop';
        $storageGamelist = 'https://pix-api.pointdns.rest/games-bitstarz.json';
        //$storageGamelist = 'https://gitlab.freedesktop.org/ryan-gate-2/casino-montik/-/raw/main/games_bitstarz.json'; 
    } else {
        return response()->json([
            'message' => 'Incorrect origin_target specified, pick between: bets.io | bitstarz.com | duxcasino.com'
        ], 400);            
    }

    // Check if request wants us to proxy the gamelist from proxy server (Germany specified because most likely German geo ip's have more access on Softswiss products due 90% being hosted within hetzner.de cheap boxes @ softswiss)
    if($request->origin_proxied === 1) {
    $getGames = Http::withHeaders([
        'check-url' => $apiLocationGamelist
    ])->get('http://vps-70325c4a.vps.ovh.net/api/gamelist');
    } else {
        $getGames = Http::get($storageGamelist);
    }

    $getGamesDecode = json_decode($getGames, true);

    if($getGamesDecode === NULL) {
        return response()->json([
            'message' => 'Error retrieving games it seems, please check the source - response has been tagged to this error.',
            'error' => $getGames
        ], 401);        
    }

    // Check if request wants to truncate previous mySQL list entries
    if(isset($request->clean)) {
        DB::table('softswiss_import')->where('internal_origin_identifier', $originTarget)->delete();
    }

    foreach ($getGamesDecode as $gameID => $data) {
        $explodeSSid = explode('/', $gameID);
        $bindTogether = $explodeSSid[0].':'.$explodeSSid[1];
        $typeGame = 'generic';
        $hasBonusBuy = 0;
        $hasJackpot = 0;
        $demoMode = 0;
        $demoPrefix = 0;
        $typeRatingGame = 0;
        $internal_origin_realmoneylink = [];

        if(isset($data['demo'])) {
            $demoMode = true;
            $demoPrefix = urldecode($data['demo']);
            if($originTarget === 'bitstarz.com') {
                $demoPrefix = str_replace('http://bitstarz.com', '', $demoPrefix);
            }
        }

        if(isset($data['real'])) {
            $internal_origin_realmoneylink = $data['real'];        
        }

        $stringifyDetails = json_encode($data['collections']);        
        if(str_contains($stringifyDetails, 'slots')) {
            $typeGame = 'slots';
            if(isset($data['collections']['slots'])) {
                $typeRatingGame = $data['collections']['slots'];
            } else {
                $typeRatingGame = 100;
            }
        }
        if(str_contains($stringifyDetails, 'live')) {
            $typeGame = 'live';
            if(isset($data['collections']['live'])) {
                $typeRatingGame = $data['collections']['live'];
            } else {
                $typeRatingGame = 100;
            }
        }
        if(str_contains($stringifyDetails, 'bonusbuy')) {
            $hasBonusBuy = 1;
        }
        if(str_contains($stringifyDetails, 'jackpot')) {
            $hasJackpot = 1;
        }


        $prepareArray = array(
            'gid' => $bindTogether,
            'name' => $data['title'],
            'provider' => $data['provider'],
            'type' => $typeGame,
            'typeRating' => $typeRatingGame,
            'popularity' => $data['collections']['popularity'],
            'bonusbuy' => $hasBonusBuy,
            'jackpot' => $hasJackpot,
            'demoplay' => $demoMode,
            'internal_softswiss_prefix' => $gameID,
            'internal_origin_demolink' => $demoPrefix,
            'internal_origin_identifier' => $originTarget,
            'internal_origin_realmoneylink' => json_encode($internal_origin_realmoneylink),
            'internal_enabled' => 0,
        );

        $gameArray[] = $prepareArray;

    if(isset($request->import)) {
        DB::table('softswiss_import')->insert($prepareArray);
    }

    }
    if($request->raw_list_output) {
            return response()->json($getGamesDecode, 200);
    }
        return response()->json($gameArray, 200);      

});


