# tile-cache-php
Use it as a caching proxy. To save bandwith of real tile servers.

## usage
  - Upload to directory
  - Change variables in index.php

Use `http://yourdomain.com/appfolder/{zoom}/{x}/{y}.png` as tile url an it serves a cached tile from e.g. `http://c.tile.openstreetmap.org/{zoom}/{x}/{y}.png`