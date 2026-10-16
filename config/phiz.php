<?php

return [
    /*
     * Where the browser loads vega, vega-lite and vega-embed from:
     * 'cdn' (pinned jsDelivr copies with subresource integrity), a URL prefix serving vega.min.js,
     * vega-lite.min.js and vega-embed.min.js, or 'none' when your bundle already sets window.vegaEmbed.
     */
    'assets' => env('PHIZ_ASSETS', 'cdn'),

    /*
     * Whether <x-phiz> adds the script tags itself, once per page. Turn this off and put @phizScripts in
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
];
