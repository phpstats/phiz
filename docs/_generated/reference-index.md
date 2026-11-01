## [Plots and data](reference-plots.qmd)

| Function | Method | Purpose |
| --- | --- | --- |
| [`ggplot()`](reference-plots.qmd#ggplot-function) | [`Plot::of()`](reference-plots.qmd#of) | A plot of rows, columns, a phibble Table, a Laravel Collection, or Data::url(...), with optional mappings. |
|  | [`->aes()`](reference-plots.qmd#aes) | Adds plot-wide aesthetic mappings: x, y, color, fill, size, shape, alpha, group, tooltip, label, ... |
|  | [`->data()`](reference-plots.qmd#data) | Replaces the plot's data with any supported input, keeping the mappings and layers. |
|  | [`Data::from()`](reference-plots.qmd#data-from) | Wraps any supported input: rows, columns, a table, a collection, an iterable, or a Data object as given. |
|  | [`Data::url()`](reference-plots.qmd#data-url) | Data that Vega-Lite fetches from a URL (CSV, TSV, JSON); types can't be inferred, so use 'field:Q' shorthand. |
|  | [`Data::named()`](reference-plots.qmd#data-named) | A named data source the page supplies at runtime (vega-embed's view.data(name, rows)). |
|  | [`$data->isInline()`](reference-plots.qmd#data-is-inline) | Whether the data is embedded, rather than a URL or a named source. |
|  | [`$data->isEmpty()`](reference-plots.qmd#data-is-empty) | Whether inline data has no rows. |
|  | [`$data->count()`](reference-plots.qmd#data-count) | The number of inline rows, without building them. |
|  | [`$data->toSpec()`](reference-plots.qmd#data-to-spec) | The Vega-Lite data definition: values, a url, or a name. |
|  | [`$data->fields()`](reference-plots.qmd#data-fields) | The field names of inline data, in first-seen order. |
|  | [`$data->has()`](reference-plots.qmd#data-has) | Whether inline data has the field. |
|  | [`$data->column()`](reference-plots.qmd#data-column) | A field's values, JSON-safe, read from the table or columns without building rows. |
|  | [`$data->type()`](reference-plots.qmd#data-type) | The Vega-Lite measurement type of a field: quantitative when every non-null value is a number, temporal when every value is an ISO date string, nominal otherwise. |
|  | [`$data->isTime()`](reference-plots.qmd#data-is-time) | Whether a field held phibble times, which are plotted as seconds on a continuous scale, as ggplot2 plots hms. |
|  | [`$data->levels()`](reference-plots.qmd#data-levels) | Distinct non-null values of a field in first-seen order. |
|  | [`$data->withRows()`](reference-plots.qmd#data-with-rows) | New inline data holding the given rows. |
|  | [`Data::value()`](reference-plots.qmd#data-value) | One value made JSON-safe: non-finite floats become null, dates ISO 8601 strings, phibble times seconds, enums their values or case names, and Stringable objects strings. |
|  | [`Datasets::mpg()`](reference-plots.qmd#datasets-mpg) | ggplot2's mpg: 234 cars from 1999 and 2008 with displacement (displ, liters), city and highway mpg (cty, hwy), drive (drv), and class. |
|  | [`Datasets::mtcars()`](reference-plots.qmd#datasets-mtcars) | R's mtcars: 32 cars with mpg, cylinders (cyl), weight (wt, 1,000 lb), horsepower (hp), transmission (am, 1 manual), and the model name. |
|  | [`Datasets::economics()`](reference-plots.qmd#datasets-economics) | ggplot2's economics: 574 monthly US rows from 1967 with date, pce (\$bn), pop (thousands), psavert (%), uempmed (weeks), and unemploy (thousands). |
|  | [`Datasets::faithful()`](reference-plots.qmd#datasets-faithful) | R's faithful: 272 Old Faithful eruptions with their length (eruptions) and the wait before them (waiting), in minutes. |
|  | [`Datasets::load()`](reference-plots.qmd#datasets-load) | A bundled dataset by name, from resources/data/{name}.csv, cached after the first load; an unknown name throws. |
|  | [`Bin::histogram()`](reference-plots.qmd#bin-histogram) | Equal-width bins of x: rows with x0, x1, count, and density (count / (n * width)), per group. |
|  | [`Bin::grid()`](reference-plots.qmd#bin-grid) | Counts in a grid of x-by-y rectangles: rows with x_start, x_end, y_start, y_end (named after the fields) and count. |
|  | [`Bin::hex()`](reference-plots.qmd#bin-hex) | Counts in a hexagonal grid, as ggplot2's geom_hex: rows with the hexagon centers (under the x and y names) and count. bins hexagons span the x range; aspect (height / width of the panel in pixels) keeps them regular on screen. |
|  | [`Bin::lttb()`](reference-plots.qmd#bin-lttb) | Downsamples a line to about \$threshold points with Largest-Triangle-Three-Buckets, which keeps the visual shape (peaks and troughs). |
|  | [`Bin::sample()`](reference-plots.qmd#bin-sample) | A uniform random sample of n rows (reservoir sampling, one pass), reproducible with a seed. |
|  | [`Bin::range()`](reference-plots.qmd#bin-range) | The [min, max] of a numeric field, padded when every value is equal. |
|  | [`Bin::values()`](reference-plots.qmd#bin-values) | The values of the named fields, one list per record. |
| [`aes()`](reference-plots.qmd#aes-function) |  | Aesthetic mappings: aes(x: 'displ', y: 'hwy', color: 'class'). |
| [`field()`](reference-plots.qmd#field-function) |  | A field with an explicit type or other Vega-Lite field-definition properties. |
| [`factor()`](reference-plots.qmd#factor-function) |  | A field treated as discrete categories, like R's factor(cyl). |
| [`calc()`](reference-plots.qmd#calc-function) |  | A field computed from a Vega expression: calc('datum.hwy - datum.cty', as: 'gap'). |
| [`expr()`](reference-plots.qmd#expr-function) |  | A Vega expression, such as a slider parameter's name. |

## [Geoms](reference-geoms.qmd)

| Function | Method | Purpose |
| --- | --- | --- |
| [`geom_point()`](reference-geoms.qmd#geom_point) | [`->point()`](reference-geoms.qmd#point) | Scatterplot points. jitter: true (or a width, or [width, height]) displaces them randomly. seed: makes the jitter reproducible: phiz draws it in PHP and embeds it in the data, so every render places the points the same way. |
| [`geom_jitter()`](reference-geoms.qmd#geom_jitter) | [`->jitter()`](reference-geoms.qmd#jitter) | Points jittered by up to width/height (fraction of a band on discrete axes, data units otherwise). |
| [`geom_line()`](reference-geoms.qmd#geom_line) | [`->line()`](reference-geoms.qmd#line) | Lines connecting points in order of x. downsample: n keeps about n points per group (LTTB), in PHP. |
| [`geom_path()`](reference-geoms.qmd#geom_path) | [`->path()`](reference-geoms.qmd#path) | Lines connecting points in data order. |
| [`geom_step()`](reference-geoms.qmd#geom_step) | [`->step()`](reference-geoms.qmd#step) | Stairsteps: direction 'hv' (horizontal first), 'vh', or 'mid'. |
| [`geom_area()`](reference-geoms.qmd#geom_area) | [`->area()`](reference-geoms.qmd#area) | Areas from zero, stacked by fill (position: stack, fill, identity). |
| [`geom_ribbon()`](reference-geoms.qmd#geom_ribbon) | [`->ribbon()`](reference-geoms.qmd#ribbon) | A band between ymin and ymax (or xmin and xmax). |
| [`geom_bar()`](reference-geoms.qmd#geom_bar) | [`->bar()`](reference-geoms.qmd#bar) | Bars of counts of x (or y), or of the y values when y is mapped. position: stack, dodge, fill, identity. |
| [`geom_col()`](reference-geoms.qmd#geom_col) | [`->col()`](reference-geoms.qmd#col) | Bars whose heights are the y values. |
| [`geom_histogram()`](reference-geoms.qmd#geom_histogram) | [`->histogram()`](reference-geoms.qmd#histogram) | Binned counts of x: bins (a maximum; Vega-Lite picks nice boundaries) or binwidth. density: true scales to unit area. compute: 'server' bins in PHP (exactly bins equal-width bins), 'browser' in Vega-Lite, and 'auto' (the default) in PHP above Config::\$serverRows rows. |
| [`geom_freqpoly()`](reference-geoms.qmd#geom_freqpoly) | [`->freqpoly()`](reference-geoms.qmd#freqpoly) | Frequency polygons: binned counts drawn as lines. |
| [`geom_density()`](reference-geoms.qmd#geom_density) | [`->density()`](reference-geoms.qmd#density) | Kernel density of x per group (Vega-Lite's density transform). bw sets the bandwidth; adjust multiplies it (from R's bw.nrd0 when bw is unset); counts: true scales to counts. |
| [`geom_boxplot()`](reference-geoms.qmd#geom_boxplot) | [`->boxplot()`](reference-geoms.qmd#boxplot) | Tukey boxplots; coef sets the whisker extent in IQRs. |
| [`geom_violin()`](reference-geoms.qmd#geom_violin) | [`->violin()`](reference-geoms.qmd#violin) | Violins: the kernel density of y per level of a discrete x. bw sets the bandwidth, adjust multiplies R's bw.nrd0, and width is the widest violin's share of its band. |
| [`geom_errorbar()`](reference-geoms.qmd#geom_errorbar) | [`->errorbar()`](reference-geoms.qmd#errorbar) | Error bars from ymin to ymax (or xmin to xmax) with end ticks; width is the tick length in pixels. |
| [`geom_linerange()`](reference-geoms.qmd#geom_linerange) | [`->linerange()`](reference-geoms.qmd#linerange) | Vertical (or horizontal) lines from ymin to ymax. |
| [`geom_pointrange()`](reference-geoms.qmd#geom_pointrange) | [`->pointrange()`](reference-geoms.qmd#pointrange) | A point at y on a line from ymin to ymax: the coefficient-plot geom. position: 'dodge' sets groups side by side. |
| [`geom_hline()`](reference-geoms.qmd#geom_hline) | [`->hline()`](reference-geoms.qmd#hline) | Horizontal reference lines at fixed values, or at a mapped yintercept field when yintercept is null. |
| [`geom_vline()`](reference-geoms.qmd#geom_vline) | [`->vline()`](reference-geoms.qmd#vline) | Vertical reference lines at fixed values, or at a mapped xintercept field. |
| [`geom_abline()`](reference-geoms.qmd#geom_abline) | [`->abline()`](reference-geoms.qmd#abline) | Lines y = intercept + slope * x across the x range (lists draw several). |
| [`geom_rule()`](reference-geoms.qmd#geom_rule) | [`->rule()`](reference-geoms.qmd#rule) | Rules at mapped positions (x, y, xend, yend). |
| [`geom_segment()`](reference-geoms.qmd#geom_segment) | [`->segment()`](reference-geoms.qmd#segment) | Line segments from (x, y) to (xend, yend). |
| [`geom_text()`](reference-geoms.qmd#geom_text) | [`->text()`](reference-geoms.qmd#text) | Text from the label aesthetic. nudgeX/nudgeY offset it in pixels; angle turns it anticlockwise. |
| [`geom_label()`](reference-geoms.qmd#geom_label) | [`->label()`](reference-geoms.qmd#label) | Text from the label aesthetic in bold, standing in for geom_label(); Vega-Lite draws no box behind it. |
| [`geom_tile()`](reference-geoms.qmd#geom_tile) | [`->tile()`](reference-geoms.qmd#tile) | Heatmap tiles at discrete x and y (numbers are treated as ordered levels), filled by the fill aesthetic. |
| [`geom_raster()`](reference-geoms.qmd#geom_raster) | [`->raster()`](reference-geoms.qmd#raster) | Heatmap tiles, as tile() draws them; ggplot2's geom_raster() name. |
| [`geom_rect()`](reference-geoms.qmd#geom_rect) | [`->rect()`](reference-geoms.qmd#rect) | Rectangles from xmin, xmax, ymin, ymax. |
| [`geom_bin2d()`](reference-geoms.qmd#geom_bin2d) | [`->bin2d()`](reference-geoms.qmd#bin2d) | A 2D histogram: counts in x-by-y bins; compute works as in histogram(). |
| [`geom_hex()`](reference-geoms.qmd#geom_hex) | [`->hex()`](reference-geoms.qmd#hex) | Hexagonal bins counted in PHP, as ggplot2's geom_hex: bins hexagons across the x range. |
| [`geom_smooth()`](reference-geoms.qmd#geom_smooth) | [`->smooth()`](reference-geoms.qmd#smooth) | A fitted curve per group. method: loess (span sets the bandwidth), lm, poly (order), quad, log, exp, pow. |
| [`geom_density_2d()`](reference-geoms.qmd#geom_density_2d), [`stat_density_2d()`](reference-geoms.qmd#stat_density_2d) | [`->density2d()`](reference-geoms.qmd#density2d) | Contour lines of a 2D kernel density of x and y, as ggplot2's geom_density_2d (MASS::kde2d on an n × n grid). h sets the bandwidths ([x, y] or one for both; default MASS::bandwidth.nrd), adjust multiplies them, and bins, binwidth, or breaks set the levels (default pretty(range, 10)). contourVar: density, ndensity (scaled to a maximum of 1 per group), or count. aes(color: 'level') colors lines by their level. |
| [`geom_density_2d_filled()`](reference-geoms.qmd#geom_density_2d_filled), [`stat_density_2d_filled()`](reference-geoms.qmd#stat_density_2d_filled) | [`->density2dFilled()`](reference-geoms.qmd#density2d-filled) | Filled bands of a 2D kernel density between consecutive breaks, as ggplot2's geom_density_2d_filled. |
| [`geom_contour()`](reference-geoms.qmd#geom_contour), [`stat_contour()`](reference-geoms.qmd#stat_contour) | [`->contour()`](reference-geoms.qmd#contour) | Contour lines of z over a regular grid of x and y, as ggplot2's geom_contour; bins, binwidth, or breaks. |
| [`geom_contour_filled()`](reference-geoms.qmd#geom_contour_filled) | [`->contourFilled()`](reference-geoms.qmd#contour-filled) | Filled contour bands of z over a regular grid, as ggplot2's geom_contour_filled. |
| [`geom_polygon()`](reference-geoms.qmd#geom_polygon) | [`->polygon()`](reference-geoms.qmd#polygon) | Filled polygons through the points in data order, one per group (or fill level). |
| [`stat_summary()`](reference-geoms.qmd#stat_summary) | [`->summary()`](reference-geoms.qmd#summary) | Summaries of y at each x, drawn with geom (pointrange by default), as ggplot2's stat_summary. funData: mean_se (the default), mean_cl_normal, mean_sdl, median_hilow, or a callable returning [y, ymin, ymax]. fun, funMin, and funMax: mean, median, min, max, sum, sd, n, or callables of the group's values. funArgs passes mult or conf. geom: pointrange, errorbar, linerange, crossbar, point, line, col, area, or ribbon. |
| [`geom_crossbar()`](reference-geoms.qmd#geom_crossbar) | [`->crossbar()`](reference-geoms.qmd#crossbar) | A box from ymin to ymax with a line at y, as ggplot2's geom_crossbar; width is a share of the band. |
| [`stat_ellipse()`](reference-geoms.qmd#stat_ellipse) | [`->ellipse()`](reference-geoms.qmd#ellipse) | Confidence ellipses per group, as ggplot2's stat_ellipse: type 't' (a robust multivariate t fit), 'norm', or 'euclid' (a circle of radius level), at level. geom: 'polygon' fills them. |
| [`geom_count()`](reference-geoms.qmd#geom_count) | [`->count()`](reference-geoms.qmd#count) | Points sized by how many rows share each x and y, as ggplot2's geom_count. |
| [`annotate()`](reference-geoms.qmd#annotate-function) | [`->annotate()`](reference-geoms.qmd#annotate) | A layer of fixed values rather than data, as ggplot2's annotate(): annotate('text', x: 4, y: 40, label: 'Note'), annotate('rect', xmin: 2, xmax: 3, ymin: -INF ...). |
| [`geom_function()`](reference-geoms.qmd#geom_function) | [`->curve()`](reference-geoms.qmd#curve) | A function of x drawn from `from` to `to`, as ggplot2's geom_function. |
| [`geom_rug()`](reference-geoms.qmd#geom_rug) | [`->rug()`](reference-geoms.qmd#rug) | Marginal ticks: sides 'b' (bottom, for x), 'l' (left, for y), or 'bl'; length in pixels. |
| [`stat_ecdf()`](reference-geoms.qmd#stat_ecdf) | [`->ecdf()`](reference-geoms.qmd#ecdf) | Empirical cumulative distribution of x per group. |
| [`stat_qq()`](reference-geoms.qmd#stat_qq) | [`->qq()`](reference-geoms.qmd#qq) | Normal quantile-quantile points of the sample aesthetic. |
| [`stat_qq_line()`](reference-geoms.qmd#stat_qq_line) | [`->qqLine()`](reference-geoms.qmd#qq-line) | The QQ reference line through the sample's quartiles. |
| [`geom_arc()`](reference-geoms.qmd#geom_arc) | [`->arc()`](reference-geoms.qmd#arc) | Pie (or donut, with innerRadius in pixels) slices sized by theta, or counts of fill. |
|  | [`->pie()`](reference-geoms.qmd#pie) | Pie or donut slices, as arc() draws them; innerRadius in pixels makes a donut. |
|  | [`->layer()`](reference-geoms.qmd#layer) | A raw Vega-Lite layer, used as given. |
|  | [`->geom()`](reference-geoms.qmd#geom) | Adds a geom by name with options: the generic form behind the geom methods. |

## [Scales and guides](reference-scales.qmd)

| Function | Method | Purpose |
| --- | --- | --- |
|  | [`->scale()`](reference-scales.qmd#scale) | Sets the scale for an aesthetic; see Scale::make() for the options. |
| [`scale_x()`](reference-scales.qmd#scale_x), [`scale_x_continuous()`](reference-scales.qmd#scale_x_continuous), [`scale_x_log10()`](reference-scales.qmd#scale_x_log10), [`scale_x_sqrt()`](reference-scales.qmd#scale_x_sqrt), [`scale_x_reverse()`](reference-scales.qmd#scale_x_reverse), [`scale_x_date()`](reference-scales.qmd#scale_x_date), [`scale_x_discrete()`](reference-scales.qmd#scale_x_discrete) | [`->scaleX()`](reference-scales.qmd#scale-x) | x scale: type (linear, log, sqrt, time, ...), domain, range, zero, nice, reverse, name, breaks, labels, format. |
| [`scale_y()`](reference-scales.qmd#scale_y), [`scale_y_continuous()`](reference-scales.qmd#scale_y_continuous), [`scale_y_log10()`](reference-scales.qmd#scale_y_log10), [`scale_y_sqrt()`](reference-scales.qmd#scale_y_sqrt), [`scale_y_reverse()`](reference-scales.qmd#scale_y_reverse), [`scale_y_date()`](reference-scales.qmd#scale_y_date), [`scale_y_discrete()`](reference-scales.qmd#scale_y_discrete) | [`->scaleY()`](reference-scales.qmd#scale-y) | Scale and guide options for y, as scaleX() takes them. |
| [`scale_color()`](reference-scales.qmd#scale_color), [`scale_color_manual()`](reference-scales.qmd#scale_color_manual), [`scale_color_scheme()`](reference-scales.qmd#scale_color_scheme), [`scale_color_viridis()`](reference-scales.qmd#scale_color_viridis), [`scale_color_brewer()`](reference-scales.qmd#scale_color_brewer), [`scale_color_gradient()`](reference-scales.qmd#scale_color_gradient), [`scale_color_gradient2()`](reference-scales.qmd#scale_color_gradient2), [`scale_color_viridis_d()`](reference-scales.qmd#scale_color_viridis_d), [`scale_color_viridis_c()`](reference-scales.qmd#scale_color_viridis_c), [`scale_color_viridis_b()`](reference-scales.qmd#scale_color_viridis_b), [`scale_color_distiller()`](reference-scales.qmd#scale_color_distiller), [`scale_color_fermenter()`](reference-scales.qmd#scale_color_fermenter), [`scale_color_gradientn()`](reference-scales.qmd#scale_color_gradientn), [`scale_color_grey()`](reference-scales.qmd#scale_color_grey), [`scale_color_hue()`](reference-scales.qmd#scale_color_hue), [`scale_color_identity()`](reference-scales.qmd#scale_color_identity) | [`->scaleColor()`](reference-scales.qmd#scale-color) | Color scale: scheme ('viridis', 'blues', 'category10', ...), range, values (manual palette), domainMid, ... |
| [`scale_fill()`](reference-scales.qmd#scale_fill), [`scale_fill_manual()`](reference-scales.qmd#scale_fill_manual), [`scale_fill_scheme()`](reference-scales.qmd#scale_fill_scheme), [`scale_fill_viridis()`](reference-scales.qmd#scale_fill_viridis), [`scale_fill_brewer()`](reference-scales.qmd#scale_fill_brewer), [`scale_fill_gradient()`](reference-scales.qmd#scale_fill_gradient), [`scale_fill_gradient2()`](reference-scales.qmd#scale_fill_gradient2), [`scale_fill_viridis_d()`](reference-scales.qmd#scale_fill_viridis_d), [`scale_fill_viridis_c()`](reference-scales.qmd#scale_fill_viridis_c), [`scale_fill_viridis_b()`](reference-scales.qmd#scale_fill_viridis_b), [`scale_fill_distiller()`](reference-scales.qmd#scale_fill_distiller), [`scale_fill_fermenter()`](reference-scales.qmd#scale_fill_fermenter), [`scale_fill_gradientn()`](reference-scales.qmd#scale_fill_gradientn), [`scale_fill_grey()`](reference-scales.qmd#scale_fill_grey), [`scale_fill_hue()`](reference-scales.qmd#scale_fill_hue), [`scale_fill_identity()`](reference-scales.qmd#scale_fill_identity) | [`->scaleFill()`](reference-scales.qmd#scale-fill) | Scale and guide options for fill, as scaleColor() takes them. |
| [`scale_size()`](reference-scales.qmd#scale_size) | [`->scaleSize()`](reference-scales.qmd#scale-size) | Scale and guide options for size: range sets the smallest and largest areas in square pixels. |
| [`scale_shape()`](reference-scales.qmd#scale_shape) | [`->scaleShape()`](reference-scales.qmd#scale-shape) | Scale and guide options for shape: values sets the shapes, by level or in level order. |
| [`scale_alpha()`](reference-scales.qmd#scale_alpha) | [`->scaleOpacity()`](reference-scales.qmd#scale-opacity) | Scale and guide options for opacity (alpha): range sets the lowest and highest opacity. |
| [`scale_linetype()`](reference-scales.qmd#scale_linetype) | [`->scaleLinetype()`](reference-scales.qmd#scale-linetype) | Scale and guide options for linetype: values sets the dash patterns, by level or in level order. |
| [`guides()`](reference-scales.qmd#guides-function) | [`->guides()`](reference-scales.qmd#guides) | Guides per aesthetic, as ggplot2's guides(): 'none' or false hides one, and an array sets axis or legend properties (see guide_legend(), guide_colorbar(), and guide_axis()). |
| [`xlim()`](reference-scales.qmd#xlim-function) | [`->xlim()`](reference-scales.qmd#xlim) | Fixes the x domain. |
| [`ylim()`](reference-scales.qmd#ylim-function) | [`->ylim()`](reference-scales.qmd#ylim) | Fixes the y domain. |
| [`viridis_scheme()`](reference-scales.qmd#viridis_scheme) |  | viridisLite palettes by option: A magma, B inferno, C plasma, D viridis, E cividis, H turbo (or the names). |
| [`brewer_scheme()`](reference-scales.qmd#brewer_scheme) |  | A ColorBrewer scheme, reversed when direction is -1. |
| [`guide_legend()`](reference-scales.qmd#guide_legend) |  | Legend options: title, columns (ncol), direction (horizontal or vertical), and further Vega-Lite legend properties. |
| [`guide_colorbar()`](reference-scales.qmd#guide_colorbar) |  | Color bar options: barwidth and barheight in pixels. |
| [`guide_colourbar()`](reference-scales.qmd#guide_colourbar) |  | An alias of `guide_colorbar()`. |
| [`guide_axis()`](reference-scales.qmd#guide_axis) |  | Axis options: angle turns the labels anticlockwise, as ggplot2's guide_axis(angle =); position is top, bottom, left, or right. |
| [`guide_none()`](reference-scales.qmd#guide_none) |  | Hides a guide, as ggplot2's guide_none(): guides(color: guide_none()). |
| [`label_number()`](reference-scales.qmd#label_number) |  | Number labels, as scales::label_number(): accuracy rounds, scale multiplies, prefix and suffix wrap. |
| [`label_comma()`](reference-scales.qmd#label_comma) |  | Thousands separators, as scales::label_comma(). |
| [`label_percent()`](reference-scales.qmd#label_percent) |  | Percentages of proportions, as scales::label_percent(): 0.25 becomes 25%. |
| [`label_dollar()`](reference-scales.qmd#label_dollar) |  | Currency, as scales::label_dollar(). |
| [`label_scientific()`](reference-scales.qmd#label_scientific) |  | Scientific notation with digits significant digits, as scales::label_scientific(). |
| [`label_date()`](reference-scales.qmd#label_date) |  | Dates and times with a strftime-style format, as scales::label_date(). |

## [Coordinates, facets, and composition](reference-facets.qmd)

| Function | Method | Purpose |
| --- | --- | --- |
| [`coord_flip()`](reference-facets.qmd#coord_flip) | [`->coordFlip()`](reference-facets.qmd#coord-flip) | Swaps the x and y channels, so mappings stay as written while the plot turns sideways. |
| [`coord_polar()`](reference-facets.qmd#coord_polar) | [`->coordPolar()`](reference-facets.qmd#coord-polar) | Polar coordinates for bar() and col() layers, as ggplot2's coord_polar. theta: 'y' turns stacked bars into a pie (map x to a constant, or leave it out; innerRadius in pixels makes a donut), and 'x' turns bars over a discrete x into a rose of equal angles with radius y. start rotates the first slice clockwise from twelve o'clock, in radians; direction -1 runs anticlockwise. |
| [`coord_fixed()`](reference-facets.qmd#coord_fixed), [`coord_equal()`](reference-facets.qmd#coord_equal) | [`->coordFixed()`](reference-facets.qmd#coord-fixed) | A fixed aspect ratio, as ggplot2's coord_fixed: one unit of y takes ratio times the length of one unit of x. |
| [`coord_cartesian()`](reference-facets.qmd#coord_cartesian) | [`->coordCartesian()`](reference-facets.qmd#coord-cartesian) | Zooms to fixed domains, clipping marks outside them. |
| [`facet_wrap()`](reference-facets.qmd#facet_wrap) | [`->facetWrap()`](reference-facets.qmd#facet-wrap) | Small multiples wrapped into rows. scales: fixed, free, free_x, free_y. |
| [`facet_grid()`](reference-facets.qmd#facet_grid) | [`->facetGrid()`](reference-facets.qmd#facet-grid) | A grid of panels by rows and/or cols fields. |
|  | [`Compose::beside()`](reference-facets.qmd#compose-beside) | Charts side by side in one row, as patchwork's p1 + p2 (a Vega-Lite hconcat). |
|  | [`Compose::stack()`](reference-facets.qmd#compose-stack) | Charts stacked in one column, as patchwork's p1 / p2 (a Vega-Lite vconcat). |
|  | [`Compose::grid()`](reference-facets.qmd#compose-grid) | A grid filled row by row with the given number of columns. |
|  | [`$compose->title()`](reference-facets.qmd#compose-title) | A title and subtitle over the whole composition. |
|  | [`$compose->theme()`](reference-facets.qmd#compose-theme) | A theme preset and options for the whole composition, as Plot::theme(). |
|  | [`$compose->resolve()`](reference-facets.qmd#compose-resolve) | Shares or separates scales, axes, and legends across the plots, e.g. ['scale' => ['color' => 'independent']]. |
|  | [`$compose->raw()`](reference-facets.qmd#compose-raw) | Deep-merges raw Vega-Lite into the composition's top level, as Plot::raw() does. |
|  | [`$compose->toArray()`](reference-facets.qmd#compose-to-array) | The composition's Vega-Lite specification. |
|  | [`$compose->toSpec()`](reference-facets.qmd#compose-to-spec) | The composition's specification for nesting in another composition: without \$schema or config. |

## [Labels and themes](reference-themes.qmd)

| Function | Method | Purpose |
| --- | --- | --- |
| [`labs()`](reference-themes.qmd#labs-function), [`xlab()`](reference-themes.qmd#xlab-function), [`ylab()`](reference-themes.qmd#ylab-function) | [`->labs()`](reference-themes.qmd#labs) | Titles: title, subtitle, caption, and one per aesthetic (x, y, color, fill, size, shape, ...). |
| [`ggtitle()`](reference-themes.qmd#ggtitle-function) | [`->title()`](reference-themes.qmd#title) | The title and subtitle, as ggplot2's ggtitle(); null removes the title. |
| [`theme()`](reference-themes.qmd#theme-function), [`theme_gray()`](reference-themes.qmd#theme_gray), [`theme_grey()`](reference-themes.qmd#theme_grey), [`theme_bw()`](reference-themes.qmd#theme_bw), [`theme_minimal()`](reference-themes.qmd#theme_minimal), [`theme_classic()`](reference-themes.qmd#theme_classic), [`theme_light()`](reference-themes.qmd#theme_light), [`theme_dark()`](reference-themes.qmd#theme_dark), [`theme_void()`](reference-themes.qmd#theme_void), [`theme_linedraw()`](reference-themes.qmd#theme_linedraw) | [`->theme()`](reference-themes.qmd#theme) | A theme preset (gray, bw, linedraw, minimal, classic, light, dark, void, vega) and/or individual options; config is raw Vega-Lite config merged last. |
| [`size()`](reference-themes.qmd#size-function) | [`->size()`](reference-themes.qmd#size) | Width and height in pixels (per panel when faceted); 'container' fills the parent element's width. |
|  | [`->width()`](reference-themes.qmd#width) | The width in pixels (per panel when faceted), or 'container' to fill the parent element. |
|  | [`->height()`](reference-themes.qmd#height) | The height in pixels (per panel when faceted), or 'container' to fill the parent element. |
|  | [`->description()`](reference-themes.qmd#description) | A text description of the chart, which vega-embed uses as its accessible name. |

## [Interaction](reference-interaction.qmd)

| Function | Method | Purpose |
| --- | --- | --- |
| [`tooltip()`](reference-interaction.qmd#tooltip-function) | [`->tooltip()`](reference-interaction.qmd#tooltip) | Tooltips on every layer: true shows the encoded fields, a list shows those fields. |
| [`interactive()`](reference-interaction.qmd#interactive-function) | [`->interactive()`](reference-interaction.qmd#interactive) | Pan and zoom by dragging and scrolling (scales bound to an interval selection). |
| [`brush()`](reference-interaction.qmd#brush-function) | [`->brush()`](reference-interaction.qmd#brush) | A drag-to-select interval named \$name. |
| [`highlight()`](reference-interaction.qmd#highlight-function) | [`->highlight()`](reference-interaction.qmd#highlight) | Click legend entries (or marks, with bind: null) to highlight their group; others fade to \$opacity. |
|  | [`->slider()`](reference-interaction.qmd#slider) | A slider bound to a parameter; use expr(\$name) in geoms or a filter to react to it. |
|  | [`->dropdown()`](reference-interaction.qmd#dropdown) | A dropdown bound to a parameter. |
|  | [`->param()`](reference-interaction.qmd#param) | A Vega-Lite parameter: a value, optionally bound to an input, or an expr. |
|  | [`->select()`](reference-interaction.qmd#select) | A raw selection parameter, attached to the first layer. |

## [Output](reference-output.qmd)

| Function | Method | Purpose |
| --- | --- | --- |
|  | [`->toArray()`](reference-output.qmd#to-array) | The Vega-Lite specification as a PHP array, with \$schema and config. |
|  | [`->toSpec()`](reference-output.qmd#to-spec) | The specification for nesting in a composition: without \$schema or config. |
|  | [`->toJson()`](reference-output.qmd#to-json) | The specification as JSON; pretty adds indentation. |
|  | [`->embed()`](reference-output.qmd#embed) | An HTML fragment that renders the chart: a container div plus, unless \$assets is false, the script tags. |
|  | [`->html()`](reference-output.qmd#html) | A complete HTML page that draws the chart, loading the libraries from the CDN unless assets says otherwise. |
|  | [`->svg()`](reference-output.qmd#svg) | An SVG image rendered by vl-convert. |
|  | [`->png()`](reference-output.qmd#png) | A PNG image rendered by vl-convert; scale multiplies the pixel dimensions. |
|  | [`->pdf()`](reference-output.qmd#pdf) | A PDF document rendered by vl-convert. |
|  | [`->save()`](reference-output.qmd#save) | Writes to a file whose extension picks the format: svg, png, jpg, pdf, html, or json. |
|  | [`->transform()`](reference-output.qmd#transform) | Vega-Lite transforms applied to the data before every layer. |
|  | [`->raw()`](reference-output.qmd#raw) | Raw Vega-Lite merged into the top-level spec last (nested objects merge; lists and scalars replace). |
|  | [`->apply()`](reference-output.qmd#apply) | Applies a function to the plot: pipe-friendly composition of reusable plot fragments. |
|  | [`Html::container()`](reference-output.qmd#html-container) | The container element for a chart. |
|  | [`Html::deferred()`](reference-output.qmd#html-deferred) | A container that loads its spec from a URL when it scrolls into view, for pages with many charts. |
|  | [`Html::embed()`](reference-output.qmd#html-embed) | A self-contained fragment: the container plus script tags (null assets means the pinned CDN copies). |
|  | [`Html::page()`](reference-output.qmd#html-page) | A complete HTML page that draws the chart, or holds body when chart is null; assets defaults to the CDN. |
|  | [`Html::attributes()`](reference-output.qmd#html-attributes) | HTML attributes from a name => value map: true writes a bare attribute, and null or false leaves it out. |
|  | [`Assets::cdn()`](reference-output.qmd#assets-cdn) | The pinned libraries from jsDelivr, with subresource integrity; base replaces the CDN address. |
|  | [`Assets::local()`](reference-output.qmd#assets-local) | Copies served from your own URL prefix: {base}/vega.min.js, {base}/vega-lite.min.js, {base}/vega-embed.min.js. |
|  | [`Assets::urls()`](reference-output.qmd#assets-urls) | Explicit URLs for each library. |
|  | [`Assets::inline()`](reference-output.qmd#assets-inline) | Embeds the three files from a directory containing vega.min.js, vega-lite.min.js and vega-embed.min.js. |
|  | [`Assets::none()`](reference-output.qmd#assets-none) | No library tags: for pages that already load vega-embed (bundled with npm, say). |
|  | [`$assets->scripts()`](reference-output.qmd#assets-scripts) | Script tags for the libraries, followed by the phiz loader. |
|  | [`Assets::loader()`](reference-output.qmd#assets-loader) | The phiz loader script: renders every [data-phiz] element and re-renders when its spec attribute changes. |
|  | [`Export::find()`](reference-output.qmd#export-find) | The vl-convert binary path, or null when none is found. |
|  | [`Export::available()`](reference-output.qmd#export-available) | Whether the vl-convert binary can be found. |
|  | [`$export->svg()`](reference-output.qmd#export-svg) | The chart rendered to SVG. |
|  | [`$export->png()`](reference-output.qmd#export-png) | PNG bytes; scale multiplies the pixel size (2 for high-DPI screens), ppi sets the embedded resolution. |
|  | [`$export->jpeg()`](reference-output.qmd#export-jpeg) | The chart rendered to JPEG; scale multiplies the pixel dimensions, and quality runs from 0 to 100. |
|  | [`$export->pdf()`](reference-output.qmd#export-pdf) | The chart rendered to PDF. |
|  | [`$export->vega()`](reference-output.qmd#export-vega) | The compiled Vega (not Vega-Lite) spec. |
|  | [`$export->save()`](reference-output.qmd#export-save) | Writes the chart to a file whose extension picks the format: svg, png, jpg/jpeg, pdf, html, or json. |

## [Statistics](reference-statistics.qmd)

| Function | Method | Purpose |
| --- | --- | --- |
| [`coefplot()`](reference-statistics.qmd#coefplot-function) |  | Coefficient plot of one fit, or of several keyed by label (dodged side by side). |
| [`residual_plot()`](reference-statistics.qmd#residual_plot) |  | Residuals against fitted values, with a reference line at zero. |
| [`qq_plot()`](reference-statistics.qmd#qq_plot) |  | A normal QQ plot of a fit's residuals, or of a list of values; standardize scales them first. |
| [`fitted_plot()`](reference-statistics.qmd#fitted_plot) |  | Observed against fitted values, with the identity line; observed defaults to fitted plus residuals. |
| [`scale_location_plot()`](reference-statistics.qmd#scale_location_plot) |  | The square root of the absolute residuals divided by sigma, against fitted values, as R's plot(fit, 3). |
| [`diagnostic_plots()`](reference-statistics.qmd#diagnostic_plots) |  | residual_plot(), qq_plot(), scale_location_plot(), and fitted_plot() in a two-by-two grid. |
| [`effect_plot()`](reference-statistics.qmd#effect_plot) |  | Predicted values with confidence bands as x varies (by levels of \$by), other variables held at typical values. |
| [`predictions()`](reference-statistics.qmd#predictions-function) |  | The predicted rows behind effect_plot() for a grid of rows, without plotting them: each row with estimate, lower, upper, and std_error. |
| [`interval_plot()`](reference-statistics.qmd#interval_plot) |  | Posterior intervals from draws columns or a summary, as bayesplot's mcmc_intervals. |
| [`marginal_means()`](reference-statistics.qmd#marginal_means) |  | Estimated marginal means of a factor with confidence intervals, as emmeans. |
| [`random_effects_plot()`](reference-statistics.qmd#random_effects_plot) |  | A caterpillar plot of a mixed model's random effects with conditional intervals, as lme4's dotplot(ranef()). |

