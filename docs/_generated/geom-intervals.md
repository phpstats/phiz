```php
use Phiz\Plot;

$estimates = [
    ['site' => 'North', 'mean' => 4.1, 'lower' => 3.2, 'upper' => 5.0],
    ['site' => 'East', 'mean' => 5.3, 'lower' => 4.6, 'upper' => 6.1],
    ['site' => 'South', 'mean' => 3.4, 'lower' => 2.1, 'upper' => 4.6],
    ['site' => 'West', 'mean' => 4.8, 'lower' => 4.2, 'upper' => 5.5],
];

$plot = Plot::of($estimates)
    ->aes(x: 'site', y: 'mean', ymin: 'lower', ymax: 'upper')
    ->errorbar(width: 10)
    ->point(size: 60)
    ->hline(4.5, linetype: 'dashed', color: '#888888')
    ->labs(x: null, y: 'Mean score (95% CI)');
```

```{=html}
<div class="phiz-figure"><div data-phiz="{&quot;$schema&quot;:&quot;https://vega.github.io/schema/vega-lite/v6.json&quot;,&quot;data&quot;:{&quot;values&quot;:[{&quot;site&quot;:&quot;North&quot;,&quot;mean&quot;:4.1,&quot;lower&quot;:3.2,&quot;upper&quot;:5.0},{&quot;site&quot;:&quot;East&quot;,&quot;mean&quot;:5.3,&quot;lower&quot;:4.6,&quot;upper&quot;:6.1},{&quot;site&quot;:&quot;South&quot;,&quot;mean&quot;:3.4,&quot;lower&quot;:2.1,&quot;upper&quot;:4.6},{&quot;site&quot;:&quot;West&quot;,&quot;mean&quot;:4.8,&quot;lower&quot;:4.2,&quot;upper&quot;:5.5}]},&quot;width&quot;:&quot;container&quot;,&quot;height&quot;:320,&quot;layer&quot;:[{&quot;mark&quot;:{&quot;type&quot;:&quot;errorbar&quot;,&quot;ticks&quot;:{&quot;size&quot;:10},&quot;color&quot;:&quot;#4C78A8&quot;},&quot;encoding&quot;:{&quot;x&quot;:{&quot;field&quot;:&quot;site&quot;,&quot;type&quot;:&quot;nominal&quot;,&quot;title&quot;:null},&quot;y&quot;:{&quot;field&quot;:&quot;lower&quot;,&quot;type&quot;:&quot;quantitative&quot;,&quot;title&quot;:&quot;Mean score (95% CI)&quot;,&quot;scale&quot;:{&quot;zero&quot;:false}},&quot;y2&quot;:{&quot;field&quot;:&quot;upper&quot;}}},{&quot;mark&quot;:{&quot;type&quot;:&quot;point&quot;,&quot;filled&quot;:true,&quot;size&quot;:60},&quot;encoding&quot;:{&quot;x&quot;:{&quot;field&quot;:&quot;site&quot;,&quot;type&quot;:&quot;nominal&quot;,&quot;title&quot;:null},&quot;y&quot;:{&quot;field&quot;:&quot;mean&quot;,&quot;type&quot;:&quot;quantitative&quot;,&quot;title&quot;:&quot;Mean score (95% CI)&quot;,&quot;scale&quot;:{&quot;zero&quot;:false}}}},{&quot;data&quot;:{&quot;values&quot;:[{}]},&quot;mark&quot;:{&quot;type&quot;:&quot;rule&quot;,&quot;color&quot;:&quot;#888888&quot;,&quot;strokeDash&quot;:[4,4]},&quot;encoding&quot;:{&quot;y&quot;:{&quot;datum&quot;:4.5,&quot;title&quot;:&quot;Mean score (95% CI)&quot;}}}],&quot;config&quot;:{&quot;axisQuantitative&quot;:{&quot;tickCount&quot;:5},&quot;style&quot;:{&quot;phiz-fit&quot;:{&quot;color&quot;:&quot;#3366FF&quot;},&quot;phiz-band&quot;:{&quot;color&quot;:&quot;#999999&quot;,&quot;opacity&quot;:0.4}}},&quot;usermeta&quot;:{&quot;phiz&quot;:{&quot;dark&quot;:{&quot;background&quot;:&quot;transparent&quot;,&quot;view&quot;:{&quot;fill&quot;:null,&quot;stroke&quot;:&quot;#3F3F46&quot;},&quot;axis&quot;:{&quot;gridColor&quot;:&quot;rgba(255, 255, 255, 0.12)&quot;,&quot;gridOpacity&quot;:1,&quot;domainColor&quot;:&quot;#71717A&quot;,&quot;tickColor&quot;:&quot;#71717A&quot;,&quot;labelColor&quot;:&quot;#A1A1AA&quot;,&quot;titleColor&quot;:&quot;#E4E4E7&quot;},&quot;legend&quot;:{&quot;labelColor&quot;:&quot;#E4E4E7&quot;,&quot;titleColor&quot;:&quot;#E4E4E7&quot;},&quot;header&quot;:{&quot;labelColor&quot;:&quot;#E4E4E7&quot;,&quot;titleColor&quot;:&quot;#E4E4E7&quot;},&quot;title&quot;:{&quot;color&quot;:&quot;#E4E4E7&quot;,&quot;subtitleColor&quot;:&quot;#A1A1AA&quot;},&quot;mark&quot;:{&quot;color&quot;:&quot;#8AB4F8&quot;},&quot;point&quot;:{&quot;color&quot;:&quot;#8AB4F8&quot;},&quot;line&quot;:{&quot;color&quot;:&quot;#8AB4F8&quot;},&quot;text&quot;:{&quot;color&quot;:&quot;#E4E4E7&quot;},&quot;rule&quot;:{&quot;color&quot;:&quot;#A1A1AA&quot;},&quot;style&quot;:{&quot;phiz-fit&quot;:{&quot;color&quot;:&quot;#A5C8FF&quot;},&quot;phiz-band&quot;:{&quot;color&quot;:&quot;#FFFFFF&quot;,&quot;opacity&quot;:0.16}}}}}}" data-phiz-options="{&quot;actions&quot;:false}" aria-label="geom-intervals"><div data-phiz-view wire:ignore></div></div></div>
```
