<?php

function ReqValid($response, $jsonDecode = null, $responseCode = "any") ////change die to send at end
{
    if ($response['http'] == $responseCode or $responseCode == "any" or $responseCode == "all") {
        if ($jsonDecode != null) {
            return json_decode($response[0], 1);
        } else {
            return $response[0];
        }
    } else {
        $identifier = debug_backtrace();
        $identifier = array_shift($identifier);
        $identifier = 'FILE: '.strrev(explode('/', strrev($identifier['file']))[0]).' | LINE: '.$identifier['line'];
        if (@$response['error'] != null) {
            $error = $response['error']."\n";
        }
        $resText = HTTPResponseText($response['http']);
        $msg = $identifier."\n".'HTTP Code :- '.$response['http']." $resText\n".@$error;
        echo $msg;
    }
}

function call($url, $data = 0, $headers = 0, $method='GET', $version='1_1', $followlocation=0, $customopt = ''/*array of custom cURL options*/)
{
    if ($version == null) {
        $version = "1_1";
    }
    $version = 'CURL_HTTP_VERSION_'.$version;
    if ($method == null) {
        $method = 'GET';
    }
    if ($followlocation == null) {
        $followlocation = 0;
    }
    if (is_array($headers) and !empty($headers)) {
        foreach ($headers as $reb) {
            $rebe = preg_replace("/:/", ": ", (string) $reb, 1);
            $regh[] = $rebe;
        }
        $headers = @$regh;
    } else {
        $host = parse_url($url, PHP_URL_HOST);
        $headers = ["Host: $host"];
    }

    $ch=curl_init($url);
    if ($data != null) {
        if (is_array($headers)) {
            foreach ($headers as $k=>$as) {
                if (preg_match('/content-length/i', $as)) {
                    $as=preg_replace('/^(.*?):\s(.*?)$/', "$1: ".strlen($data), $as);
                    $headers[$k] = $as;
                }
            }
        }
        $curl_opt_array[CURLOPT_POSTFIELDS] = $data;
    }
    $encoding = null;
    if (is_array($headers)) {
        foreach ($headers as $jl=>$as) {
            if (preg_match('/accept-encoding/i', $as, $aen)) {
                preg_match('/accept-encoding:\s(.*?)$/i', $as, $eno);
                preg_match_all('/(gzip|deflate)/i', $eno[1], $enct);
                if (count($enct[0]) > 1) {
                    $encoding = join(', ', $enct[0]);
                } else {
                    $encoding = $enct[0][0];
                }
                $headers[$jl] = $aen[0].": ".$encoding;
            }
        }
	$ip =long2ip(mt_rand());
	$headers[] = "X-Forwarded-For: $ip";
        $curl_opt_array[CURLOPT_HTTPHEADER] = $headers;
    }
    $curl_opt_array[CURLOPT_CUSTOMREQUEST] = $method;
    $curl_opt_array[CURLOPT_HTTP_VERSION] = $version;
    $curl_opt_array[CURLOPT_TIMEOUT] = 45;
    $curl_opt_array[CURLOPT_SSL_VERIFYPEER] = 0;
    #$curl_opt_array[CURLOPT_SSLVERSION] = 3;
    $curl_opt_array[CURLOPT_SSL_VERIFYHOST] = 0;
    $curl_opt_array[CURLOPT_RETURNTRANSFER] = 1;

    $curl_opt_array[CURLOPT_FOLLOWLOCATION] = $followlocation;

    @$encoding = preg_replace('/br/', '', $encoding);
    $curl_opt_array[CURLOPT_ENCODING] = $encoding;
    if ($customopt != null && is_array($customopt)) {
        foreach ($customopt as $optk=>$opt) {
            $curl_opt_array[$optk] = $opt;
        }
    }
    #print_r($curl_opt_array);die();
    curl_setopt_array($ch, $curl_opt_array);
    $output = curl_exec($ch);
    $http = curl_getinfo($ch)["http_code"];
    $return = [$output, 'http'=>$http];
    if (curl_errno($ch)) {
        $error = "Error: ".curl_error($ch);
        $return['error'] = $error;
    }
    curl_close($ch);
    return $return;
}

function HTTPResponseText($code)
{
    $s = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',            // RFC2518
            103 => 'Early Hints',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',          // RFC4918
            208 => 'Already Reported',      // RFC5842
            226 => 'IM Used',               // RFC3229
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',    // RFC7238
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Content Too Large',                                           // RFC-ietf-httpbis-semantics
            414 => 'URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot',                                               // RFC2324
            421 => 'Misdirected Request',                                         // RFC7540
            422 => 'Unprocessable Content',                                       // RFC-ietf-httpbis-semantics
            423 => 'Locked',                                                      // RFC4918
            424 => 'Failed Dependency',                                           // RFC4918
            425 => 'Too Early',                                                   // RFC-ietf-httpbis-replay-04
            426 => 'Upgrade Required',                                            // RFC2817
            428 => 'Precondition Required',                                       // RFC6585
            429 => 'Too Many Requests',                                           // RFC6585
            431 => 'Request Header Fields Too Large',                             // RFC6585
        444 => 'Connection Closed Without Response',
            451 => 'Unavailable For Legal Reasons',                               // RFC7725
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',                                     // RFC2295
            507 => 'Insufficient Storage',                                        // RFC4918
            508 => 'Loop Detected',                                               // RFC5842
            510 => 'Not Extended',                                                // RFC2774
            511 => 'Network Authentication Required',                             // RFC6585
        ];
    @$error = $s[$code];
    if ($error == null) {
        return "Not Recognised";
    } else {
        return $error;
    }
}

$dataFile = ".smilesdata";
date_default_timezone_set("Asia/Kolkata");
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, '[]');
}
if (!file_exists(".pending")) {
    file_put_contents(".pending", '[]');
}

$file = json_decode(file_get_contents($dataFile), 1);
$cou = count($file);
if ($cou == 0) {
    die("Add Account to Proceed!!\n");
}

if (in_array('-pd', $_SERVER['argv'])) {
$pending = json_decode(file_get_contents(".pending"), 1);
if (count($pending) < 1) {
die("No Pending Claims!\n");
}
$j = $pending[0];
} else {
$j = 1;
}

$startTime = new DateTime('now');

$ban = [];
$notClaim = [];
for($j; $j <= count($file); $j++) {
if (in_array('-pd', $_SERVER['argv'])) {
if (!in_array(($j-1), $pending)) {
goto fskp;
}
}
echo "(".($j).") ";
$token = $file[($j-1)]['token'];
if ($token != null) {
$var = ReqValid(call("https://be.smilesbitcoin.com/user", "", ["Cookie:token=$token","Host:be.smilesbitcoin.com","Connection:Keep-Alive","Accept-Encoding:gzip","User-Agent:okhttp/4.3.1"], "GET", "", 0), 1);
if ($var['status']['code'] != 200) {
echo "Error: ".$var['status']['message']."\n$token\n";
if ($var['status']['message'] == "Not authorized") {
$ban[] = ($j-1);
}
goto skp;
}

$chk1 = $var["data"]["captcha_activated"];
$chk2 = $var["data"]["email_activated"];
if ($chk1 != 1) {
echo "Captcha Not Activated!\n$token\n";
goto skp;
}
if ($chk2 != 1) {
echo "Email Not Added!\n$token\n";
goto skp;
}

$var = ReqValid(call("https://be.smilesbitcoin.com/games/next_fortune_wheel", "", ["Cookie:token=$token","Host:be.smilesbitcoin.com","Connection:Keep-Alive","Accept-Encoding:gzip","User-Agent:okhttp/4.3.1"], "GET", "", 0), 1)['data']['remaining_time'];
if ($var == 0) {
$var = ReqValid(call("https://be.smilesbitcoin.com/games/sponsored_fortune_wheel", "{\"sponsored_game\":true}", ["Cookie:token=$token","Content-Type:application/json; charset=UTF-8","Content-Length:23","Host:be.smilesbitcoin.com","Connection:Keep-Alive","Accept-Encoding:gzip","User-Agent:okhttp/4.3.1"], "POST", "", 0), 1);
if (@$var['status']['code'] == 200) {
echo "Fortune Wheel: ".$var['data'] ." ".$var['status']['message']."\n";
} else {
echo "Fortune Wheel Error: ".@$var['status']['message']."\n";
}
} else {
echo "Fortune Wheel Claim After ".gmdate('H:i:s',$var)."\n";
}

$var = ReqValid(call("https://be.smilesbitcoin.com/games/next_fort_nakamoto", "", ["Cookie:token=$token","Host:be.smilesbitcoin.com","Connection:Keep-Alive","Accept-Encoding:gzip","User-Agent:okhttp/4.3.1"], "GET", "", 0), 1)['data']['remaining_time'];
if ($var == 0) {
$var = ReqValid(call("https://be.smilesbitcoin.com/games/fort_nakamoto", "{\"sponsored_game\":true}", ["Cookie:token=$token","Content-Type:application/json; charset=UTF-8","Content-Length:23","Host:be.smilesbitcoin.com","Connection:Keep-Alive","Accept-Encoding:gzip","User-Agent:okhttp/4.3.1"], "POST", "", 0), 1);
if (@$var['status']['code'] == 200) {
echo "Fortune Nakamoto: ".$var['data'] ." ".$var['status']['message']."\n";
} else {
echo "Fortune Nakamoto Error: ".@$var['status']['message']."\n";
}
} else {
echo "Fortune Nakamoto Claim After ".gmdate('H:i:s', $var)."\n";
}

if (in_array('-sp', $_SERVER['argv'])) {
goto skp;
}
$okWalk = 0;
while (true) {
$step = rand(10002,13000);
$dis = rand(10002,13000);
$var = ReqValid(call("https://be.smilesbitcoin.com/activity/claim/reward", "{\"activity_type\":\"walking\",\"distance\":".$dis.",\"steps\":".$step."}", ["Cookie:token=$token","Content-Type:application/json; charset=UTF-8","Content-Length:57","Host:be.smilesbitcoin.com","Connection:Keep-Alive","Accept-Encoding:gzip","User-Agent:okhttp/4.3.1"], "POST", "", 0), 1, "any");
if ($var['status']['code'] == 200) {
echo "Earned ".$var['data']['num_satoshis']." Satoshis From Walking.\n";
} else {
if ($var['status']['message'] == "You reached the 24h reward limit for this activity type. Please come back later.") {
$okWalk = 1;
echo "Already Claimed!\n";
} else {
echo $var['status']['message']."\n";
}
break;
}
}

if ($okWalk != 1) {
$notClaim[] = ($j-1);
}
}

skp:
echo "==================\n";
fskp:
}

if (!empty($ban)) {
@system("cp $dataFile .bk");
echo count($ban)." Junk Accounts Removed!\n";
foreach ($ban as $rm) {
unset($file[$rm]);
}
$newFile = array_values($file);
$file = json_encode($newFile, 1);
file_put_contents($dataFile, $file);
}

if (!empty($notClaim)) {
$pending = [];
if (!isset($newFile)) {
$boy = array_keys($file);
foreach ($notClaim as $nC) {
$enter = array_search($nC, $boy);
$pending[] = $enter;
}
} else {
$pending = $notClaim;
}
echo "Run with -pd argument!\n";
$pending = json_encode($pending, 1);
file_put_contents(".pending", $pending);
}

echo "Done\n";
$endTime = new DateTime('now');
$diff = $endTime->diff($startTime);
echo "Execution Time: ".$diff->format('%H:%I:%S')."s\n";

?>
