<?php

$aOpenAiModels = array(
    'gpt-4' => [
        'name' => 'GPT-4',
        'model' => 'gpt-4',
        'legacy' => false,
        'api' => [
            'url' => 'https://api.openai.com/v1/completions',
            'image_url' => 'https://api.openai.com/v1/images/generations'
        ],
        'description' => 'GPT-4, the most advanced model with improved performance and understanding, supporting a wide range of tasks. Returns a maximum of 8,192 output tokens.',
        'max_tokens' => 8192,
        'training_data' => '2023-03-01',
    ],
    'gpt-3.5-turbo' => [
        'name' => 'GPT-3.5 Turbo',
        'model' => 'gpt-3.5-turbo',
        'legacy' => false,
        'api' => [
            'url' => 'https://api.openai.com/v1/completions',
            'image_url' => 'https://api.openai.com/v1/images/generations'
        ],
        'description' => 'GPT-3.5 Turbo, optimized for performance and cost, suitable for many general tasks. Returns a maximum of 4,096 output tokens.',
        'max_tokens' => 4096,
        'training_data' => '2022-12-01',
    ],
    'gpt-3.5-turbo-0301' => [
        'name' => 'GPT-3.5 Turbo (0301)',
        'model' => 'gpt-3.5-turbo-0301',
        'legacy' => true,
        'api' => [
            'url' => 'https://api.openai.com/v1/completions',
            'image_url' => 'https://api.openai.com/v1/images/generations'
        ],
        'description' => 'A previous version of GPT-3.5 Turbo, optimized for performance and cost, suitable for many general tasks. Returns a maximum of 4,096 output tokens.',
        'max_tokens' => 4096,
        'training_data' => '2021-09-01',
    ],
    'gpt-3.5-turbo-1107' => [
        'name' => 'GPT-3.5 Turbo (1107)',
        'model' => 'gpt-3.5-turbo-1107',
        'legacy' => true,
        'api' => [
            'url' => 'https://api.openai.com/v1/completions',
            'image_url' => 'https://api.openai.com/v1/images/generations'
        ],
        'description' => 'Another previous version of GPT-3.5 Turbo with similar capabilities. Returns a maximum of 4,096 output tokens.',
        'max_tokens' => 4096,
        'training_data' => '2021-09-01',
    ],
    'gpt-3' => [
        'name' => 'GPT-3',
        'model' => 'gpt-3',
        'legacy' => true,
        'api' => [
            'url' => 'https://api.openai.com/v1/completions',
            'image_url' => 'https://api.openai.com/v1/images/generations'
        ],
        'description' => 'The original GPT-3 model, well-suited for a wide variety of tasks with high-quality outputs. Returns a maximum of 4,096 output tokens.',
        'max_tokens' => 4096,
        'training_data' => '2020-06-01',
    ],
);