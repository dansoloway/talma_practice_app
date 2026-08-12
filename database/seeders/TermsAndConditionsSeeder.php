<?php

namespace Database\Seeders;

use App\Models\TermsAndCondition;
use Illuminate\Database\Seeder;

class TermsAndConditionsSeeder extends Seeder
{
    public function run(): void
    {
        TermsAndCondition::updateOrCreate(
            ['type' => 'student_signup'],
            [
                'title' => 'Terms of Use and Privacy Policy',
                'content' => <<<'EN'
TALMA — Terms of Use and Privacy Policy

By registering for TALMA, you (or your parent/guardian on your behalf) agree to the following terms.

General
TALMA is an online English practice platform operated by TALMA. The platform provides self-paced lessons, vocabulary activities, and games for learners. Access to some programs requires registration.

Eligibility and accounts
You agree to provide accurate information during registration. If you are under 18, a parent or legal guardian must complete registration and accept these terms on your behalf.

Use of the platform
You agree to use TALMA for personal learning only. You may not attempt to access other users' accounts, scrape content, or misuse the service.

Privacy
We collect registration information such as names, email addresses, phone numbers, and learner profile details needed to operate the program you join. We use this information to provide access, support learners, and improve our educational content.

When voice recording retention is enabled for a program, learners may optionally contribute anonymized voice samples for training purposes. Those samples are stored without linkage to personal identity.

We do not sell personal information. We may share data with service providers who help us operate the platform, or when required by law.

You may contact us to request access to or correction of personal information we hold about you.

Content and intellectual property
Lesson content, images, audio, and materials on TALMA are owned by TALMA and its partners. You may not copy, redistribute, or commercially exploit platform content without written permission.

Limitation of liability
TALMA is provided as an educational supplement. TALMA is not liable for indirect or consequential damages arising from use of the platform or third-party services integrated with it.

Changes
We may update these terms from time to time. The version shown at registration applies to your signup.

Parent/guardian confirmation
By checking the acceptance box during registration, you confirm that you have read and agree to these terms and that you are the learner or the legal guardian registering a learner.
EN,
                'translations' => [
                    'he' => [
                        'title' => 'תנאי שימוש ומדיניות פרטיות',
                        'content' => <<<'HE'
TALMA — תנאי שימוש ומדיניות פרטיות

בעת ההרשמה ל-TALMA, אתם (או הוריכם/אפוטרופוסיכם בשמכם) מסכימים לתנאים הבאים.

כללי
TALMA היא פלטפורמת תרגול אנגלית מקוונת המופעלת על ידי TALMA. הפלטפורמה מספקת שיעורים בקצב אישי, פעילויות אוצר מילים ומשחקים ללומדים. גישה לתוכניות מסוימות מחייבת הרשמה.

זכאות וחשבונות
אתם מתחייבים לספק מידע מדויק במהלך ההרשמה. אם אתם מתחת לגיל 18, הורה או אפוטרופוס חוקי חייבים להשלים את ההרשמה ולקבל תנאים אלה בשמכם.

שימוש בפלטפורמה
אתם מסכימים להשתמש ב-TALMA ללמידה אישית בלבד. אין לנסות לגשת לחשבונות של משתמשים אחרים, לגרד תוכן או לעשות שימוש לרעה בשירות.

פרטיות
אנו אוספים מידע הרשמה כגון שמות, כתובות דוא"ל, מספרי טלפון ופרטי פרופיל לומד הנדרשים להפעלת התוכנית שאליה אתם מצטרפים. אנו משתמשים במידע זה כדי לספק גישה, לתמוך בלומדים ולשפר את התוכן החינוכי שלנו.

כאשר שמירת הקלטות קול מופעלת עבור תוכנית, לומדים יכולים לבחור לתרום דגימות קול אנונימיות לצורכי אימון. דגימות אלה נשמרות ללא קישור לזהות אישית.

איננו מוכרים מידע אישי. אנו עשויים לשתף נתונים עם ספקי שירות המסייעים לנו להפעיל את הפלטפורמה, או כאשר נדרש על פי חוק.

ניתן לפנות אלינו כדי לבקש גישה לתיקון מידע אישי שמוחזק עליכם.

תוכן וקניין רוחני
תוכן שיעורים, תמונות, אודיו וחומרים ב-TALMA שייכים ל-TALMA ולשותפיה. אין להעתיק, להפיץ מחדש או לנצל לצרכים מסחריים תוכן מהפלטפורמה ללא אישור בכתב.

הגבלת אחריות
TALMA מסופקת כתוספת חינוכית. TALMA אינה אחראית לנזקים עקיפים או תוצאתיים הנובעים משימוש בפלטפורמה או בשירותי צד שלישי המשולבים בה.

שינויים
אנו עשויים לעדכן תנאים אלה מעת לעת. הגרסה המוצגת בעת ההרשמה חלה על ההרשמה שלכם.

אישור הורה/אפוטרופוס
בסימון תיבת ההסכמה במהלך ההרשמה, אתם מאשרים שקראתם והסכמתם לתנאים אלה, ושאתם הלומד או האפוטרופוס החוקי המרשם לומד.
HE,
                    ],
                    'ar' => [
                        'title' => 'شروط الاستخدام وسياسة الخصوصية',
                        'content' => <<<'AR'
TALMA — شروط الاستخدام وسياسة الخصوصية

بالتسجيل في TALMA، أنت (أو ولي أمرك/وصيك نيابةً عنك) توافق على الشروط التالية.

عام
TALMA هي منصة تدريب إنجليزي عبر الإنترنت تديرها TALMA. توفر المنصة دروساً ذاتية الإيقاع وأنشطة مفردات وألعاب للمتعلمين. يتطلب الوصول إلى بعض البرامج التسجيل.

الأهلية والحسابات
توافق على تقديم معلومات دقيقة أثناء التسجيل. إذا كان عمرك أقل من 18 عاماً، يجب على ولي الأمر أو الوصي القانوني إكمال التسجيل وقبول هذه الشروط نيابةً عنك.

استخدام المنصة
توافق على استخدام TALMA للتعلم الشخصي فقط. لا يجوز محاولة الوصول إلى حسابات مستخدمين آخرين أو استخراج المحتوى أو إساءة استخدام الخدمة.

الخصوصية
نجمع معلومات التسجيل مثل الأسماء وعناوين البريد الإلكتروني وأرقام الهاتف وتفاصيل ملف المتعلم اللازمة لتشغيل البرنامج الذي تنضم إليه. نستخدم هذه المعلومات لتوفير الوصول ودعم المتعلمين وتحسين المحتوى التعليمي.

عند تفعيل الاحتفاظ بتسجيلات الصوت لبرنامج ما، يمكن للمتعلمين اختيار المساهمة بعينات صوت مجهولة لأغراض التدريب. تُخزَّن هذه العينات دون ربطها بهوية شخصية.

لا نبيع المعلومات الشخصية. قد نشارك البيانات مع مزودي خدمات يساعدوننا في تشغيل المنصة، أو عندما يقتضي القانون ذلك.

يمكنك التواصل معنا لطلب الوصول إلى معلوماتك الشخصية أو تصحيحها.

المحتوى والملكية الفكرية
محتوى الدروس والصور والصوت والمواد في TALMA مملوكة لـ TALMA وشركائها. لا يجوز نسخ أو إعادة توزيع أو استغلال محتوى المنصة تجارياً دون إذن كتابي.

تحديد المسؤولية
تُقدَّم TALMA كمكمل تعليمي. لا تتحمل TALMA مسؤولية الأضرار غير المباشرة أو التبعية الناشئة عن استخدام المنصة أو خدمات طرف ثالث مدمجة معها.

التغييرات
قد نحدّث هذه الشروط من وقت لآخر. ينطبق الإصدار المعروض عند التسجيل على تسجيلك.

تأكيد ولي الأمر/الوصي
بتحديد مربع الموافقة أثناء التسجيل، تؤكد أنك قرأت هذه الشروط ووافقت عليها، وأنك المتعلم أو ولي الأمر القانوني الذي يسجل متعلماً.
AR,
                    ],
                ],
                'version' => '1.0',
                'active' => true,
            ]
        );
    }
}
