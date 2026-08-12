<?php

use App\Models\TermsAndCondition;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        TermsAndCondition::query()->each(function (TermsAndCondition $document) {
            $document->content = $this->rebrand($document->content ?? '');
            $document->translations = $this->rebrandTranslations($document->translations ?? []);
            $document->save();
        });
    }

    public function down(): void
    {
        // Name-only copy change; original wording is not restored.
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, mixed>
     */
    private function rebrandTranslations(array $translations): array
    {
        foreach ($translations as $locale => $payload) {
            if (! is_array($payload)) {
                continue;
            }

            if (isset($payload['content']) && is_string($payload['content'])) {
                $translations[$locale]['content'] = $this->rebrand($payload['content']);
            }

            if (isset($payload['title']) && is_string($payload['title'])) {
                $translations[$locale]['title'] = $this->rebrand($payload['title']);
            }
        }

        return $translations;
    }

    private function rebrand(string $text): string
    {
        $text = str_replace('TALMA Practice Pal', 'TALMA', $text);

        return str_replace('Practice Pal', 'TALMA', $text);
    }
};
