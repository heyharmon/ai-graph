<?php

namespace App\Services;

use OpenAI;
use OpenAI\Client;

class OpenAIService
{
    private Client $client;
    private string $defaultModel = 'gpt-4o';
    private int $defaultMaxTokens = 2000;
    private float $defaultTemperature = 0.7;

    public function __construct()
    {
        $apiKey = config('services.openai.key');

        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key is not configured. Please set OPENAI_KEY in your .env file.');
        }

        $this->client = OpenAI::client($apiKey);
    }

    /**
     * Generate a response using OpenAI's Responses API
     *
     * @param array $input Array of message objects with 'role' and 'content'
     * @param array $options Optional configuration:
     *   - model: string (default: 'gpt-4o')
     *   - max_tokens: int (default: 2000)
     *   - temperature: float (default: 0.7)
     *   - tools: array of tool definitions
     *   - tool_choice: string|array for tool selection
     *   - response_format: array for structured outputs
     * @return string The generated text content
     */
    public function generate(array $input, array $options = []): string
    {
        $params = [
            'model' => $options['model'] ?? $this->defaultModel,
            'input' => $input,
            'max_output_tokens' => $options['max_tokens'] ?? $this->defaultMaxTokens,
            'temperature' => $options['temperature'] ?? $this->defaultTemperature,
        ];

        if (isset($options['tools'])) {
            $params['tools'] = $options['tools'];
        }

        if (isset($options['tool_choice'])) {
            $params['tool_choice'] = $options['tool_choice'];
        }

        // Add text.format if provided (for structured outputs in Responses API)
        if (isset($options['text_format'])) {
            $params['text'] = [
                'format' => $options['text_format'],
            ];
        }

        try {
            $response = $this->client->responses()->create($params);

            return $this->extractContent($response, isset($options['text_format']));
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate OpenAI response: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate a response with system instructions and user prompt
     *
     * @param string $systemInstructions System message/instructions
     * @param string $userPrompt User prompt
     * @param array $options Optional configuration
     * @return string The generated text content
     */
    public function generateWithInstructions(string $systemInstructions, string $userPrompt, array $options = []): string
    {
        $input = [
            ['role' => 'system', 'content' => $systemInstructions],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        return $this->generate($input, $options);
    }

    /**
     * Generate structured output
     *
     * @param array $input Array of message objects
     * @param array $jsonSchema JSON schema for structured output
     * @param array $options Optional configuration
     * @return array Parsed JSON response
     */
    public function generateStructured(array $input, array $jsonSchema, array $options = []): array
    {
        // Use a model that supports structured outputs
        $options['model'] = $options['model'] ?? 'gpt-4o';

        // Use text.format for Responses API structured outputs
        // Structure: text.format.type, text.format.name, text.format.schema (all at same level)
        $options['text_format'] = [
            'type' => 'json_schema',
            'name' => 'structured_output',
            'schema' => $jsonSchema,
            'strict' => true,
        ];

        $jsonString = $this->generate($input, $options);

        $decoded = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to decode structured output JSON: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Extract text content from OpenAI response
     *
     * @param mixed $response OpenAI response object
     * @param bool $isStructuredOutput Whether this is a structured output response
     * @return string Extracted text content (or JSON string for structured outputs)
     */
    private function extractContent($response, bool $isStructuredOutput = false): string
    {
        // First, try the output_text/outputText property if it exists (simplest path)
        if (isset($response->outputText)) {
            return $response->outputText;
        }

        // Try snake_case version
        if (isset($response->output_text)) {
            return $response->output_text;
        }

        // Fallback: check output array for message items with content
        if (!isset($response->output) || !is_array($response->output) || count($response->output) === 0) {
            return '';
        }

        // Iterate through all output items to find messages with content
        foreach ($response->output as $outputItem) {
            // Skip tool calls - they don't have content
            if (isset($outputItem->type) && str_contains($outputItem->type, 'tool_call')) {
                continue;
            }

            // Check if this output item has content
            if (isset($outputItem->content) && is_array($outputItem->content)) {
                $textParts = [];
                foreach ($outputItem->content as $contentItem) {
                    if (isset($contentItem->text)) {
                        $textParts[] = $contentItem->text;
                    }
                }

                if (!empty($textParts)) {
                    return implode('', $textParts);
                }
            }
        }

        return '';
    }
}
