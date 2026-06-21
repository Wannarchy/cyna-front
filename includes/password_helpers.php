<?php

function password_meets_policy(string $password): bool
{
    return password_policy_errors($password) === [];
}

/**
 * @return list<string>
 */
function password_policy_errors(string $password, string $lang = 'fr'): array
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = match ($lang) {
            'en' => 'Password must be at least 8 characters.',
            'ar' => 'يجب أن تكون كلمة المرور 8 أحرف على الأقل.',
            'he' => 'הסיסמה חייבת להיות לפחות 8 תווים.',
            default => 'Le mot de passe doit contenir au moins 8 caractères.',
        };
    }

    if (! preg_match('/[A-Z]/', $password)) {
        $errors[] = match ($lang) {
            'en' => 'Password must contain at least one uppercase letter.',
            'ar' => 'يجب أن تحتوي كلمة المرور على حرف كبير واحد على الأقل.',
            'he' => 'הסיסמה חייבת להכיל לפחות אות גדולה אחת.',
            default => 'Le mot de passe doit contenir au moins une majuscule.',
        };
    }

    if (! preg_match('/[a-z]/', $password)) {
        $errors[] = match ($lang) {
            'en' => 'Password must contain at least one lowercase letter.',
            'ar' => 'يجب أن تحتوي كلمة المرور على حرف صغير واحد على الأقل.',
            'he' => 'הסיסמה חייבת להכיל לפחות אות קטנה אחת.',
            default => 'Le mot de passe doit contenir au moins une minuscule.',
        };
    }

    if (! preg_match('/[0-9]/', $password)) {
        $errors[] = match ($lang) {
            'en' => 'Password must contain at least one number.',
            'ar' => 'يجب أن تحتوي كلمة المرور على رقم واحد على الأقل.',
            'he' => 'הסיסמה חייבת להכיל לפחות ספרה אחת.',
            default => 'Le mot de passe doit contenir au moins un chiffre.',
        };
    }

    if (! preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = match ($lang) {
            'en' => 'Password must contain at least one special character.',
            'ar' => 'يجب أن تحتوي كلمة المرور على رمز خاص واحد على الأقل.',
            'he' => 'הסיסמה חייבת להכיל לפחות תו מיוחד אחד.',
            default => 'Le mot de passe doit contenir au moins un caractère spécial.',
        };
    }

    return $errors;
}

/**
 * @return list<string>
 */
function password_policy_hints(string $lang = 'fr'): array
{
    return match ($lang) {
        'en' => ['8 chars min', '1 uppercase', '1 lowercase', '1 number', '1 special char'],
        'ar' => ['8 أحرف على الأقل', 'حرف كبير', 'حرف صغير', 'رقم', 'رمز خاص'],
        'he' => ['8 תווים לפחות', 'אות גדולה', 'אות קטנה', 'ספרה', 'תו מיוחד'],
        default => ['8 car. min', '1 majuscule', '1 minuscule', '1 chiffre', '1 car. spécial'],
    };
}

function password_policy_placeholder(string $lang = 'fr'): string
{
    return match ($lang) {
        'en' => 'Min. 8 chars, upper, lower, number, special',
        'ar' => '8 أحرف، كبير، صغير، رقم، رمز',
        'he' => '8 תווים, גדולה, קטנה, ספרה, תו מיוחד',
        default => '8 car. min., maj., min., chiffre, spécial',
    };
}
