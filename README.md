# tile-cache-php
PHP caching proxy between tile server and your map application. To save bandwith of the real tile servers.

## usage
Change variables in index.php and set RewriteBase in .htaccess.

Use `https://yourdomain.com/appfolder/https://tileserver.com/{zoom}/{x}/{y}.png` as tile url and it serves a cached tile from e.g. `http://c.tile.openstreetmap.org/{zoom}/{x}/{y}.png`

Add other tile server by extending the $domains array.

### leaflet Example
```
var tile_osm = L.tileLayer('https://yourdomain.com/appfolder/https://http://{s}.tile.openstreetmap.org/{zoom}/{x}/{y}.png', {
  attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);
```
