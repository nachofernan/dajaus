<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIMatcherService
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.ai.api_key');
        $this->baseUrl = config('services.ai.base_url');
        $this->model = config('services.ai.model', 'llama-3.1-8b-instant');
    }

    /**
     * Match a user-provided item name against an existing collection.
     * Returns ['action' => 'match', 'id' => X, 'name' => '...']
     *      or ['action' => 'create', 'name' => '...']
     */
    public function match(string $newItem, Collection $existingItems): array
    {
        if ($existingItems->isEmpty()) {
            return ['action' => 'create', 'name' => $newItem];
        }

        $itemList = $existingItems->map(fn($i) => "ID {$i->id}: {$i->name}")->join("\n");

        $prompt = <<<PROMPT
Sos un asistente que gestiona una lista de compras. El usuario quiere agregar o marcar como comprado: "{$newItem}"

Lista actual:
{$itemList}

Determiná si "{$newItem}" corresponde a algún item existente (mismo producto aunque esté escrito diferente, con o sin tilde, abreviado, etc.).

Respondé ÚNICAMENTE con JSON válido, sin texto adicional, sin markdown:
- Si existe: {"action":"match","id":ID_DEL_ITEM,"name":"NOMBRE_EXACTO"}
- Si no existe: {"action":"create","name":"NOMBRE_NORMALIZADO"}

El nombre normalizado debe estar en minúsculas y correctamente escrito en español.
PROMPT;

        try {
            $response = Http::withToken($this->apiKey)
                ->post($this->baseUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0,
                    'response_format' => ['type' => 'json_object'],
                ]);

            $text = $response->json('choices.0.message.content') ?? '';
            $result = json_decode(trim($text), true);

            if (isset($result['action']) && in_array($result['action'], ['match', 'create'])) {
                return $result;
            }
        } catch (\Throwable $e) {
            Log::error('AIMatcherService error', ['error' => $e->getMessage()]);
        }

        return ['action' => 'create', 'name' => mb_strtolower($newItem)];
    }
}
