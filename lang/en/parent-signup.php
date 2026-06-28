<?php

return [
    'page_title' => 'Parent Registration — :org',
    'heading' => 'Join :org',
    'subtitle' => 'Parent or guardian registration',

    'parent_section' => 'Parent / guardian details',
    'hebrew_name' => 'Full name (Hebrew)',
    'english_name' => 'Full name (English)',
    'id_number' => 'ID number',
    'email' => 'Email',
    'password' => 'Password',
    'password_hint' => 'At least 8 characters',
    'confirm_password' => 'Confirm password',
    'phone' => 'Phone',
    'phone_placeholder' => 'Number only',
    'city_optional' => 'City (optional)',
    'select_city' => 'Select city',

    'children' => 'Children',
    'add_child' => '+ Add child',

    'terms_prefix' => 'I agree to the',
    'terms_link' => 'terms of use and privacy policy',

    'voice_waiver' => 'I agree that my voice recordings may be saved anonymously to help improve voice recognition tools.',
    'voice_applies_all' => 'This applies to every child on your account.',

    'create_account' => 'Create Account',
    'already_have' => 'Already have an account?',
    'sign_in' => 'Sign in',

    'child' => [
        'label' => 'Child :number',
        'remove' => 'Remove',
        'first_name_hebrew' => 'First name (Hebrew)',
        'last_name_hebrew' => 'Last name (Hebrew)',
        'first_name_english' => 'First name (English)',
        'last_name_english' => 'Last name (English)',
        'birth_year' => 'Year of birth',
        'grade' => 'Grade',
        'select_grade' => 'Select grade',
        'grade_option' => 'Grade :grade',
        'gender' => 'Gender',
        'select_gender' => 'Select gender',
        'native_language' => 'Native language',
        'select_native_language' => 'Select native language',
        'login_type' => 'Login type',
        'login_shared' => 'Shared with parent',
        'login_separate' => 'Separate login for child',
        'separate_hint' => 'Use a different email or phone from the parent account.',
        'contact_email' => 'Email',
        'contact_phone' => 'Phone',
        'child_email' => 'Child email',
        'child_password' => 'Child password',
        'confirm_child_password' => 'Confirm child password',
    ],

    'terms_modal' => [
        'close' => 'Close',
        'version' => 'Version :version · Updated :date',
    ],

    'validation' => [
        'terms_accepted' => 'Please check the box to accept the terms of use and privacy policy.',
        'phone_invalid' => 'Phone must be a valid Israeli number (starting with 0 or +972).',
        'phone_registered' => 'This phone number is already registered.',
        'child_password_required' => 'Password is required for separate child login.',
        'child_email_required' => 'Please enter email for student with separate login.',
        'child_email_different' => 'Student email must be different from parent\'s email.',
        'child_email_registered' => 'This email is already registered.',
        'child_email_taken' => 'This email is already registered to another student.',
        'child_email_duplicate' => 'This email is already used for another child in this form.',
        'child_phone_required' => 'Please enter phone for student with separate login.',
        'child_phone_invalid' => 'Phone must be a valid Israeli number.',
        'child_phone_different' => 'Student phone must be different from parent\'s phone.',
        'child_phone_registered' => 'This phone number is already registered.',
        'child_phone_taken' => 'This phone number is already registered to another student.',
        'child_phone_duplicate' => 'This phone number is already used for another child in this form.',
    ],

    'welcome' => 'Welcome! Your account has been created.',
];
