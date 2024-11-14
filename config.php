<?php

return [
    /**
     * The directory containing your log files.
     */
    'log_path' => $_SERVER['DOCUMENT_ROOT'] . '/logs',

    'severity_levels' => [
        'DEBUG' => [
            'bold' => false,
            'emergency' => false,
            'explicit' => false,
            'html_color' => 'black',
        ],
        'INFO' => [
            'bold' => false,
            'emergency' => false,
            'explicit' => false,
            'html_color' => 'black',
        ],
        'WARNING' => [
            'bold' => false,
            'emergency' => false,
            'explicit' => false,
            'html_color' => 'SandyBrown',
        ],
        'ERROR' => [
            'bold' => true,
            'emergency' => false,
            'explicit' => true,
            'html_color' => 'tomato',
        ],
        'EMERGENCY' => [
            'bold' => true,
            'emergency' => true,
            'explicit' => true,
            'html_color' => 'red',
        ],
    ],

    /**
     * The email addresses you want to send the reports to.
     */
    'users' => [
        // 'example@email.com',
    ],

    'max_emails_per_minute' => 1,
    'max_emails_per_hour' => 60,
    'max_emails_per_day' => 24,

    'smtp_host' => 'smtp.example.com',
    'smtp_user' => 'your_smtp_username',
    'smtp_password' => 'your_smtp_password',
    'smtp_port' => 587,
    'from_email' => 'noreply@example.com',
    'from_name' => 'Your App Name',
];