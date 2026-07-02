<?php

return [
    'page_title' => 'تسجيل ولي الأمر — :org',
    'heading' => 'الانضمام إلى :org',
    'subtitle' => 'تسجيل ولي الأمر أو الوصي',

    'parent_section' => 'بيانات ولي الأمر / الوصي',
    'hebrew_name' => 'الاسم الكامل (بالعبرية)',
    'english_name' => 'الاسم الكامل (بالإنجليزية)',
    'id_number' => 'رقم الهوية',
    'email' => 'البريد الإلكتروني',
    'password' => 'كلمة المرور',
    'password_hint' => '8 أحرف على الأقل',
    'confirm_password' => 'تأكيد كلمة المرور',
    'phone' => 'الهاتف',
    'phone_placeholder' => 'الرقم فقط',
    'city_optional' => 'المدينة (اختياري)',
    'select_city' => 'اختر المدينة',

    'children' => 'الأطفال',
    'add_child' => '+ إضافة طفل',

    'terms_prefix' => 'أوافق على',
    'terms_link' => 'شروط الاستخدام وسياسة الخصوصية',

    'voice_waiver' => 'أوافق على حفظ تسجيلات صوتي بشكل مجهول للمساعدة في تحسين أدوات التعرف على الصوت.',
    'voice_applies_all' => 'ينطبق هذا على كل الأطفال في حسابك.',

    'create_account' => 'إنشاء حساب',
    'already_have' => 'لديك حساب بالفعل؟',
    'sign_in' => 'تسجيل الدخول',

    'show_password' => 'إظهار كلمة المرور',
    'hide_password' => 'إخفاء كلمة المرور',

    'child' => [
        'label' => 'الطفل :number',
        'remove' => 'إزالة',
        'first_name_hebrew' => 'الاسم الأول (بالعبرية)',
        'last_name_hebrew' => 'اسم العائلة (بالعبرية)',
        'first_name_english' => 'الاسم الأول (بالإنجليزية)',
        'last_name_english' => 'اسم العائلة (بالإنجليزية)',
        'birth_year' => 'سنة الميلاد',
        'grade' => 'الصف',
        'select_grade' => 'اختر الصف',
        'grade_option' => 'الصف :grade',
        'gender' => 'الجنس',
        'select_gender' => 'اختر الجنس',
        'native_language' => 'اللغة الأم',
        'select_native_language' => 'اختر اللغة الأم',
        'login_type' => 'نوع تسجيل الدخول',
        'login_shared' => 'مشترك مع ولي الأمر',
        'login_separate' => 'تسجيل دخول منفصل للطفل',
        'separate_hint' => 'استخدم بريداً إلكترونياً أو هاتفاً مختلفاً عن حساب ولي الأمر.',
        'contact_email' => 'البريد الإلكتروني',
        'contact_phone' => 'الهاتف',
        'child_email' => 'البريد الإلكتروني للطفل',
        'child_password' => 'كلمة مرور الطفل',
        'confirm_child_password' => 'تأكيد كلمة مرور الطفل',
    ],

    'terms_modal' => [
        'close' => 'إغلاق',
        'version' => 'الإصدار :version · تم التحديث :date',
    ],

    'validation' => [
        'terms_accepted' => 'يرجى تحديد مربع الموافقة على شروط الاستخدام وسياسة الخصوصية.',
        'phone_invalid' => 'يجب إدخال رقم هاتف إسرائيلي صالح (يبدأ بـ 0 أو +972).',
        'phone_registered' => 'رقم الهاتف هذا مسجل بالفعل.',
        'child_password_required' => 'كلمة المرور مطلوبة لتسجيل دخول منفصل للطفل.',
        'child_email_required' => 'يرجى إدخال البريد الإلكتروني للطفل الذي لديه تسجيل دخول منفصل.',
        'child_email_different' => 'يجب أن يكون بريد الطفل مختلفاً عن بريد ولي الأمر.',
        'child_email_registered' => 'هذا البريد الإلكتروني مسجل بالفعل.',
        'child_email_taken' => 'هذا البريد الإلكتروني مسجل بالفعل لطفل آخر.',
        'child_email_duplicate' => 'هذا البريد الإلكتروني مستخدم بالفعل لطفل آخر في هذا النموذج.',
        'child_phone_required' => 'يرجى إدخال الهاتف للطفل الذي لديه تسجيل دخول منفصل.',
        'child_phone_invalid' => 'يجب إدخال رقم هاتف إسرائيلي صالح.',
        'child_phone_different' => 'يجب أن يكون هاتف الطفل مختلفاً عن هاتف ولي الأمر.',
        'child_phone_registered' => 'رقم الهاتف هذا مسجل بالفعل.',
        'child_phone_taken' => 'رقم الهاتف هذا مسجل بالفعل لطفل آخر.',
        'child_phone_duplicate' => 'رقم الهاتف هذا مستخدم بالفعل لطفل آخر في هذا النموذج.',
    ],

    'welcome' => 'مرحباً! تم إنشاء حسابك.',

    'login' => [
        'page_title' => 'تسجيل الدخول — :org',
        'subtitle' => 'سجّل الدخول للوصول إلى دوراتك',
        'email_or_phone' => 'البريد الإلكتروني أو الهاتف',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'remember_me' => 'تذكرني',
        'submit' => 'تسجيل الدخول',
        'no_account' => 'ليس لديك حساب؟',
        'create_one' => 'إنشاء حساب',
    ],

    'login_errors' => [
        'too_many_attempts' => 'محاولات تسجيل دخول كثيرة جداً. يرجى المحاولة مرة أخرى بعد :minutes دقائق.',
        'invalid_credentials' => 'بيانات تسجيل الدخول غير صحيحة.',
        'cannot_access_portal' => 'لا يمكن لهذا الحساب الوصول إلى بوابة الطلاب.',
        'no_program_access' => 'ليس لديك حق الوصول إلى هذا البرنامج.',
    ],
];
