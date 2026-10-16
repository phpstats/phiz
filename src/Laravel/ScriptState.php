<?php

declare(strict_types=1);

namespace Phit\Laravel;

/** @internal Whether this request's page already has the phit script tags. */
final class ScriptState
{
    public bool $included = false;
}
