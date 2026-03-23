<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model');
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function generateResponse(string $userQuery, array $contextData = [], array $history = []): string
    {
        if (empty($this->apiKey)) {
            Log::error('OpenAI API key is not configured.');
            return 'OpenAI API key is not configured.';
        }

        $url = 'https://api.openai.com/v1/chat/completions';

        $systemMessage = 'You are a customer support AI agent. Be concise, max 2 sentences. Only answer based on provided customer data. Customer data: ' . json_encode($contextData);

        $messages = [
            ['role' => 'system', 'content' => $systemMessage],
        ];

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'] ?? 'user',
                'content' => $msg['content'] ?? '',
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userQuery];

        try {
            $response = Http::withoutVerifying()
                ->withToken($this->apiKey)
                ->timeout(30)
                ->post($url, [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 150,
                ]);

            Log::info('OpenAI API Status: ' . $response->status());
            Log::info('OpenAI API Response: ' . $response->body());

            if (! $response->successful()) {
                Log::error('OpenAI API Failed: ' . $response->body());
                return 'Sorry, the AI service is temporarily unavailable.';
            }

            $body = $response->json();
            $text = $body['choices'][0]['message']['content'] ?? '';

            return trim($text) ?: 'I could not generate a response for that query.';
        } catch (\Throwable $e) {
            report($e);
            return 'Sorry, an error occurred while processing your request.';
        }
    }

    public function generateWithToolCalling(string $userQuery, int $customerId, OpenAIToolExecutor $executor): string
    {
        return 'This is a placeholder response from OpenAIService with tool calling.';
    }
}
