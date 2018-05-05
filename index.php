<?php

$cacheDir = "cache";              // http://example.com/$baseDir/$cacheDir/
$errorTile = "./tile-error.png";  // relative to $baseDir
$logging = false;

$cacheHeaderTime = 60*60*24*365; // Browser Header (sec)
$cacheFileTime = 60*60*24*356 / 4; // max File Age (sec)

$domains = array(
	"a.tile.openstreetmap.org" => "osm",
	"b.tile.openstreetmap.org" => "osm",
	"c.tile.openstreetmap.org" => "osm",
	"secais.dfs.de" => "icao"
);

$tileURL = str_replace(str_replace(basename(__FILE__), '', $_SERVER['SCRIPT_NAME']), "", $_SERVER['REQUEST_URI']);
$tileURLParts = parse_url($tileURL);
$tileDomain = $tileURLParts['host'];

$cacheDir = $cacheDir."/".$domains[$tileDomain];
$cacheTileFile = $cacheDir."/".substr($tileURLParts['path'], 1);

if(!array_key_exists($tileDomain, $domains)) {
	die('Domain not allowed');
}

$cacheRes = true;

if(!file_exists($cacheTileFile)) {
  logger($cacheTileFile." new tile ".$tileURL);
  $cacheRes = cacheTile($tileURL, $cacheTileFile);
}else{
  $fileAge = time()-filemtime($cacheTileFile);
  logger($cacheTileFile." existing tile ".$fileAge."/".$cacheFileTime);
  if($fileAge > $cacheFileTime) {
    logger($cacheTileFile." renew tile");
    $cacheRes = cacheTile($tileURL, $cacheTileFile);
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
function cacheTile($tileURL, $cacheTileFile) {
  if(!file_exists(dirname($cacheTileFile))) {
    mkdir(dirname($cacheTileFile), 0777, true);
  }

  $ch = curl_init($tileURL);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);


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