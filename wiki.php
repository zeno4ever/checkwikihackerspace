<?php

/*
    edit.php

    MediaWiki API Demos
    Demo of `Edit` module: POST request to edit a page
    MIT license

	from https://www.mediawiki.org/wiki/API:Edit#POST_request
	https://www.mediawiki.org/wiki/API:Logout
	//https://www.mediawiki.org/wiki/API:Edit#CAPTCHAs

*/

include "./settings.php"; //get secret settings

$loglevel=0;
$loglevelfile=0;
$log_path = '.';

//$wikiApi  = "https://test.wikipedia.org/w/api.php";
//$wikiApi  = "https://wiki.hackerspaces.org/w/api.php";
// echo getTweets('TkkrLab',4);
// exit;

echo 'Base URL '.$wikiApi.PHP_EOL;

echo 'Get Login Token '.PHP_EOL;
$login_Token = getLoginToken(); // Step 1
echo 'Login with token '.$login_Token.PHP_EOL;
loginRequest( $login_Token ); // Step 2

echo 'Get csrf Token'.PHP_EOL;
$csrf_Token = getCSRFToken(); // Step 3
echo 'csrf Token '.$csrf_Token.PHP_EOL;

//editRequest($csrf_Token); // Step 4

//For each do 
	$hackerspace = 'TkkrLab';
	echo 'Change '.$hackerspace.' to status closed'.PHP_EOL;
	$wikitext = getWikiPage($hackerspace);
	//set status to closed 
	//check wiki
	// /api.php?action=query&list=recentchanges&format=json
	//check twitter / Auth
	//https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=tkkrlab&count=2
	//check feed (atom)
	//flicker?
	//ical


	$wikitext = str_replace('|status=active','|status=closed',$wikitext);
	//echo 'Edit with csrkToken'.$csrf_Token.PHP_EOL;
	closeHackerspaceRequest($hackerspace,$wikitext,$csrf_Token);//Step5

//all done, logout
echo 'Logout'.PHP_EOL;
logoutRequest($csrf_Token);

// Step 1: GET request to fetch login token
function getLoginToken() {
	global $wikiApi ;

	$params = [
		"action" => "query",
		"meta" => "tokens",
		"type" => "login",
		"format" => "json"
	];

	$url = $wikiApi  . "?" . http_build_query( $params );

	$result = getCurl($url);

	return $result["json"]["query"]["tokens"]["logintoken"];
}

// Step 2: POST request to log in. Use of main account for login is not
// supported. Obtain credentials via Special:BotPasswords
// (https://www.mediawiki.org/wiki/Special:BotPasswords) for lgname & lgpassword
function loginRequest( $logintoken ) {
	global $wikiApi ;

	$params = [
		"action" => "login",
		"lgname" => $botUser,
		"lgpassword" => $botPasswd,
		"lgtoken" => $logintoken,
		"format" => "json"
	];

	$url = $wikiApi;//  . "?" . http_build_query( $params );

	$result = getCurl($url,$params);
}

// Step 3: GET request to fetch CSRF token
function getCSRFToken() {
	global $wikiApi ;

	$params = [
		"action" => "query",
		"meta" => "tokens",
		"format" => "json"
	];

	$url = $wikiApi  . "?" . http_build_query( $params );


	$result = getCurl($url);

	return $result["json"]["query"]["tokens"]["csrftoken"];
}

function getWikiPage($spaceURLname) {
	global $wikiApi ;

	$params = [
		"action" => "parse",
		"page" => $spaceURLname,
		"prop" => "wikitext",
	    "format" => "json"
	];

	$url = $wikiApi . "?" . http_build_query( $params );

	$result = getCurl($url);
	return($result["json"]["parse"]["wikitext"]["*"]);
	//return $result["json"]["parse"]["wikitext"]["*"]; 

}


// Step 4: POST request to edit a page
function closeHackerspaceRequest( $spaceURLname , $newpage,$csrftoken ) {
	global $wikiApi ;

	//https://wiki.hackerspaces.org/Special:ApiSandbox#action=edit&title=TkkrLab&appendtext=%22Hello%20World%22&format=json

	//get curret page
	// $params = [
	// 	"action" => "edit",
	// 	"title" => $spaceURLname,
	// 	"token" => $csrftoken,
	// 	"format" => "json"
	// ];


	// $url = $wikiApi  . "?" . http_build_query( $params );

	// $result = getCurl($url);

	$params = [
		"action" => "edit",
		"title" => $spaceURLname,
		//"section" => "new",
		//"nocreate" => false,
		//"pageid" => $spaceURLname,
		//"appendtext" => "Hello",
		"text" => $newpage,
		"token" => $csrftoken,
		"summary" => "Space closed by bot",
		"bot" => true,
		"format" => "json"
	];

	var_dump($newpage);

	$url = $wikiApi ;// . "?" . http_build_query( $params );

	$result = getCurl($url,$params);

	echo 'Close by bot : '.print_r($result).PHP_EOL;


	//solve captcha 
	if ($result["json"]['edit']['result']=='Failure' and isset(['edit']['captcha']) ) {

		echo 'Solve Chapta';

		$captchparams = [
			"captchaid" => $result["json"]['edit']['captcha']['id'],
			"captchaword" => getCaptchaAnswer($result["json"]['edit']['captcha']['id']),
		];
		$params = array_merge($params,$captchparams);

		$url = $wikiApi ;// . "?" . http_build_query( $params );

		$result = getCurl($url,$params);
	}

	//echo ( $result["json"] );
}


// Step 4: POST request to logout
function logoutRequest( $csrftoken ) {
	global $wikiApi;

	$params = [
		"action" => "logout",
		"token" => $csrftoken,
		"format" => "json"
	];


	$url = $wikiApi;//  . "?" . http_build_query( $params );

	$result = getCurl($url,$params);
}

function getCaptchaAnswer($question) {
	switch ($question) {
		case "What does the quote on the top of the List of Events page say?":
			return '"To become great, you must stand on the shoulders of giants."';
			break;
        case "Where is hackerspaces.org currently hosted at? Hint: Read the Disclaimers (bottom of page)":
            return "Nessus";
			break;
        case "What is the name of our IRC channel on freenode? Hint: Read the Communication page":
            return "#hackerspaces";
			break;
		case "This website is for whom? Hint: Read the frontpage":
			return "Anyone and Everyone";
			break;
		default:
			echo "CaptchaAnswer not found!";
	}
}


function getCurl($url,$fields=null,$timeout=240) {
    global $messages;

    $curlSession = curl_init();
    curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curlSession, CURLOPT_USERAGENT, "mapall.space");
    //for redirect
    curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION, true);
    
    //timeout in secs
    curl_setopt($curlSession, CURLOPT_TIMEOUT,$timeout); 

    //get file
    curl_setopt($curlSession, CURLOPT_URL, $url);

    //set options if needed
    if (is_array($fields)) {
    	curl_setopt( $curlSession, CURLOPT_POST, true );
    	curl_setopt( $curlSession, CURLOPT_POSTFIELDS, http_build_query( $fields ) );
    };


    $space_api_json = curl_exec($curlSession);
    $curl_error = curl_errno($curlSession);
    $curl_info = curl_getinfo($curlSession,CURLINFO_HTTP_CODE);

    curl_close($curlSession);

    if ( $curl_error == 0 && $curl_info == 200 ) {
        $json = json_decode($space_api_json, true);
        if ($json != null ){
            return array('json'=>$json,'error'=>0 );
        } else {
            return array('json'=>null,'error'=>1000 );
        };
    } else {
        $error = ($curl_error!=0) ? $curl_error : $curl_info;  
        return array('json'=>null,'error'=>$error);
    };
};

function message($message,$lineloglevel=0) {
    global $loglevel;
    global $loglevelfile;
    global $log_path;

    if ($lineloglevel >= $loglevel) {
        echo $message.PHP_EOL;
    };

    if ($lineloglevel >= $loglevelfile) {
        //
        if(!file_exists ( $log_path.'errorlog.txt' )) {
            $message = "Map all spaces error log, see also FAQ\nError 0-99 Curl\nError 100-999 http\nError 1000 no valid json\nError 1001 dupe\n\n".$message;
        }

        $fp = fopen($log_path.'errorlog.txt', 'a');
        fwrite($fp,$message.PHP_EOL);
        fclose($fp);
    };
}

function getTweets($user, $count) {
    $datas = file_get_contents('https://mobie.twitter.com/'.$user);

    preg_match_all('/data-id="[0-9]{19}/s',$datas,$matchetweets,PREG_SET_ORDER);
    //echo '['.$matchetweets[0].']';
    //1237340054885892097
    //1583874258
    //1237340054885


	var_dump($matchetweets);

    //echo(date("F d, y h:i:s A", 1237340054));

    for ($i = 1; $i <= $count; $i++) {
    	echo 's=['.$matchetweets[$i][0].']';
    	echo 'String = ['.substr($matchetweets[$i][0],9,10).']'.PHP_EOL;
    	echo 'Datum = '.date("F d, Y h:i:s A", substr($matchetweets[$i][0],9,10)).PHP_EOL;
    	//$matchetweets[$i][0]
    }

    //echo(date("F d, Y h:i:s A", $matchetweets[0]));

   

    // preg_match_all('/<div class="tweet-text" data-id="\d*">(.*?)<\/div>/s', $datas, $matchetweets, PREG_SET_ORDER);

    // for ($i = 1; $i <= $count; $i++) {
    //     $matchetweets[$i][0] = preg_replace('/<div class="dir-ltr" dir="ltr">/', '', $matchetweets[$i][0]);
    //     $matchetweets[$i][0] = preg_replace('/\s+/', ' ', $matchetweets[$i][0]);
    //     $matchetweets[$i][0] = str_replace('"> ', '">', $matchetweets[$i][0]);

    //     echo '<li>'.$matchetweets[$i][0].'</li>'."\n";
    // }

};

// $req_url = 'https://api.twitter.com/oauth/request_token';
// $authurl = 'https://api.twitter.com/oauth/authorize';
// $acc_url = 'https://api.twitter.com/oauth/access_token';
// $api_url = 'https://api.twitter.com/1.1/account';
// $conskey = '0ofOq8ENZuiUiCBNExmg';
// $conssec = '716oYAPRVfqVVCX4wwGrvtgOzMRKzt1sy8snKiW3U0';

// $httpClient = new \SocialConnect\HttpClient\Curl();


// $configureProviders = [
//     'redirectUri' => 'http://sconnect.local/auth/cb/${provider}/',
//     'provider' => [
//         'facebook' => [
//             'applicationId' => '',
//             'applicationSecret' => '',
//             'scope' => ['email'],
//             'options' => [
//                 'identity.fields' => [
//                     'email',
//                     'picture.width(99999)'
//                 ],
//             ],
//         ],
//     ],
// ];

// $service = new \SocialConnect\Auth\Service(
//     $httpStack,
//     new \SocialConnect\Provider\Session\Session(),
//     $configureProviders,
//     $collectionFactory
// );

// /**
//  * By default collection factory is null, in this case Auth\Service will create 
//  * a new instance of \SocialConnect\Auth\CollectionFactory
//  * you can use custom or register another providers by CollectionFactory instance
//  */
// $collectionFactory = null;

// $service = new \SocialConnect\Auth\Service(
//     $httpClient,
//     new \SocialConnect\Provider\Session\Session(),
//     $configureProviders,
//     $collectionFactory
// );

// //session_start();
// function twitterLogin() {

// 	// In state=1 the next request should include an oauth_token.
// 	// If it doesn't go back to 0
// 	if(!isset($_GET['oauth_token']) && $_SESSION['state']==1) $_SESSION['state'] = 0;
// 		try {
// 		  $oauth = new OAuth($conskey,$conssec,OAUTH_SIG_METHOD_HMACSHA1,OAUTH_AUTH_TYPE_URI);
// 		  $oauth->enableDebug();
// 		  if(!isset($_GET['oauth_token']) && !$_SESSION['state']) {
// 		    $request_token_info = $oauth->getRequestToken($req_url);
// 		    $_SESSION['secret'] = $request_token_info['oauth_token_secret'];
// 		    $_SESSION['state'] = 1;
// 		    header('Location: '.$authurl.'?oauth_token='.$request_token_info['oauth_token']);
// 		    exit;
// 		  } else if($_SESSION['state']==1) {
// 		    $oauth->setToken($_GET['oauth_token'],$_SESSION['secret']);
// 		    $access_token_info = $oauth->getAccessToken($acc_url);
// 		    $_SESSION['state'] = 2;
// 		    $_SESSION['token'] = $access_token_info['oauth_token'];
// 		    $_SESSION['secret'] = $access_token_info['oauth_token_secret'];
// 		  } 
// 		  $oauth->setToken($_SESSION['token'],$_SESSION['secret']);
// 		  //$oauth->fetch("$api_url/user.json");
// 		  $oauth->fetch("$api_url/verify_credentials.json");
// 		  $json = json_decode($oauth->getLastResponse());
// 		  print_r($json);
// 		} catch(OAuthException $E) {
// 		  print_r($E);
// 	}

// }

?>