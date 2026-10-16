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
<div class="phiz-figure"><div data-phiz="{&quot;$schema&quot;:&quot;https://vega.github.io/schema/vega-lite/v6.json&quot;,&quot;data&quot;:{&quot;values&quot;:[{&quot;month&quot;:&quot;2026-01-01&quot;,&quot;visits&quot;:1200},{&quot;month&quot;:&quot;2026-02-01&quot;,&quot;visits&quot;:1350},{&quot;month&quot;:&quot;2026-03-01&quot;,&quot;visits&quot;:1290},{&quot;month&quot;:&quot;2026-04-01&quot;,&quot;visits&quot;:1610},{&quot;month&quot;:&quot;2026-05-01&quot;,&quot;visits&quot;:1820},{&quot;month&quot;:&quot;2026-06-01&quot;,&quot;visits&quot;:1765}]},&quot;layer&quot;:[{&quot;mark&quot;:{&quot;type&quot;:&quot;line&quot;},&quot;encoding&quot;:{&quot;x&quot;:{&quot;field&quot;:&quot;month&quot;,&quot;type&quot;:&quot;temporal&quot;},&quot;y&quot;:{&quot;field&quot;:&quot;visits&quot;,&quot;type&quot;:&quot;quantitative&quot;,&quot;scale&quot;:{&quot;zero&quot;:false}}}},{&quot;mark&quot;:{&quot;type&quot;:&quot;point&quot;,&quot;filled&quot;:true,&quot;size&quot;:50},&quot;encoding&quot;:{&quot;x&quot;:{&quot;field&quot;:&quot;month&quot;,&quot;type&quot;:&quot;temporal&quot;},&quot;y&quot;:{&quot;field&quot;:&quot;visits&quot;,&quot;type&quot;:&quot;quantitative&quot;,&quot;scale&quot;:{&quot;zero&quot;:false}}}}]}" data-phiz-options="{&quot;actions&quot;:false}" aria-label="data-columns"><div data-phiz-view wire:ignore></div></div></div>
```
