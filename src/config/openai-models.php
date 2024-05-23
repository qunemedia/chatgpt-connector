<?php

$aOpenAiModels = array(
    'gpt-3.5-turbo-instruct' => [
        'name' => 'GPT-3.5 Turbo',
        'model' => 'gpt-3.5-turbo-instruct',
        'default' => ['text', 'translation'],
        'legacy' => false,
        'api' => [
            'url' => 'https://api.openai.com/v1/completions',
            'image_url' => 'https://api.openai.com/v1/images/generations'
        ],
        'description' => 'GPT-3.5 Turbo, optimized for performance and cost, suitable for many general tasks. Returns a maximum of 4,096 output tokens.',
        'max_tokens' => 4096,
        'training_data' => '2022-12-01',
    ],
    'dalle-2' => [
        'name' => 'DALL-E 2',
        'model' => 'dalle-2',
        'default' => ['image'],
        'legacy' => false,
        'api' => [
            'url' => 'https://api.openai.com/v1/images/generations',
            'image_url' => 'https://api.openai.com/v1/images/generations'
        ],
        'description' => 'DALL-E 2, the most advanced image generation model, capable of creating highly detailed and diverse images from textual descriptions.',
        'max_tokens' => null,  // Not applicable for image models
        'training_data' => '2022-01-01',
    ],
);