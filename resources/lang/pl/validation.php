<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Walidacja — komunikaty po polsku (uzupełnienie + fallback na pl/en w configu)
    |--------------------------------------------------------------------------
    */

    'accepted' => 'Pole :attribute musi zostać zaakceptowane.',

    'confirmed' => 'Potwierdzenie pola :attribute jest niezgodne.',

    'email' => 'Podaj prawidłowy adres e-mail w polu :attribute.',

    'required' => 'Pole :attribute jest wymagane.',

    'string' => 'Pole :attribute musi być ciągiem znaków.',

    'min' => [
        'string' => 'Pole :attribute musi mieć co najmniej :min znaków.',
    ],

    'max' => [
        'string' => 'Pole :attribute nie może być dłuższe niż :max znaków.',
    ],

    'lowercase' => 'Pole :attribute musi składać się z małych liter.',

    'unique' => 'Wybrany :attribute jest już zajęty.',

    'password' => [
        'letters' => 'Hasło musi zawierać co najmniej jedną literę.',
        'mixed' => 'Hasło musi zawierać co najmniej jedną wielką i jedną małą literę.',
        'numbers' => 'Hasło musi zawierać co najmniej jedną cyfrę.',
        'symbols' => 'Hasło musi zawierać co najmniej jeden znak specjalny.',
        'uncompromised' => 'Podane hasło pojawiło się w wycieku danych — wybierz inne, bezpieczniejsze hasło.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Niestandardowe komunikaty (atrybut.reguła)
    |--------------------------------------------------------------------------
    */

    'current_password' => 'Podane aktualne hasło jest nieprawidłowe.',

    'custom' => [
        'email' => [
            'unique' => 'Ten adres e-mail jest już zarejestrowany. Możesz się zalogować lub skorzystać z „Nie pamiętasz hasła?”.',
            'email' => 'Podaj prawidłowy format adresu e-mail.',
        ],
        'password' => [
            'confirmed' => 'Hasło i potwierdzenie hasła muszą być identyczne.',
        ],
        'rodo_consent' => [
            'required' => 'Musisz zaznaczyć obowiązkową zgodę na przetwarzanie danych osobowych (RODO).',
            'accepted' => 'Musisz zaznaczyć obowiązkową zgodę na przetwarzanie danych osobowych (RODO).',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nazwy pól (zamiast „email”, „first_name” w treści komunikatów)
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'first_name' => 'imię',
        'last_name' => 'nazwisko',
        'email' => 'adres e-mail',
        'current_password' => 'aktualne hasło',
        'password' => 'hasło',
        'password_confirmation' => 'potwierdzenie hasła',
        'rodo_consent' => 'zgoda RODO',
        'newsletter_consent' => 'zgoda na newsletter',
    ],

];
