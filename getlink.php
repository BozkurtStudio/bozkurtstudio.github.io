<?php
// Getting url from query ?url=...
$url = $_GET['url'];
// check if it matches regex:
preg_match('/video.sibnet.ru\/(?:(?:shell\.php\?videoid=)|(?:video))(\d+)/', $url, $iurl);
$id = "";
$sib = 1;
function dw($msg) {
	$f = fopen("log.log", "a");
	fwrite($f, $msg."\n");
	fclose($f);
}
function init_ch($u, $o = null) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $u);
  curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; Android 4.4.2; Nexus 4 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.114 Mobile Safari/537.3");
  curl_setopt($ch, CURLOPT_REFERER, $u);
  curl_setopt($ch, CURLOPT_ACCEPT_ENCODING, "utf-8,cp1251");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  if ($o != null) {
    curl_setopt_array($ch, $o);
  }
  return $ch;
}
function get_data($u, $o = null) {
  $ch = init_ch($u, $o);
  $data = curl_exec($ch);
  $ret = $data;
  if(curl_errno($ch)){
    throw new Exception(curl_error($ch));
  }
  curl_close($ch);
  return $ret;
}
function headers($raw) {
  $headers = array();
  $lines = explode("\n", $raw);
  $f = 0;
  foreach($lines as $line) {
    if ($f == 0) {
      dw("STATUS LINE: $line");
      $f = 1;
      $headers["STATUS"] = explode(" ", $line, 3);
      continue;
    }
    if ($line == null || trim($line[0]) == "") {
      continue;
    }
    if(substr($line, 0, 1) == "<") { break; }
    dw("Header-Line: $line");
    list($k, $v) = explode(":", $line, 2);
    $xk = ucfirst(strtolower($k));
    if (!array_key_exists($xk, $headers)) {
      $headers[$xk] = trim($v);
    } else {
      dw("Add value: $xk: $v");
      $headers[$xk] = $headers[$xk] . " " . trim($v);
    }
  }
  return $headers;
}
function tocookies($setcookies) {
  $m = preg_replace('/(expires|path|domain|samesite)=.*?;|\s(httponly|secure|samesite=none)(;|\s|$)|\spath=[^;]*;?/i', '', $setcookies);
  $n = preg_replace('/;\s+/', '; ', $m);
  return $n;
}
$cookies = ""; $e_url = ""; $title = ""; $durl = ""; $aurl = ""; 
if (sizeof($iurl) >= 2) {
  $id = $iurl[1];
  $e_url = "https://video.sibnet.ru/shell.php?videoid=$id";
  $mydata = get_data($e_url);
  // getting url and title from response:
  preg_match('/.*(\/v\/.*?mp4)", type.*/m', $mydata, $ddg);
  preg_match('/og:title" content="(.+?)"/m', $mydata, $ttg);
  $title = $ttg[1];
  $aurl = "https://video.sibnet.ru".$ddg[1];
  $myinfo = get_data($aurl, Array(CURLOPT_REFERER => $e_url, CURLOPT_HEADER => TRUE, CURLOPT_NOBODY => TRUE));
  $hed = headers($myinfo);
  $durl = "https:".$hed["Location"];
} else { // Not sibnet 
  $sib = 0;
  dw("Try with myvi/ourvideo.ru...");
  preg_match('/(myvi\.[^\/]+).*?v=(.{26})/', $url, $mid); // check if not myvy.ru
  if (sizeof($mid) >= 3) {
    $id = $mid[2];
    dw("Get id from url, make embed: $id");
    $e_url = "https://" . $mid[1] . "/embed/" . $id;
  } else {  
    preg_match('/(ourvideo.ru|myvi).*embed/', $url, $myv);
    // print_r($myv);
    dw("Not embed-url, try to get...");
    $myvh = get_data($url, Array(CURLOPT_HEADER => TRUE, CURLOPT_NOBODY => TRUE));
    $hed = headers($myvh);
    if (array_key_exists("Set-cookie", $hed)) {
      $cookies = tocookies($hed["Set-cookie"]);
      dw("cookie1: $cookies");
    }
    if (sizeof($myv) < 2) {
      $myvdata = get_data($url, Array(CURLOPT_HTTPHEADER => array("Cookie:".$cookies)));
      #$cookies = "Cookie: ".str_replace("Set-Cookie: ", "", $myvhed[4]. "; ".$myvhed[7]);
      // print($myvdata);
      preg_match('/content="(\/\/myvi.ru\/player\/embed.*?)"/m', $myvdata, $myve);
      $e_url = "https:".$myve[1];
    } else { $e_url = $url; }
    if (array_key_exists("STATUS", $hed)) {
      if($hed["STATUS"][1] == 301) {
        dw("301 Moved handling.");
        $e_url = $hed["Location"];
      }
    }
  }
  // get Unique User Id cookie
  $myvh2 = get_data($e_url, Array(CURLOPT_HEADER => TRUE, CURLOPT_NOBODY => TRUE, CURLOPT_HTTPHEADER => array("Cookie:".$cookies)));
  $hed2 = headers($myvh2);
  if (array_key_exists("Set-cookie", $hed2)) {
    $cookies = $cookies . " " . tocookies($hed2["Set-cookie"]);
    dw("cookie2: $cookies");
  }
  $myvedata = get_data($e_url, Array(CURLOPT_HTTPHEADER => array("Cookie:".$cookies)));
  preg_match('/<title\>(.*?)\<\/title\>/m', $myvedata, $ttg);
  $title = $ttg[1];
  // print($title);
  preg_match('/.*?\("v=(.+?)".*/m', $myvedata, $ddg);
  $aurl = explode("&tp=", str_replace('\u0026', "&", urldecode($ddg[1])))[0];
  preg_match('/myvi[^\/]+\/embed/', $e_url, $myt);
  if (sizeof($myt) >= 1) {
    $durl = $aurl;
    if (substr($durl,0,4) != "http") {
      $durl = "https:".$durl;
    }
    $sib = 1;
  }
  $myvhed = get_data($aurl, Array(CURLOPT_REFERER => $e_url, CURLOPT_HEADER => TRUE, CURLOPT_NOBODY => TRUE, CURLOPT_HTTPHEADER => array("Cookie:".$cookies)));
  $hed3 = headers($myvhed);
  if(!array_key_exists("Location", $hed3)) {
    $myvhed = get_data($aurl, Array(CURLOPT_REFERER => $e_url, CURLOPT_HEADER => TRUE, CURLOPT_HTTPHEADER => array("Cookie:".$cookies)));
  }
  //print($myvhed);
  $durl = headers($myvhed)["Location"];
}
// outputing result
echo($durl);
echo("<br/>");
echo($title);
?>