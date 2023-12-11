<?php

/**
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 * (c) Víctor Pàmies i Riudor <vpamies@gmail.com>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Get tags from sentence, using OpenAI API.
 *
 * @return list<string>
 *
 * @throws JsonException
 * @throws GuzzleException
 *
 * @suppress PhanUndeclaredClassMethod, PhanTypeInvalidThrowsIsInterface
 */
function getSentenceTags(string $sentence): array
{
    $client = new Client();
    $data = [
        'model' => 'gpt-3.5-turbo-1106',
        'temperature' => 0.3,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Your task is to generate tags for Catalan idioms and proverbs in JSON. Tags should be in Catalan, lowercase, and include nouns, places, names, topics, and words related to the meaning of the sentence.',
            ],
            [
                'role' => 'user',
                'content' => "Generate tags for: \"{$sentence}\"",
            ],
        ],
    ];

    $api_key = getenv('OPENAI_KEY');
    $response = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$api_key}",
        ],
        'json' => $data,
    ]);

    $body = (string) $response->getBody();
    $body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    assert(is_array($body));

    /** @var array{choices: array{0: array{message: array{content: string}}}} $body */
    $tagsContent = $body['choices'][0]['message']['content'];

    $tagsJson = json_decode($tagsContent, true, 512, JSON_THROW_ON_ERROR);

    /** @var array{tags: list<string>}|false $tagsJson */
    return $tagsJson['tags'] ?? [];
}

/**
 * Appends data to a JSON file.
 *
 * @param array{sentence: string, tags: list<string>} $data
 *
 * @throws JsonException
 */
function appendToJsonFile(array $data, string $filePath): void
{
    if (!file_exists($filePath)) {
        file_put_contents($filePath, json_encode([], JSON_THROW_ON_ERROR));
    }

    $content = file_get_contents($filePath);
    assert(is_string($content));
    $currentData = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    assert(is_array($currentData));
    $currentData[] = $data;

    file_put_contents($filePath, json_encode($currentData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
