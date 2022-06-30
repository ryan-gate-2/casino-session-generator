## Casino Session Generator with PROXY SUPPORT
Using Laravel (ofcourse), these snippets make it possible to easily generate demo & real-money game sessions on DAMA N.V. Casino's (centrifuge).

[Click to generate a session (soon off or might be off at time of trying)](https://pix-api.pointdns.rest/retrieveDemoURL?gameID=1x2gaming:BarbarianGold&origin_target=bets.io)

[Click to start real money session on Bets.io, proxied using this snippets](http://vps-70325c4a.vps.ovh.net/api/bets.io_cookied/games/pragmaticexternal/TheDogHouse/29876)

![Proxied Realmoney Session](https://i.imgur.com/DAtU7uh.png)

*Proxied Real-money Gamesession*


## What is this
These games can and are being edited, so you can do yourself aswell if you wish to start your own casino business.

Right now in these snippets is support for launching session & gamelist scraping:
- [Bitstarz.com](https://www.bitstarz.com/)  (best for real money game sessions)
- [Arlekincasino.com](https://www.arlekincasino.com/)
- [Duxcasino.com](https://www.duxcasino.com)
- [Bets.io](https://bets.io) (best for demo game sessions)

But, there are hundreds casino's using softswiss gamelist format.
To make it easy for you to get started with GitLab, here's a list of recommended next steps.

## Setup 
Setup laravel base and add game-importer and so on together into your routes/api.php.

Insert .sql file.

The proxy should be setup on external box. The game session creator will first try to start session locally if fails to create session (mostly due to geo-locks based on I.P. of your server), it will send a proxy support. 

Proxy files is to be installed on external box, place the included 2 proxy scripts to /App/Helpers and register this in your /App/Providers/AppServiceProvider.php (will upload this).

## Gamelist Scrape & Import
However you can get nice info's that you can use on your copied/grey games like the tournament information on pragmaticplay etc etc.

![Gamelist Import](https://i.imgur.com/0shHFTj.png)

*Scrape softswiss formatted gamelist and import to local database*

After added both the game-importer & session-creator to your routes/api.php, you use following queries:

In below example query, script will use the 'offline' stored gamelist.json (right now set to some gitlabs, you should store these yourself)

```
## yoururl.com/api/game-importer?import=1&origin_target=bets.io&clean=1&origin_proxied=1
```

**Parameters**:
    - import=1  | _Toggle to 0 or remove completely from query to just see the result (dry-run'ish)_
    - origin_target={casinoID}  | _Basically the vector where to retrieve gamelists and create sessions, you should probably setup around 10        casino's so you got access to all games and reliable._
    - clean=1  | _Clean means it will delete the previous records in your local database for the specific origin_target, it will not remove/clean games from other origin_targets, if you set this 0 you have a big chance on tons and tons of duplicates._
    - raw_list_output=0  | _This will instead return the raw gamelist as it is scraped, instead of the transformed gamelist array._
    - origin_proxied=1  | _Use proxy / external server to retrieve LIVE gamelist from specific casino, make sure this server is in a preferential geo-location (germany)_


## Casino Game Session Generator
NOTE: the real money sessions really only can be used and should be used with your own gameserver (taking their fraud games and basically do same as them) as these gamesession url's are redirected & in most cases not a real valid money session but a demo session.

**If you are going to use real-money sessions, you need to use specific game_ids at end of URL.** They are imported to the _internal-realmoneylink_ database field. 
Not all games support all currencies and not all casino's support all currencies, so they are specified when taking the gamelistings.
You simply change the digits on the end of URL to the one said for the currency you wish to generate game.

In below example the snippet will search in your local database for game ID and the origin_target. The snippet will always first try and launch local

Softswiss doing the big fraud makes it so that all games are wrapped in their fraudulant casino's & centrifuges on their external page, this page does include the session url's.

However you can get nice info's that you can use on your copied/grey games like the tournament information on pragmaticplay etc etc.

```php
## yoururl.com/api/session-generator?gameID=1x2gaming:BarbarianGold&origin_target=bets.io
```
**Parameters:**
    - gameID=1x2gaming:BarbarianGold   | _your local game id session stored in local database (done by importer)_
    - origin_target=bets.io  | The casino/vector to generate the game at, the proxy also shows example of a cookied request._
    - redirect=to_origin_wrapper | 'to_game_url', 'to_origin_wrapper', 'to_proxied_wrapper'. This will redirect immediately after succesfull session generation, however do not put this snippets to public, however it can be handy testing yourself or to run grey-games. This is unset by default. _
    - load_content=1  | _This will import the source of game to index.php_

![Session generation](https://i.imgur.com/RP4373v.png)*Session Generator*


## Proxy 
Setup laravel on external box, place ProxyHelper.php & ProxyHelperFacade.php /App/Helpers/, create this directory if you need to.

My suggestion is if you are to use this for Softswiss game generation is to pick Germany based VPS/servers as Softswiss hosts mainly on Hetzner.de which is german based and thus seems more lenient.

After go to the file: `App\Providers\AppServiceProvider.php` and in the method `register()` adds the facade:
```php
$this->app->bind('ProxyHelper', function($app) {
    return new ProxyHelper();
});
```
Remember that you need to import the class (not the one called facade) in `App\Providers\AppServiceProvider.php`

```php
use App\Helpers\ProxyHelper; //or your path
```

Proxy routes:

```php
    Route::match(['get', 'post', 'head', 'patch', 'put', 'delete'] , 'proxy/{slug}', function(Request $request){

        // To redirect the request to a different host, the first parameter will be the host.
        // the second, will be the current path that we want to ignore, it must be the url of the controller (api/proxy)
        //so we're telling you that the new url will be:
        // (host) http://my.server2.test + (deleted)[api/proxy] + ({slug}) /api/avatar/color
        return ProxyHelperFacade::CreateProxy($request)->toHost('http://my.server2.test','api/proxy');
        
        //other way is to tell him the url directly.
        return ProxyHelperFacade::CreateProxy($request)->toUrl('http://my.server2.test/api/avatar/color');
        
        // this second way will no longer be dynamic.
        

    })->where('slug', '([A-Za-z0-9\-\/]+)');
```



Example of a cookie route on bets.io, you should make a helper to keep the cookie 'alive':


```php
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
```



