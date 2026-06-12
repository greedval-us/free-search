<?php

return [
    'required' => 'The :attribute field is required.',
    'accepted' => 'The :attribute must be accepted.',
    'string' => 'The :attribute must be a string.',
    'email' => 'The :attribute must be a valid email address.',
    'max' => [
        'string' => 'The :attribute may not be greater than :max characters.',
    ],
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'unique' => 'The :attribute has already been taken.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'regex' => 'The :attribute format is invalid.',
    'lowercase' => 'The :attribute must contain at least one lowercase letter.',
    'uppercase' => 'The :attribute must contain at least one uppercase letter.',
    'letters' => 'The :attribute must contain at least one letter.',
    'mixed' => 'The :attribute must contain at least one uppercase and one lowercase letter.',
    'numbers' => 'The :attribute must contain at least one number.',
    'symbols' => 'The :attribute must contain at least one symbol.',
    'password' => [
        'letters' => 'The :attribute must contain at least one letter.',
        'mixed' => 'The :attribute must contain at least one uppercase and one lowercase letter.',
        'numbers' => 'The :attribute must contain at least one number.',
        'symbols' => 'The :attribute must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],

    'attributes' => [
        'name' => 'name',
        'email' => 'email',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'accept_service_rules' => 'service rules',
        'activation_token' => 'activation token',
    ],

    'custom' => [
        'activation_token' => [
            'invalid' => 'The activation token is invalid.',
            'used' => 'This activation token has already been used.',
            'expired' => 'This activation token has expired.',
        ],
    ],
];
