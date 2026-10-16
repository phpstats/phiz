<?php

declare(strict_types=1);

namespace Phit\Laravel;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Phit\Assets;

/**
 * Registers <x-phit :plot="$plot" /> and @phitScripts. Auto-discovered by Laravel; phit works without it.
 */
final class PhitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/phit.php', 'phit');
        $this->app->scoped(ScriptState::class);
    }

    public function boot(): void
    {
        $this->publishes([dirname(__DIR__, 2).'/config/phit.php' => $this->app->configPath('phit.php')], 'phit-config');
        Blade::component('phit', PhitComponent::class);
        \Phit\Config::$serverRows = (int) config('phit.server_rows', \Phit\Config::$serverRows);
        $warn = config('phit.warn_rows', \Phit\Config::$warnRows);
        \Phit\Config::$warnRows = $warn === null ? null : (int) $warn;
        \Phit\Config::$warn ??= static fn (string $message) => \Illuminate\Support\Facades\Log::warning($message);
        Blade::directive('phitScripts', fn () => '<?php echo \\'.self::class.'::scripts(); ?>');
    }

    /** The script tags for the configured assets, marking them as included for this request. */
    public static function scripts(): string
    {
        app(ScriptState::class)->included = true;

        return self::assets()->scripts();
    }

    public static function assets(): Assets
    {
        $setting = (string) config('phit.assets', 'cdn');

        return match (true) {
            $setting === 'cdn' => Assets::cdn(),
            $setting === 'none' => Assets::none(),
            default => Assets::local($setting),
        };
    }
}
