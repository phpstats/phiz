<?php

declare(strict_types=1);

namespace Phiz\Laravel;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Phiz\Assets;

/**
 * Registers <x-phiz :plot="$plot" /> and @phizScripts. Auto-discovered by Laravel; phiz works without it.
 */
final class PhizServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/phiz.php', 'phiz');
        $this->app->scoped(ScriptState::class);
    }

    public function boot(): void
    {
        $this->publishes([dirname(__DIR__, 2).'/config/phiz.php' => $this->app->configPath('phiz.php')], 'phiz-config');
        Blade::component('phiz', PhizComponent::class);
        \Phiz\Config::$serverRows = (int) config('phiz.server_rows', \Phiz\Config::$serverRows);
        $warn = config('phiz.warn_rows', \Phiz\Config::$warnRows);
        \Phiz\Config::$warnRows = $warn === null ? null : (int) $warn;
        Blade::directive('phizScripts', fn () => '<?php echo \\'.self::class.'::scripts(); ?>');
    }

    /** The script tags for the configured assets, marking them as included for this request. */
    public static function scripts(): string
    {
        app(ScriptState::class)->included = true;

        return self::assets()->scripts();
    }

    public static function assets(): Assets
    {
        $setting = (string) config('phiz.assets', 'cdn');

        return match (true) {
            $setting === 'cdn' => Assets::cdn(),
            $setting === 'none' => Assets::none(),
            default => Assets::local($setting),
        };
    }
}
