
Route::get('/gamesession-creator', function (Request $request) {

    // Game ID, for example: softswiss:AllLuckyClover
    if(!$request->gameID) {
        return response()->json([
            'message' => 'No game ID selected.'
        ], 400);
    }

    // Origin target where to try hijack session create URL, make sure to first import the gamelistings on other function to your local database. Bets.io is probably best for demo games while Duxcasino.com should be used for real-money session hijacking. As games already are being frauded, they are already untrackable for the game providers
    if(!$request->origin_target) {
        return response()->json(['message' => 'No origin_target specified, pick between: bets.io | bitstarz.com | duxcasino.com'], 400);
    }

    // Origin target variables to use, these should be stored locally (also for importing games) as they are unlikely to change and if they do there needs to be manual intervention to change to correct variables regardless
   if(str_contains('bets.io', $request->origin_target)) {
       $originTarget = 'bets.io';
       $gameRequest = 'https://api.bets.io';
   } elseif(str_contains('duxcasino.com', $request->origin_target)) {
       $originTarget = 'duxcasino.com';
       $gameRequest = 'https://www.duxcasino.com';
   } elseif(str_contains('bitstarz.com', $request->origin_target)) {
       $originTarget = 'bitstarz.com';
       $gameRequest = 'https://bitstarz.com/';
   } else {
       return response()->json([
           'message' => 'Incorrect origin_target specified, pick between: bets.io | bitstarz.com | duxcasino.com'
       ], 400);            
   }

   // Selecting game in local database, this can be expanded to live-check url's, also the base "DEMO LINK URL" in many cases can be stored for later re-use.

   // In particular providers that are actively joining in fraud have made it possible to easily generate sessions from a singular static request endpoint.
  $findGame = DB::table('softswiss_import')->where('gid', $request->gameID)->where('internal_origin_identifier', $originTarget)->first();

  if(!$findGame) {

    // In case game cannot be found search local database to see if other "origin_target" does support this game this should be put aside in a helper, so that in the end the system will automatically switch to next origin target
    $queryBiggerScope = DB::table('softswiss_import')->where('gid', $request->gameID)->first();
    if($queryBiggerScope) {
        $get = DB::table('softswiss_import')->where('gid', $request->gameID)->get();
    } else {
        $get = NULL;
    }
    return response()->json(['message' => 'Game not found.', 'other_origin_results' => $get, 'origin_target' => $originTarget], 400);
  }
  if($findGame->demoplay === 0) {

    //Demo Play not found, same as above, this should also get the query function above (when refactornig)
    return response()->json(['message' => 'Demo is toggled disabled status, probably a live game.', 'origin_target' => $originTarget], 400);
  }

  // Demo url not found, while "demoplay" & "findgame" checks did pass, this means something might be wrong on specific origin or something went wrong importing database
  if($findGame->internal_origin_demolink === NULL) {
    return response()->json(['message' => 'Demo request could not be completed.', 'origin_target' => $originTarget], 400);
  }

  // We fire off the softswiss wrapper request URL, inside contains the so called entry URL of the game provider
  // Please note that in the case of real-money games in many cases the URL is frauded/malformed by softswiss and unuseable directly, instead if you want the content you should follow the redirect that the 'www.a8r.cur.games/sg.js' injection results.
  $request_url = $gameRequest.$findGame->internal_origin_demolink;
  $getHttp = Http::get($request_url);

  // After we "imported" the gamesession's local source, we then try to find and select the session url noted in the pre-fire softswiss wrapper, this URL   
  $inBetweenRegex = '/{"game_url":"(.*?)","strategy":"i/s';
  preg_match($inBetweenRegex, $getHttp, $match);

  // If the pregmatch didnt find the game_url, we fire off using a proxy server, please see PROXY_SNIPPETS folder how to turn laravel with 2 functions into a proxy to use for anything (also is handy if you are building your own man in middle game-server)
  if(!isset($match[1])) {
    $demoPrefix = $findGame->internal_origin_demolink;
    $proxy_url = 'http://vps-70325c4a.vps.ovh.net/api/'.$originTarget.$demoPrefix;
    $getHttpProxied = Http::get($proxy_url);

    preg_match('/{"game_url":"(.*?)","strategy":"/s', $getHttpProxied, $match_proxy);

    if(!isset($match_proxy[1])) {
        return response()->json(['message' => 'API unable to retrieve URL regularly and through proxy', 'regex' => $match_proxy, 'proxy_url' => $proxy_url], 400);        
    } else {
        $loadedThrough = 'proxy';
        $matchURL = $match_proxy[1];
    }
  } else {
    $loadedThrough = 'local';
    $matchURL = $match[1];
    $proxy_url = false;
  }

  // Remove any unicoded '&' functions (softswiss needs to use these because of their pre-fire script as they select session/mgckey's in retarded way), can add in sanitizer function here for example for the cashier_url's etc. but this whole function obviously need refactored into seperated functions later on package
  $final_game_url = urldecode($matchURL);
  $final_game_url = str_replace('\u0026', '&', $final_game_url);

  if($request->load_content) {
        if($request->load_content !== 0) {
            if($proxy_url === false) {
                return $getHttp;
            } else {
                return $getHttpProxied;
            }
        } 
  }
  if($request->redirect) {
    if($request->redirect === 'to_game_url') {
        return redirect($final_game_url);
    }
    if($request->redirect === 'to_origin_wrapper') {
        return redirect($request_url);
    }
    if($request->redirect === 'to_proxied_wrapper') {
        if($proxy_url === false) {
            $message = 'You set to redirect to proxied wrapper, however it was not used in the request so please manually proxy or set redirect mode to \'to_origin_wrapper\' or \'to_game_url\'';
                return response()->json(['message' => $message, 'game_url' => $final_game_url, 'request_url' => $request_url, 'internal_technique' => $loadedThrough, 'proxy_url' => $proxy_url], 400);
        } else {
            return redirect($proxy_url);
        }
    }
  }


  return response()->json(['message' => 'success', 'game_url' => $final_game_url, 'request_url' => $request_url, 'internal_technique' => $loadedThrough, 'proxy_url' => $proxy_url], 200);
});
