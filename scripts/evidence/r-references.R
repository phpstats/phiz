# Reference values for scripts/evidence-parity.php, computed with R and the packages phiz follows. Writes
# tests/fixtures/r-references.json with the R and package versions.
#
#     Rscript scripts/evidence/r-references.R

suppressPackageStartupMessages({
  library(ggplot2)
  library(MASS)
  library(lme4)
  library(jsonlite)
})

ref <- list()

# Normal and t quantiles, and the normal CDF.
ref$quantiles <- list(
  qnorm = list(p = c(0.975, 0.001, 0.3), value = qnorm(c(0.975, 0.001, 0.3))),
  qt = list(p = c(0.975, 0.975, 0.995, 0.4), df = c(10, 1, 3.5, 5), value = qt(c(0.975, 0.975, 0.995, 0.4), c(10, 1, 3.5, 5))),
  pnorm = list(q = 1.959963984540054, value = pnorm(1.959963984540054))
)

# Type 7 sample quantiles and the bw.nrd0 bandwidth of mtcars$wt.
ref$sample_quantiles <- list(
  p = c(0.05, 0.25, 0.9),
  value = unname(quantile(mtcars$wt, c(0.05, 0.25, 0.9), type = 7)),
  bw_nrd0 = bw.nrd0(mtcars$wt)
)

# lm(mpg ~ wt) on mtcars, and its 90% confidence band at the ends of wt.
fit <- lm(mpg ~ wt, mtcars)
band <- predict(fit, data.frame(wt = range(mtcars$wt)), interval = "confidence", level = 0.9)
ref$lm <- list(
  coefficients = unname(coef(fit)),
  std_errors = unname(summary(fit)$coefficients[, 2]),
  df_residual = fit$df.residual,
  band = list(x = range(mtcars$wt), fit = unname(band[, 1]), lower = unname(band[, 2]), upper = unname(band[, 3]))
)

# loess(hwy ~ displ) on mpg with direct surfaces and exact statistics, at five points, and its 95% band at the ends.
at <- c(1.6, 2.5, 3.3, 4.7, 7)
loess_ref <- function(span, degree) {
  m <- loess(hwy ~ displ, mpg, span = span, degree = degree, control = loess.control(surface = "direct", statistics = "exact"))
  p <- predict(m, data.frame(displ = at), se = TRUE)
  ends <- predict(m, data.frame(displ = range(mpg$displ)), se = TRUE)
  t <- qt(0.975, ends$df)
  list(span = span, degree = degree, at = at, fit = unname(p$fit), se = unname(p$se.fit), df = p$df, sigma = p$residual.scale,
       band = list(x = range(mpg$displ), lower = unname(ends$fit - t * ends$se.fit), upper = unname(ends$fit + t * ends$se.fit)))
}
ref$loess <- list(loess_ref(0.75, 2), loess_ref(1.5, 1))

# MASS::kde2d on faithful with bandwidth.nrd bandwidths on a 100 x 100 grid; z[i, j] is at x[i], y[j].
k <- kde2d(faithful$eruptions, faithful$waiting, n = 100)
ref$kde2d <- list(x = k$x, y = k$y, z = unname(k$z))

# ggplot2's density contours on faithful: the default levels, and the pieces at breaks = 0.002.
levels <- sort(unique(layer_data(ggplot(faithful, aes(eruptions, waiting)) + geom_density_2d())$level))
pieces <- layer_data(ggplot(faithful, aes(eruptions, waiting)) + geom_density_2d(breaks = 0.002))
ref$contours <- list(levels = levels, piece_sizes = sort(unname(as.vector(table(pieces$piece)))))

# stat_ellipse on faithful, type t and norm: the polygon points.
ellipse <- function(type) {
  d <- layer_data(ggplot(faithful, aes(eruptions, waiting)) + stat_ellipse(type = type))
  list(x = d$x, y = d$y)
}
ref$ellipse <- list(t = ellipse("t"), norm = ellipse("norm"))

# stat_ellipse(type = "t") on two more datasets, for the multivariate t fit.
ellipse_on <- function(data, x, y) {
  d <- layer_data(ggplot(data, aes(.data[[x]], .data[[y]])) + stat_ellipse(type = "t"))
  list(x = d$x, y = d$y)
}
ref$ellipse_more <- list(mpg = ellipse_on(mpg, "displ", "hwy"), mtcars = ellipse_on(mtcars, "wt", "mpg"))

# pretty() on 1,500 seeded random ranges and some edge cases.
set.seed(20261102)
ref$pretty <- lapply(1:1500, function(i) {
  size <- 10^runif(1, -9, 9)
  lo <- (runif(1) - 0.5) * size * sample(c(0.1, 1, 10), 1)
  hi <- lo + abs(rnorm(1)) * size * 10^runif(1, -10, 1)
  n <- sample(1:25, 1)
  list(lo = lo, hi = hi, n = n, out = pretty(c(lo, hi), n = n))
})
for (e in list(c(0, 0), c(5, 5), c(-2, -2), c(1, 15), c(43, 96), c(0.000123, 0.0371)))
  for (n in c(5, 10)) ref$pretty[[length(ref$pretty) + 1]] <- list(lo = e[1], hi = e[2], n = n, out = pretty(e, n = n))

# stat_summary(fun.data = mean_cl_normal) on mpg hwy by class: the mean and t interval, as t.test() computes them.
classes <- sort(unique(mpg$class))
ref$summary <- lapply(classes, function(cl) {
  v <- mpg$hwy[mpg$class == cl]
  ci <- t.test(v)$conf.int
  list(class = cl, mean = mean(v), lower = ci[1], upper = ci[2])
})

# The density of faithful$eruptions at five points, relative to its largest value.
d <- density(faithful$eruptions, n = 5, from = 1.6, to = 5.1)
ref$density <- list(x = d$x, relative = d$y / max(d$y))

# scales' hue and grey palettes.
ref$palettes <- list(
  hue3 = toupper(scales::hue_pal()(3)), hue5 = toupper(scales::hue_pal()(5)), hue8 = toupper(scales::hue_pal()(8)),
  grey4 = toupper(scales::grey_pal()(4))
)

# predict(lm(mpg ~ wt + hp + factor(cyl))) with 95% confidence intervals on a grid: wt at its ends and middle, hp at its
# mean, and each cylinder count.
fit <- lm(mpg ~ wt + hp + factor(cyl), mtcars)
grid <- expand.grid(wt = c(min(mtcars$wt), mean(range(mtcars$wt)), max(mtcars$wt)), cyl = c(4, 6, 8))
grid$hp <- mean(mtcars$hp)
p <- predict(fit, grid, interval = "confidence")
ref$predictions <- list(grid = grid, fit = unname(p[, 1]), lower = unname(p[, 2]), upper = unname(p[, 3]))

# Marginal means of cyl from lm(mpg ~ factor(cyl) + factor(am) + wt): the design averaged with equal weight over am,
# wt at its mean, and a t interval from the averaged row, as emmeans computes them.
fit <- lm(mpg ~ factor(cyl) + factor(am) + wt, mtcars)
means <- lapply(c(4, 6, 8), function(cyl) {
  rows <- data.frame(cyl = cyl, am = c(0, 1), wt = mean(mtcars$wt))
  X <- model.matrix(~ factor(cyl, levels = c(4, 6, 8)) + factor(am, levels = c(0, 1)) + wt, rows)
  l <- colMeans(X)
  est <- sum(l * coef(fit))
  se <- sqrt(drop(t(l) %*% vcov(fit) %*% l))
  t <- qt(0.975, fit$df.residual)
  list(cyl = cyl, estimate = est, lower = est - t * se, upper = est + t * se)
})
ref$marginal_means <- means

# lme4's conditional modes and standard deviations of the random intercepts of lmer(hwy ~ displ + (1 | manufacturer)).
m <- lmer(hwy ~ displ + (1 | manufacturer), mpg)
re <- ranef(m, condVar = TRUE)$manufacturer
ref$random_effects <- list(group = rownames(re), mode = unname(re[, 1]), sd = unname(sqrt(attr(re, "postVar")[1, 1, ])))

versions <- list(
  R = paste(R.version$major, R.version$minor, sep = "."),
  ggplot2 = as.character(packageVersion("ggplot2")),
  MASS = as.character(packageVersion("MASS")),
  lme4 = as.character(packageVersion("lme4")),
  scales = as.character(packageVersion("scales"))
)
out <- list(versions = versions, date = format(Sys.Date()), references = ref)
writeLines(toJSON(out, auto_unbox = TRUE, digits = I(17), pretty = FALSE), "tests/fixtures/r-references.json")
cat("Wrote tests/fixtures/r-references.json\n")
