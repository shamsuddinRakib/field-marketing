<?php

namespace App\Ai\Tools;

use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class WebSearchTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'This is the tool to use when i ask you anything recent that happend after your training cut off date';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = trim((string) $request['query']);

        if ($query === '') {
            return 'No query provided.';
        }

        try {
            $response = Http::withToken(env('OLLAMA_API_KEY')
            )
                ->acceptJson()
                ->timeout(20)
                ->post('https://ollama.com/api/web_search', [
                    'query' => $query,
                    'max_results' => 5,
                ]);
            // dd($response);
            $response->throw();

            $results = collect($response->json('results', []))
                ->pluck('content')
                ->implode("\n\n");

            return (string) $results;
        } catch (Exception $e) {
            report($e);

            return 'Web search is temporarily unavailable.';

        }
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->required(),
        ];
    }
}
