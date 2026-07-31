<?php

return [
    'verify_email' => [
        'subject' => 'Verify your email for Uraboros',
        'preheader' => 'Confirm your email to unlock your Uraboros workspace.',
        'title' => 'Confirm your email address',
        'intro' => 'Finish securing your account and activate full access to the Uraboros workspace.',
        'button' => 'Verify email',
        'expiry' => 'For security reasons, use the most recent verification email you received.',
        'fallback' => 'If the button does not open, copy and paste this link into your browser:',
        'security' => 'This verification protects account access, notifications, and password recovery.',
        'ignore' => 'If you did not create an account, you can safely ignore this email.',
        'signature' => 'Uraboros security system',
    ],
    'reset_password' => [
        'subject' => 'Reset your Uraboros password',
        'preheader' => 'Use the secure link to create a new password for your Uraboros account.',
        'title' => 'Reset your password',
        'intro' => 'We received a password reset request for your Uraboros account. Use the secure button below to set a new password.',
        'button' => 'Create new password',
        'expiry' => 'This password reset link will expire in :count minutes.',
        'fallback' => 'If the button does not open, copy and paste this link into your browser:',
        'security' => 'For security, choose a strong unique password you do not use anywhere else.',
        'ignore' => 'If you did not request a password reset, you can safely ignore this email and your current password will remain unchanged.',
        'signature' => 'Uraboros security system',
    ],
];
