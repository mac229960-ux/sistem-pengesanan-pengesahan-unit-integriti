<?php

namespace App\Services\Ai;

final class AiRespons
{
    public function __construct(
        public readonly string $teks,
        public readonly array $metadata = [],
        public readonly ?string $model = null,
    ) {}
}
