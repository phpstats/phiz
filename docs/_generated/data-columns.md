```php
use Phiz\Plot;

$columns = [
    'month' => ['2026-01-01', '2026-02-01', '2026-03-01', '2026-04-01', '2026-05-01', '2026-06-01'],
    'visits' => [1200, 1350, 1290, 1610, 1820, 1765],
];

$plot = Plot::of($columns)
    ->aes(x: 'month', y: 'visits')
    ->line()
    ->point(size: 50);
```

```{=html}
<div class="phiz-figure"><div data-phiz="{&quot;$schema&quot;:&quot;https://vega.github.io/schema/vega-lite/v6.json&quot;,&quot;data&quot;:{&quot;values&quot;:[{&quot;month&quot;:&quot;2026-01-01&quot;,&quot;visits&quot;:1200},{&quot;month&quot;:&quot;2026-02-01&quot;,&quot;visits&quot;:1350},{&quot;month&quot;:&quot;2026-03-01&quot;,&quot;visits&quot;:1290},{&quot;month&quot;:&quot;2026-04-01&quot;,&quot;visits&quot;:1610},{&quot;month&quot;:&quot;2026-05-01&quot;,&quot;visits&quot;:1820},{&quot;month&quot;:&quot;2026-06-01&quot;,&quot;visits&quot;:1765}]},&quot;width&quot;:&quot;container&quot;,&quot;height&quot;:320,&quot;layer&quot;:[{&quot;mark&quot;:{&quot;type&quot;:&quot;line&quot;},&quot;encoding&quot;:{&quot;x&quot;:{&quot;field&quot;:&quot;month&quot;,&quot;type&quot;:&quot;temporal&quot;},&quot;y&quot;:{&quot;field&quot;:&quot;visits&quot;,&quot;type&quot;:&quot;quantitative&quot;,&quot;scale&quot;:{&quot;zero&quot;:false}}}},{&quot;mark&quot;:{&quot;type&quot;:&quot;point&quot;,&quot;filled&quot;:true,&quot;size&quot;:50},&quot;encoding&quot;:{&quot;x&quot;:{&quot;field&quot;:&quot;month&quot;,&quot;type&quot;:&quot;temporal&quot;},&quot;y&quot;:{&quot;field&quot;:&quot;visits&quot;,&quot;type&quot;:&quot;quantitative&quot;,&quot;scale&quot;:{&quot;zero&quot;:false}}}}],&quot;config&quot;:{&quot;axisQuantitative&quot;:{&quot;tickCount&quot;:5},&quot;style&quot;:{&quot;phiz-fit&quot;:{&quot;color&quot;:&quot;#3366FF&quot;},&quot;phiz-band&quot;:{&quot;color&quot;:&quot;#999999&quot;,&quot;opacity&quot;:0.4}}},&quot;usermeta&quot;:{&quot;phiz&quot;:{&quot;dark&quot;:{&quot;background&quot;:&quot;transparent&quot;,&quot;view&quot;:{&quot;fill&quot;:null,&quot;stroke&quot;:&quot;#3F3F46&quot;},&quot;axis&quot;:{&quot;gridColor&quot;:&quot;rgba(255, 255, 255, 0.12)&quot;,&quot;gridOpacity&quot;:1,&quot;domainColor&quot;:&quot;#71717A&quot;,&quot;tickColor&quot;:&quot;#71717A&quot;,&quot;labelColor&quot;:&quot;#A1A1AA&quot;,&quot;titleColor&quot;:&quot;#E4E4E7&quot;},&quot;legend&quot;:{&quot;labelColor&quot;:&quot;#E4E4E7&quot;,&quot;titleColor&quot;:&quot;#E4E4E7&quot;},&quot;header&quot;:{&quot;labelColor&quot;:&quot;#E4E4E7&quot;,&quot;titleColor&quot;:&quot;#E4E4E7&quot;},&quot;title&quot;:{&quot;color&quot;:&quot;#E4E4E7&quot;,&quot;subtitleColor&quot;:&quot;#A1A1AA&quot;},&quot;mark&quot;:{&quot;color&quot;:&quot;#8AB4F8&quot;},&quot;point&quot;:{&quot;color&quot;:&quot;#8AB4F8&quot;},&quot;line&quot;:{&quot;color&quot;:&quot;#8AB4F8&quot;},&quot;text&quot;:{&quot;color&quot;:&quot;#E4E4E7&quot;},&quot;rule&quot;:{&quot;color&quot;:&quot;#A1A1AA&quot;},&quot;style&quot;:{&quot;phiz-fit&quot;:{&quot;color&quot;:&quot;#A5C8FF&quot;},&quot;phiz-band&quot;:{&quot;color&quot;:&quot;#FFFFFF&quot;,&quot;opacity&quot;:0.16}}}}}}" data-phiz-options="{&quot;actions&quot;:false}" aria-label="data-columns"><div data-phiz-view wire:ignore></div></div></div>
```
