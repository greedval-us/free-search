<?php

return [
    'required' => 'Поле :attribute обязательно для заполнения.',
    'accepted' => 'Необходимо принять :attribute.',
    'string' => 'Поле :attribute должно быть строкой.',
    'email' => 'Поле :attribute должно быть корректным email-адресом.',
    'max' => [
        'string' => 'Поле :attribute не должно быть длиннее :max символов.',
    ],
    'min' => [
        'string' => 'Поле :attribute должно содержать минимум :min символов.',
    ],
    'unique' => 'Такое значение поля :attribute уже используется.',
    'confirmed' => 'Подтверждение поля :attribute не совпадает.',
    'regex' => 'Поле :attribute имеет неверный формат.',
    'lowercase' => 'Поле :attribute должно содержать хотя бы одну строчную букву.',
    'uppercase' => 'Поле :attribute должно содержать хотя бы одну заглавную букву.',
    'letters' => 'Поле :attribute должно содержать хотя бы одну букву.',
    'mixed' => 'Поле :attribute должно содержать строчные и заглавные буквы.',
    'numbers' => 'Поле :attribute должно содержать хотя бы одну цифру.',
    'symbols' => 'Поле :attribute должно содержать хотя бы один спецсимвол.',
    'password' => [
        'letters' => 'Поле :attribute должно содержать хотя бы одну букву.',
        'mixed' => 'Поле :attribute должно содержать строчные и заглавные буквы.',
        'numbers' => 'Поле :attribute должно содержать хотя бы одну цифру.',
        'symbols' => 'Поле :attribute должно содержать хотя бы один спецсимвол.',
        'uncompromised' => 'Этот :attribute найден в утечках данных. Укажите другой :attribute.',
    ],

    'attributes' => [
        'name' => 'имя',
        'email' => 'email',
        'password' => 'пароль',
        'password_confirmation' => 'подтверждение пароля',
        'accept_service_rules' => 'правила сервиса',
        'activation_token' => 'токен активации',
    ],

    'custom' => [
        'activation_token' => [
            'invalid' => 'Указан неверный токен активации.',
            'used' => 'Этот токен уже был использован.',
            'expired' => 'Срок действия этого токена уже истёк.',
        ],
    ],
];
