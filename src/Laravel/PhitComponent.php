<?php

declare(strict_types=1);

namespace Phit\Laravel;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\Component;
use Phit\Chart;
use Phit\Html;

/**
 * <x-phit :plot="$plot" class="h-80" :options="['theme' => 'auto']" />
 *
 * Renders a Plot (or any Chart, spec array, or spec JSON). Inside Livewire components it re-renders whenever the
 * component does: the spec rides on an attribute Livewire morphs, and the phit loader re-embeds on change while
 * the drawn chart sits in a wire:ignore child. Give each chart a wire:key when a component renders several.
 */
final class PhitComponent extends Component
{
    /**
     * @param Chart|array<string, mixed>|string $plot
     * @param array<string, mixed> $options vega-embed options, merged over config('phit.embed_options').
     */
    public function __construct(
        public Chart|array|string $plot,
        public array $options = [],
    ) {}

    /** Lazy, because Blade sets the attributes after render() and before the HTML is taken. */
    public function render(): Htmlable
    {
        return new class(fn () => $this->html()) implements Htmlable
        {
            public function __construct(private readonly \Closure $html) {}

            public function toHtml(): string
            {
                return ($this->html)();
            }
        };
    }

    private function html(): string
    {
        $spec = match (true) {
            is_string($this->plot) => json_decode($this->plot, true, 512, JSON_THROW_ON_ERROR),
            default => $this->plot,
        };
        $options = array_replace((array) config('phit.embed_options', []), $this->options);
        $attributes = $this->attributes->getAttributes();
        // Blade passes :class and :style as arrays; render them as Blade's own components do.
        if (isset($attributes['class']) && is_array($attributes['class'])) {
            $attributes['class'] = \Illuminate\Support\Arr::toCssClasses($attributes['class']);
        }
        if (isset($attributes['style']) && is_array($attributes['style'])) {
            $attributes['style'] = \Illuminate\Support\Arr::toCssStyles($attributes['style']);
        }
        $html = Html::container($spec, $options, $attributes);

        $state = app(ScriptState::class);
        if (config('phit.inject_scripts', true) && ! $state->included && ! request()->hasHeader('X-Livewire')) {
            $html .= "\n".PhitServiceProvider::scripts();
        }

        return $html;
    }
}
