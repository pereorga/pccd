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

use GuzzleHttp\Client;

/**
 * Get tags from sentence, using OpenAI API.
 *
 * @return list<string>
 *
 * @throws JsonException
 */
function getSentenceTags(string $sentence): array
{
    // TODO: - Implement request rate limiting (e.g., X max requests per minute).
    //       - Ensure the function is idempotent (it can resume safely after an interruption).
    //       - Use OpenAI's batch API for efficiency and cost optimization, if applicable.
    //       - Validate the structure and format of the API response more robustly.
    //       - Add logging for API errors and unexpected responses.
    //       - Consider using retry logic for transient API failures (e.g., network issues).
    $client = new Client();
    $data = [
        'messages' => [
            [
                'content' => "Your task is to generate consistent tags for Catalan proverbs. Tags must always:\n"
                    . "- Be in Catalan.\n"
                    . "- Include nouns, places, names, topics, and related words to its meaning.\n"
                    . "- Be lowercase, singular, and relevant to the sentence.\n"
                    . "- Provide 2–10 tags as a JavaScript array.\n"
                    . "- Never include generic tags such as \"humor\", \"lloc\", \"poble\", \"tradició\", \"cultura\" or \"costum\".\n"
                    . "Example:\n"
                    . "Sentence: \"A Abrera, donen garses per perdius.\"\n"
                    . '["abrera", "perdiu", "garsa", "engany", "valor"]',
                'role' => 'system',
            ],
            [
                'content' => "Generate tags for: \"{$sentence}\"",
                'role' => 'user',
            ],
        ],
        'model' => 'gpt-4o-mini',
        'temperature' => 0.1,
    ];

    $api_key = getenv('OPENAI_KEY');
    $response = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => "Bearer {$api_key}",
            'Content-Type' => 'application/json',
        ],
        'json' => $data,
    ]);

    $body = (string) $response->getBody();
    $body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    assert(is_array($body));

    /** @var array{choices: array{0: array{message: array{content: string}}}} $body */
    $tagsContent = $body['choices'][0]['message']['content'];
    $tagsJson = json_decode($tagsContent, true, 512, JSON_THROW_ON_ERROR);

    assert(is_array($tagsJson) && array_is_list($tagsJson));

    return $tagsJson;
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
