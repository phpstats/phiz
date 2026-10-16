<?php

declare(strict_types=1);

namespace Phiz\Laravel;

/** @internal Whether this request's page already has the phiz script tags. */
final class ScriptState
{
    public bool $included = false;
}
