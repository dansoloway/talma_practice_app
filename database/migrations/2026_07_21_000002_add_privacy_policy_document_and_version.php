<?php

use App\Models\TermsAndCondition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('privacy_policy_version', 50)->nullable()->after('privacy_policy_read_at');
        });

        if (TermsAndCondition::where('type', 'privacy_policy')->exists()) {
            return;
        }

        TermsAndCondition::create([
            'type' => 'privacy_policy',
            'title' => 'Privacy Policy',
            'content' => <<<'EN'
TALMA Practice Pal — Privacy Policy

Replace this placeholder with your final English privacy policy text.

Privacy
We collect registration information such as names, email addresses, phone numbers, and learner profile details needed to operate the program you join. We use this information to provide access, support learners, and improve our educational content.

When voice recording retention is enabled for a program, learners may optionally contribute anonymized voice samples for training purposes. Those samples are stored without linkage to personal identity.

We do not sell personal information. We may share data with service providers who help us operate the platform, or when required by law.

You may contact us to request access to or correction of personal information we hold about you.
EN,
            'translations' => [
                'he' => [
                    'title' => 'מדיניות פרטיות',
                    'content' => <<<'HE'
TALMA Practice Pal — מדיניות פרטיות

יש להחליף טקסט זה במדיניות הפרטיות הסופית בעברית.

פרטיות
אנו אוספים מידע הרשמה כגון שמות, כתובות דוא"ל, מספרי טלפון ופרטי פרופיל לומד הנדרשים להפעלת התוכנית שאליה אתם מצטרפים. אנו משתמשים במידע זה כדי לספק גישה, לתמוך בלומדים ולשפר את התוכן החינוכי שלנו.

כאשר שמירת הקלטות קול מופעלת עבור תוכנית, לומדים יכולים לבחור לתרום דגימות קול אנונימיות לצורכי אימון. דגימות אלה נשמרות ללא קישור לזהות אישית.

איננו מוכרים מידע אישי. אנו עשויים לשתף נתונים עם ספקי שירות המסייעים לנו להפעיל את הפלטפורמה, או כאשר נדרש על פי חוק.

ניתן לפנות אלינו כדי לבקש גישה לתיקון מידע אישי שמוחזק עליכם.
HE,
                ],
            ],
            'version' => '1.0',
            'active' => true,
        ]);
    }

    public function down(): void
    {
        TermsAndCondition::where('type', 'privacy_policy')->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('privacy_policy_version');
        });
    }
};
