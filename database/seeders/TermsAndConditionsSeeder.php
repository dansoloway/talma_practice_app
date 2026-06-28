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
                'content' => <<<'TEXT'
TALMA Practice Pal — Terms of Use and Privacy Policy

By registering for Practice Pal, you (or your parent/guardian on your behalf) agree to the following terms.

General
Practice Pal is an online English practice platform operated by TALMA. The platform provides self-paced lessons, vocabulary activities, and games for learners. Access to some programs requires registration.

Eligibility and accounts
You agree to provide accurate information during registration. If you are under 18, a parent or legal guardian must complete registration and accept these terms on your behalf.

Use of the platform
You agree to use Practice Pal for personal learning only. You may not attempt to access other users' accounts, scrape content, or misuse the service.

Privacy
We collect registration information such as names, email addresses, phone numbers, and learner profile details needed to operate the program you join. We use this information to provide access, support learners, and improve our educational content.

When voice recording retention is enabled for a program, learners may optionally contribute anonymized voice samples for training purposes. Those samples are stored without linkage to personal identity.

We do not sell personal information. We may share data with service providers who help us operate the platform, or when required by law.

You may contact us to request access to or correction of personal information we hold about you.

Content and intellectual property
Lesson content, images, audio, and materials on Practice Pal are owned by TALMA and its partners. You may not copy, redistribute, or commercially exploit platform content without written permission.

Limitation of liability
Practice Pal is provided as an educational supplement. TALMA is not liable for indirect or consequential damages arising from use of the platform or third-party services integrated with it.

Changes
We may update these terms from time to time. The version shown at registration applies to your signup.

Parent/guardian confirmation
By checking the acceptance box during registration, you confirm that you have read and agree to these terms and that you are the learner or the legal guardian registering a learner.
TEXT,
                'version' => '1.0',
                'active' => true,
            ]
        );
    }
}
