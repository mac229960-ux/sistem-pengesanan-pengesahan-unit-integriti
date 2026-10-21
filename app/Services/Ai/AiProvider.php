<?php

namespace App\Services\Ai;

interface AiProvider
{
    public function nama(): string;

    public function model(): ?string;

    /**
     * @param  array  $konteks  Data berstruktur yang sama digunakan untuk membina prompt
     *                          (digunakan oleh MockProvider untuk menjana jawapan demo).
     */
    public function jana(string $sistem, string $prompt, array $konteks = []): AiRespons;
}
