<?php

$baseDir = "maps/tile-cache"; // http://example.com/$baseDir/5/54/10.png; eg. sub/folder
$cacheDir = "cache";              // http://example.com/$baseDir/$cacheDir/
$errorTile = "./tile-error.png";  // relative to $baseDir
$logging = false;

$cacheHeaderTime = 60*60*24*365; // Browser Header (sec)
$cacheFileTime = 60*60*24*365/4; // max File Age (sec)

$maptypes = array();

$maptypes['osm']['url'] = "http://{s}.tile.osm.org/{z}/{x}/{y}";
$maptypes['osm']['subdomains'] = "abc";
$maptypes['stamen-watercolor']['url'] = "http://stamen-tiles-{s}.a.ssl.fastly.net/watercolor/{z}/{x}/{y}";
$maptypes['stamen-watercolor']['subdomains'] = "abcd";

/* +++++++++++++++++++++++++++++++++ */

$requestURI = $_SERVER['REQUEST_URI'];

if(substr($requestURI, 0, 1) == "/") {
  $requestURI = substr($requestURI, 1);
}

$requestURI = str_replace($baseDir."/", "", $requestURI);
list($maptype, $zoom, $x, $y) = explode("/", $requestURI);

if($maptype == "" || $zoom == "" || $x == "" || $y == "") {
  var_dump($maptype);
  var_dump($zoom);
  var_dump($x);
  var_dump($y);
  die("Nicht alle Parameter angegeben: /{maptype}/{zoom}/{x}/{y}.png");
}

if(!array_key_exists($maptype, $maptypes)) {
  var_dump($maptype);
  die("Unbekannter Maptype");
}

$tileUrl = $maptypes[$maptype]['url']; 
$tileHostSubdomains = $maptypes[$maptype]['subdomains']; 

$tileUrl = str_replace("{z}", $zoom, $tileUrl);
$tileUrl = str_replace("{x}", $x, $tileUrl);
$tileUrl = str_replace("{y}", $y, $tileUrl);

if($tileHostSubdomains) {
  $tileHostSubdomain = $tileHostSubdomains[rand(0, strlen($tileHostSubdomains)-1)];
  $tileUrl = str_replace("{s}", $tileHostSubdomain, $tileUrl);
}

$cacheDir = $cacheDir."/".$maptype;
$cacheTileFile = $cacheDir."/".$zoom."/".$x."/".$y;

if(!$imgData = cacheTile($tileUrl, $cacheTileFile)) {
  logger($tileUrl." using error Tile");
  $imgData = file_get_contents($errorTile);
}

$file_extension = strtolower(substr(strrchr(basename($cacheTileFile),"."),1));

switch( $file_extension ) {
  case "png": $mimetype="image/png"; break;
  case "gif": $mimetype="image/gif"; break;
  case "jpeg":
  case "jpg": $mimetype="image/jpg"; break;
  default:
}


//set last-modified header
// TODO: make $imgData->data and $imgData->lastModified
#header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastModified)." GMT");

//set expires header
header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + ($cacheHeaderTime)));

//set etag-header
header("Etag: ".md5($imgData));

//make sure caching is turned on
header('Cache-Control: public');

// content type
header('Content-type: ' . $mimetype);

echo $imgData;


// Functions
function cacheTile($tileUrl, $cacheTileFile) {
  global $cacheFileTime;

  if(file_exists($cacheTileFile)) {
    logger($tileUrl." Tile exists");
    $fileAge = time()-filemtime($cacheTileFile);
    if($fileAge > $cacheFileTime) {
      logger($tileUrl." cache old");
      if($data = downloadTile($tileUrl)) {
        logger($tileUrl." cache renewed");
        saveTile($cacheTileFile, $data);
        return $data;
      }else{
        logger($tileUrl." cache renew error");
        return false;
      }
    }
    logger($tileUrl." use cache");
    return file_get_contents($cacheTileFile);
  }else{
    logger($tileUrl." Tile not cached");
    if($data = downloadTile($tileUrl)) {
      logger($tileUrl." cache created");
      saveTile($cacheTileFile, $data);
      return $data;
    }else{
      logger($tileUrl." cache create error");
      return false;
    }
  }
}

function saveTile($cacheTileFile, $data) {
  if(!file_exists(dirname($cacheTileFile))) {
    mkdir(dirname($cacheTileFile), 0777, true);
  }
  return file_put_contents($cacheTileFile, $data);
}

function downloadTile($tileUrl) {
  $ch = curl_init($tileUrl);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

  $data = curl_exec($ch);
  $header = curl_getinfo($ch);

  if( $header['http_code'] == "200"){
    curl_close($ch);
    return $data;
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
