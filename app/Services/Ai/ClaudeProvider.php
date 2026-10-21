<?php

namespace App\Services\Ai;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;
use Anthropic\RequestOptions;

/**
 * Penyedia AI menggunakan Claude API (Anthropic). Data kes keluar dari rangkaian MBSP,
 * jadi hanya aktifkan selepas kelulusan ICT / Keselamatan Maklumat.
 */
class ClaudeProvider implements AiProvider
{
    public function nama(): string
    {
        return 'claude';
    }

    public function model(): ?string
    {
        return config('integriti.ai.claude.model');
    }

    public function jana(string $sistem, string $prompt, array $konteks = []): AiRespons
    {
        $cfg = config('integriti.ai.claude');
        if (empty($cfg['api_key'])) {
            throw new AiGagal('ANTHROPIC_API_KEY belum ditetapkan dalam fail .env.');
        }

        $client = new Client(apiKey: $cfg['api_key']);

        try {
            $mesej = $client->beta->messages->create(
                model: $cfg['model'],
                maxTokens: $cfg['max_tokens'],
                system: $sistem,
                messages: [['role' => 'user', 'content' => $prompt]],
                thinking: ['type' => 'adaptive'],
                outputConfig: ['effort' => $cfg['effort']],
                // Jika model menolak atas sebab polisi, API cuba semula pada model ganti lalai.
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
                requestOptions: RequestOptions::with(timeout: (float) config('integriti.ai.timeout')),
            );
        } catch (AuthenticationException) {
            throw new AiGagal('Kunci API Claude tidak sah.');
        } catch (RateLimitException) {
            throw new AiGagal('Had penggunaan API Claude dicapai. Sila cuba semula sebentar lagi.');
        } catch (APIStatusException $e) {
            throw new AiGagal('Ralat API Claude: '.$e->getMessage());
        } catch (APIConnectionException $e) {
            throw new AiGagal('Tidak dapat menghubungi API Claude: '.$e->getMessage());
        }

        if ($mesej->stopReason === 'refusal') {
            throw new AiGagal('AI menolak untuk menjawab permintaan ini. Sila semak kes secara manual.');
        }

        $teks = '';
        foreach ($mesej->content as $blok) {
            if ($blok->type === 'text') {
                $teks .= $blok->text;
            }
        }

        if (trim($teks) === '') {
            throw new AiGagal('AI tidak memulangkan sebarang teks.');
        }

        return new AiRespons($teks, [
            'stop_reason' => $mesej->stopReason,
            'input_tokens' => $mesej->usage->inputTokens,
            'output_tokens' => $mesej->usage->outputTokens,
        ], $mesej->model);
    }
}
