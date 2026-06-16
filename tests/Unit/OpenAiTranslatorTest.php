<?php

namespace Tests\Unit;

use App\Services\Translation\OpenAiTranslator;
use Tests\TestCase;

class OpenAiTranslatorTest extends TestCase
{
    public function test_default_arabic_variant_is_saudi(): void
    {
        config(['services.openai.arabic_variant' => 'saudi']);

        $translator = app(OpenAiTranslator::class);

        $this->assertSame('saudi', $translator->arabicVariant());
        $this->assertSame('Saudi Arabic', $translator->arabicVariantLabel());
    }

    public function test_msa_arabic_variant_can_be_configured(): void
    {
        config(['services.openai.arabic_variant' => 'msa']);

        $translator = app(OpenAiTranslator::class);

        $this->assertSame('msa', $translator->arabicVariant());
        $this->assertSame('Modern Standard Arabic', $translator->arabicVariantLabel());
    }

    public function test_unknown_arabic_variant_falls_back_to_saudi(): void
    {
        config(['services.openai.arabic_variant' => 'egyptian']);

        $translator = app(OpenAiTranslator::class);

        $this->assertSame('saudi', $translator->arabicVariant());
    }
}
