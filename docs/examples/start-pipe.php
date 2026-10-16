<?php

use Phiz\Datasets;

use function Phiz\{aes, geom_point, geom_smooth, ggplot, labs, theme_minimal};

return ggplot(Datasets::mpg(), aes(x: 'displ', y: 'hwy', color: 'drv'))
    |> geom_point(alpha: 0.5)
    |> geom_smooth(method: 'lm', se: false)
    |> labs(x: 'Displacement (L)', y: 'Highway mpg', color: 'Drive')
    |> theme_minimal();
