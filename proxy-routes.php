Route::match(['get', 'post', 'head', 'patch', 'put', 'delete'] , 'bets.io_cookied/{slug}', function(Request $request){
    $cookie = 'referral_params=eJwrSk0szs+zTU/MTY0vSi0uKcpMLklNic/Mi0/OL80rKaoEAOQvDaE=; dateamlutsk-_zldp=M6KbIcofZ5OdbzCklHE/wT4m8vct0Wfje3KHtA0uoRoY8NE801Jy2Psphbw8i4k+WGzG+PDOVsw=; dateamlutsk-_zldt=7698a211-3f3c-4241-b261-240e437d0678-0; locale=ImVuIg$
    return ProxyHelperFacade::CreateProxy($request)
            // add a header before sending the request
            ->withHeaders(['cookie' => $cookie])
            // add a Bearer token (this is useful for the client not to have the token, and from the intermediary proxy we add it.
            //Maintain the query of the url.
            ->preserveQuery(true)
            ->toHost('https://api.bets.io','api/bets.io_cookied');

})->where('slug', '([A-Za-z0-9\-\/]+)');

Route::match(['get', 'post', 'head', 'patch', 'put', 'delete'] , 'bets.io/{slug}', function(Request $request){
    return ProxyHelperFacade::CreateProxy($request)->toHost('https://api.bets.io/', 'api/bets.io');
})->where('slug', '([A-Za-z0-9\-\/]+)');

Route::match(['get', 'post', 'head', 'patch', 'put', 'delete'] , 'arlekincasino.com/{slug}', function(Request $request){
    return ProxyHelperFacade::CreateProxy($request)->toHost('https://arlekincasino.com/', 'api/arlekincasino.com');
})->where('slug', '([A-Za-z0-9\-\/]+)');

Route::match(['get', 'post', 'head', 'patch', 'put', 'delete'] , 'bitstarz.com/{slug}', function(Request $request){
    return ProxyHelperFacade::CreateProxy($request)->toHost('https://bitstarz.com/', 'api/bitstarz.com');
})->where('slug', '([A-Za-z0-9\-\/]+)');

Route::match(['get', 'post', 'head', 'patch', 'put', 'delete'] , 'duxcasino.com/{slug}', function(Request $request){
    return ProxyHelperFacade::CreateProxy($request)->toHost('https://duxcasino.com/', 'api/duxcasino.com');
})->where('slug', '([A-Za-z0-9\-\/]+)');



