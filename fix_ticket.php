<?php
function replace_static_maps($file) {
    $content = file_get_contents($file);
    if (strpos($content, '$mapboxToken') === false) {
        $content = preg_replace('/(require_once.*?;\n)/', "$1\$mapboxToken = defined('MAPBOX_TOKEN') ? MAPBOX_TOKEN : 'pk.TU_MAPBOX_TOKEN_AQUI';\n", $content);
    }
    if (strpos($content, 'const mapboxToken =') === false) {
        $content = str_replace('<script>', "<script>\nconst mapboxToken = '<?php echo \$mapboxToken; ?>';\n", $content);
    }
    $content = preg_replace('/https:\/\/maps\.googleapis\.com\/maps\/api\/staticmap\?center=\$\{?(.*?)\}?,\$\{?(.*?)\}?&zoom=15&size=300x150&markers=color:red.*?&key=[A-Za-z0-9_-]+/', 'https://api.mapbox.com/styles/v1/mapbox/streets-v12/static/pin-s-marker+ff0000($2,$1)/$2,$1,15,0/300x150?access_token=${mapboxToken}', $content);
    $content = preg_replace('/https:\/\/maps\.googleapis\.com\/maps\/api\/staticmap\?center=\$\{?(.*?)\}?,\$\{?(.*?)\}?&zoom=15&size=300x150&markers=color:blue.*?&key=[A-Za-z0-9_-]+/', 'https://api.mapbox.com/styles/v1/mapbox/streets-v12/static/pin-s-marker+0000ff($2,$1)/$2,$1,15,0/300x150?access_token=${mapboxToken}', $content);
    $content = preg_replace('/<script src="https:\/\/maps\.googleapis\.com\/maps\/api\/js\?key=.*?"><\/script>/', '', $content);
    file_put_contents($file, $content);
}

function migrate_live_map($file) {
    $content = file_get_contents($file);
    $mapbox_scripts = <<<EOT
<link href="https://api.mapbox.com/mapbox-gl-js/v3.2.0/mapbox-gl.css" rel="stylesheet">
<script src="https://api.mapbox.com/mapbox-gl-js/v3.2.0/mapbox-gl.js"></script>
EOT;
    $content = str_replace("</head>", $mapbox_scripts . "\n</head>", $content);
    $google_map = '/locationMap = new google\.maps\.Map\(document\.getElementById\("mapContainer"\), \{.*?\}\);/s';
    $mapbox_map = <<<EOT
mapboxgl.accessToken = mapboxToken;
locationMap = new mapboxgl.Map({
    container: 'mapContainer',
    style: 'mapbox://styles/mapbox/streets-v12',
    center: [lng, lat],
    zoom: 16
});
EOT;
    $content = preg_replace($google_map, $mapbox_map, $content);
    $google_marker = '/new google\.maps\.Marker\(\{.*?position: \{ lat, lng \}.*?map: locationMap.*?\}\);/s';
    $mapbox_marker = <<<EOT
new mapboxgl.Marker({color: 'red'})
    .setLngLat([lng, lat])
    .addTo(locationMap);
EOT;
    $content = preg_replace($google_marker, $mapbox_marker, $content);
    file_put_contents($file, $content);
}

replace_static_maps("ticket.php");
migrate_live_map("ticket.php");
?>