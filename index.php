<?php

$baseDir = "/hosted/tile-cache/"; // http://example.com/$baseDir/5/54/10.png
$cacheDir = "cache";              // http://example.com/$baseDir/$cacheDir/
$errorTile = "./tile-error.png";  // relative to $baseDir
$logging = true;

$cacheHeaderTime = 60*60*24*365; // Browser Header (sec)
$cacheFileTime = 60*60*24*14; // max File Age (sec)

$tileHostSubdomains = "abc"; // optional string of possible subdomains
$tileHost = "tile.openstreetmap.org"; // tile server hostname

if(!empty($tileHostSubdomains)) {
  $tileHostSubdomain = $tileHostSubdomains[rand(0, strlen($tileHostSubdomains)-1)];
  $tileHost = $tileHostSubdomain.".".$tileHost; 
}

$requestURI = $_SERVER['REQUEST_URI'];
$requestURI = str_replace($baseDir, "", $requestURI);
list($zoom, $x, $y) = explode("/", $requestURI);

if($zoom == "" || $x == "" || $y == "") {
  var_dump($zoom);
  var_dump($x);
  var_dump($y);
  die("Nicht alle Parameter angegeben");
}

$tileUrl = "http://".$tileHost."/".$zoom."/".$x."/".$y;
$cacheTilePath = $cacheDir."/".$zoom."/".$x;
$cacheTileFile = $cacheTilePath."/".$y;

$cacheRes = true;

if(!file_exists($cacheTileFile)) {
  logger($cacheTileFile." new tile ".$tileUrl);
  $cacheRes = cacheTile();
}else{
  $fileAge = time()-filemtime($cacheTileFile);
  logger($cacheTileFile." existing tile ".$fileAge."/".$cacheFileTime);
  if($fileAge > $cacheFileTime) {
    logger($cacheTileFile." renew tile");
    $cacheRes = cacheTile();
  }
}

if(!$cacheRes) {
  $cacheTileFile = $errorTile;
}

$imgData = file_get_contents($cacheTileFile);

$file_extension = strtolower(substr(strrchr(basename($cacheTileFile),"."),1));
switch( $file_extension ) {
    case "png": $mimetype="image/png"; break;
    case "gif": $mimetype="image/gif"; break;
    case "jpeg":
    case "jpg": $mimetype="image/jpg"; break;
    default:
}

header("Cache-Control: public, max-age=".$cacheHeaderTime.", s-maxage=".$cacheHeaderTime."");
header('Content-type: ' . $mimetype);
echo $imgData;


// Functions
function cacheTile() {
  global $tileUrl, $cacheTilePath, $cacheTileFile;
  if(!file_exists($cacheTilePath)) {
    mkdir($cacheTilePath, 0777, true);
  }

  $ch = curl_init($tileUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $data = curl_exec($ch);
  $header = curl_getinfo($ch);

  if( $header['http_code'] == "200"){
    file_put_contents($cacheTileFile, $data);
    curl_close($ch);
    return true;    
  }else{
    curl_close($ch);
    return false;     
  }
}

function logger($line) {
global $logging;
  if($logging) {
    file_put_contents('log.txt', $line."\n", FILE_APPEND);
  }
}