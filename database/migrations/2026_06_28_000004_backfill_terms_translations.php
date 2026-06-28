<?php

use App\Models\TermsAndCondition;
use Database\Seeders\TermsAndConditionsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! TermsAndCondition::where('type', 'student_signup')->exists()) {
            (new TermsAndConditionsSeeder)->run();

            return;
        }

        $terms = TermsAndCondition::where('type', 'student_signup')->first();
        if ($terms && empty($terms->translations)) {
            (new TermsAndConditionsSeeder)->run();
        }
    }

    public function down(): void
    {
        // Translations are content-only; no rollback needed.
    }
};
