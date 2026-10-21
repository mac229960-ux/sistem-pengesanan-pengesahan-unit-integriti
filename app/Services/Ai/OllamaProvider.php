<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Model AI tempatan melalui Ollama (dipasang di server MBSP). Data kekal dalam rangkaian.
 */
class OllamaProvider implements AiProvider
{
    public function nama(): string
    {
        return 'ollama';
    }

    public function model(): ?string
    {
        return config('integriti.ai.ollama.model');
    }

    public function jana(string $sistem, string $prompt, array $konteks = []): AiRespons
    {
        try {
            $res = Http::baseUrl(config('integriti.ai.ollama.url'))
                ->timeout(config('integriti.ai.timeout'))
                ->post('/api/chat', [
                    'model' => $this->model(),
                    'stream' => false,
                    'messages' => [
                        ['role' => 'system', 'content' => $sistem],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new AiGagal('Tidak dapat menghubungi pelayan Ollama: '.$e->getMessage());
        }

        if ($res->failed()) {
            throw new AiGagal('Ralat Ollama ('.$res->status().'): '.$res->body());
        }

        $teks = (string) $res->json('message.content');
        if (trim($teks) === '') {
            throw new AiGagal('Ollama tidak memulangkan sebarang teks.');
        }

        return new AiRespons($teks, [
            'input_tokens' => $res->json('prompt_eval_count'),
            'output_tokens' => $res->json('eval_count'),
        ], $this->model());
    }
}
