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

//composer components
//twitter feed
require 'vendor/autoload.php';

$loglevel=0;
$loglevelfile=0;
$log_path = '.';

//$wikiApi  = "https://test.wikipedia.org/w/api.php";
$wikiApi  = "https://wiki.hackerspaces.org/w/api.php";


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
	$wikitext = str_replace('|status=active','|status=closed',$wikitext);

	//echo 'Edit with csrkToken'.$csrf_Token.PHP_EOL;
	closeHackerspaceRequest($hackerspace,$wikitext,$csrf_Token);//Step5

	//works,check return value and test with time elapsed.
	// $unixtime = getDateLastTweet('TkkrLab');
	// $unixtime = getDateNewsFeed('https://tkkrlab.nl/nieuws/index.xml');

	//todo
	//$test = getDateLastWikiEdit('https://tkkrlab.nl/wiki/');
	//getCalenderFeed('http://tinyurl.com/6bbzrpt');


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

	echo 'Logintoken is '.$logintoken.PHP_EOL;

	$params = [
		"action" => "login",
		"lgname" => $botUser,
		"lgpassword" => $botPasswd,
		"lgtoken" => $logintoken,
		"format" => "json"
	];

	$url = $wikiApi;//  . "?" . http_build_query( $params );

	$result = getCurl($url,$params);
	var_dump($result);
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
}


// Step 4: POST request to edit a page
function closeHackerspaceRequest( $spaceURLname , $newpage,$csrftoken ) {
	global $wikiApi ;

	//https://wiki.hackerspaces.org/Special:ApiSandbox#action=edit&title=TkkrLab&appendtext=%22Hello%20World%22&format=json

 	$result = getCurl($url);

	$params = [
		"action" => "edit",
		"title" => $spaceURLname,
		//"section" => "new",
		//"nocreate" => false,
		//"pageid" => $spaceURLname,
		"appendtext" => "Hello",
		//"text" => $newpage,
		"token" => $csrftoken,
		"summary" => "Space closed by bot",
		//"bot" => true,
		"format" => "json"
	];

	$url = $wikiApi ;// . "?" . http_build_query( $params );
	$result = getCurl($url,$params);

	echo 'Close by bot : '.PHP_EOL;

	if (isset($result['json']['error'])) {
		//var_dump( $result['json']['error']);
	}

	//solve captcha 
	if ($result['json']['edit']['result']=='Failure' and isset(['json']['edit']['captcha']) ) {

		echo 'Solve Chapta';

		$captchparams = [
			"captchaid" => $result["json"]['edit']['captcha']['id'],
			"captchaword" => getCaptchaAnswer($result["json"]['edit']['captcha']['id']),
		];
		$params = array_merge($params,$captchparams);

		$url = $wikiApi ;// . "?" . http_build_query( $params );

		$result = getCurl($url,$params);
		if (isset($result['json']['error'])) {
			var_dump( $result['json']['error']);
		};
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
    //global $messages;

    $curlSession = curl_init();
    curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curlSession, CURLOPT_USERAGENT, "http://mapall.space");

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

    //curl_setopt( $curlSession, CURLOPT_HEADERFUNCTION, "CurlHeader");

    $result = curl_exec($curlSession);
    $curl_error = curl_errno($curlSession);
    $curl_info = curl_getinfo($curlSession,CURLINFO_HTTP_CODE);
    $curl_ssl  = curl_getinfo($curlSession, CURLINFO_SSL_VERIFYRESULT);

    if ($curl_ssl!=0) {
		echo 'SSL verify error '.$curl_ssl.PHP_EOL;
    }

    curl_close($curlSession);

    if ( $curl_error == 0 && $curl_info == 200 ) {
        $json = json_decode($result, true);
        if ($json != null ){
            return array('json'=>$json,'error'=>0 );
        } else {
    		//try to convert xml to json
    		//$xml = simplexml_load_string($result);
			//$json = json_decode(json_encode($xml),true);
			if ($json!=null) {
				return array('json'=>$json,'error'=>0 );
			} else {
	            return array('json'=>null,'error'=>1000 );
			};
        };
    } else {
        $error = ($curl_error!=0) ? $curl_error : $curl_info;  
        return array('json'=>null,'error'=>$error);
    };
};

function CurlHeader( $curl, $header_line ) {
    //echo "<br>YEAH: ".$header_line; // or do whatever
    //return strlen($header_line);
}

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

function getDateLastTweet($user) {
	global $twitter;
	//check https://developer.twitter.com/en/docs/tweets/timelines/api-reference/get-statuses-user_timeline
	//GET https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=twitterapi&count=2 
	$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
	$getfield = "?screen_name=$user&count=1";
	$requestMethod = 'GET';

	$result = json_decode($twitter->setGetfield($getfield)
	             ->buildOauth($url, $requestMethod)
	             ->performRequest(),JSON_OBJECT_AS_ARRAY);
	$datetime =  strtotime($result[0]['created_at']);

	return date("Y-d-m H:i",strtotime($result[0]['created_at']));
};

function getDateLastWikiEdit($wiki) {
	//https://tkkrlab.nl/w/api.php?action=query&format=json&list=recentchanges
	$result = getCurl($wiki.'/w/api.php?action=query&format=json&list=recentchanges');
	var_dump($result);
}

function getDateNewsFeed($feed) {
	$result = getCurl($feed);
	return 	strtotime($result['json']['channel']['lastBuildDate']);
}

function getCalenderFeed($ical) {
	$result = getCurl($ical);
	var_dump($result);
}

//HTTP Header -> Last Modified : 
//https://stackoverflow.com/questions/9183178/can-php-curl-retrieve-response-headers-and-body-in-a-single-request

function getFacebook($user) {
	global $fb;
	$fbToken = 'EAAF5VbXNUPsBAEMEjFlIIOhStvkHTi3VnrtodGMj9SloErWQLvr9OYTJwc2ZCYZCZASeuRq28QoWnU5kRIEiod6DiZCwkuLZAZAS3ZCqk7pZCU3JxJYukJFY2pPthMX7cORAqIZCCkU4ocuqBlZBSZB5YuJRmF7tqOf3IUSsX6iw0Nj82T2cM6tOpjCH8smcsJXam9XEQUBiONJyz5sdwvCJvAwa6HgBPQczQWxbUEahtJgzdoQNeZAS1DVX3Hv5QkiS658ZD';

	$helper = $fb->getPageTabHelper();

try {
  $accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

if (! isset($accessToken)) {
  echo 'No OAuth data could be obtained from the signed request. User has not authorized your app yet.';
  exit;
}

// Logged in
echo '<h3>Page ID</h3>';
var_dump($helper->getPageId());

echo '<h3>User is admin of page</h3>';
var_dump($helper->isAdmin());

echo '<h3>Signed Request</h3>';
var_dump($helper->getSignedRequest());

echo '<h3>Access Token</h3>';
var_dump($accessToken->getValue());
// OR





// 	$helper = $fb->getRedirectLoginHelper();

// 	try {
// 	  $accessToken = $helper->getAccessToken();
// 	} catch(Facebook\Exceptions\FacebookResponseException $e) {
// 	  // When Graph returns an error
// 	  echo 'Graph returned an error: ' . $e->getMessage();
// 	  exit;
// 	} catch(Facebook\Exceptions\FacebookSDKException $e) {
// 	  // When validation fails or other local issues
// 	  echo 'Facebook SDK returned an error: ' . $e->getMessage();
// 	  exit;
// 	}


// 	var_dump($accessToken);

// if (! $accessToken->isLongLived()) {
//   // Exchanges a short-lived access token for a long-lived one
//   try {
//     $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
//   } catch (Facebook\Exceptions\FacebookSDKException $e) {
//     echo "<p>Error getting long-lived access token: " . $e->getMessage() . "</p>\n\n";
//     exit;
//   }

//   echo '<h3>Long-lived</h3>';
//   var_dump($accessToken->getValue());
//}

//$_SESSION['fb_access_token'] = (string) $accessToken;




return;

	// try {
	//   // Returns a `Facebook\FacebookResponse` object
	//   $response = $fb->get('/me?fields=id,name', '{access-token}');
	// } catch(Facebook\Exceptions\FacebookResponseException $e) {
	//   echo 'Graph returned an error: ' . $e->getMessage();
	//   exit;
	// } catch(Facebook\Exceptions\FacebookSDKException $e) {
	//   echo 'Facebook SDK returned an error: ' . $e->getMessage();
	//   exit;
	// }




	// $user = $response->getGraphUser();

	// echo 'Name: ' . $user['name'];

	// return ;

	//http://johndoesdesign.com/blog/2011/php/adding-a-facebook-news-status-feed-to-a-website/
	//Get the contents of the Facebook page
	$FBpage = file_get_contents('https://graph.facebook.com/PAGE_ID/feed?access_token=ACCESS_TOKEN');
	//Interpret data with JSON
	$FBdata = json_decode($FBpage);
	//Loop through data for each news item
	foreach ($FBdata->data as $news )
	{
		//Explode News and Page ID's into 2 values
		$StatusID = explode("_", $news->id);
		echo '<li>';
		//Check for empty status (for example on shared link only)
		if (!empty($news->message)) { echo $news->message;}
		echo '</li>';
	};
	

};

?>