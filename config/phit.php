<?php

return [
    /*
     * Where the browser loads vega, vega-lite and vega-embed from:
     * 'cdn' (pinned jsDelivr copies with subresource integrity), a URL prefix serving vega.min.js,
     * vega-lite.min.js and vega-embed.min.js, or 'none' when your bundle already sets window.vegaEmbed.
     */
    'assets' => env('PHIT_ASSETS', 'cdn'),

    /*
     * Whether <x-phit> adds the script tags itself, once per page. Turn this off and put @phitScripts in
     * your layout to control where they go.
     */
    'inject_scripts' => true,

    /*
     * Default vega-embed options for every chart. 'theme' => 'auto' follows the visitor's light or dark
     * mode preference.
     */
    'embed_options' => [
        'actions' => false,
    ],

    /*
     * Large data. Above server_rows inline rows, bin2d(), histogram(), and hex() bin in PHP and embed only the
     * bins. A spec that embeds more than warn_rows rows raises a warning (null turns it off).
     */
    'server_rows' => 10_000,
    'warn_rows' => 20_000,
];
