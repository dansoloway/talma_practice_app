<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Prompt;
use App\Models\Option;
use Illuminate\Database\Seeder;

class PollutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lesson = Lesson::firstOrCreate(
            ['slug' => 'pollution'],
            ['title' => 'Pollution', 'grade_level' => '1', 'is_active' => true, 'sort_order' => 4]
        );

        $prompt = Prompt::firstOrCreate(
            ['lesson_id' => $lesson->id, 'prompt_text' => 'What makes the earth unhealthy?'],
            ['template' => '{{answer}} makes the earth unhealthy.', 'tts_voice' => 'default', 'sort_order' => 1]
        );

        $options = [
            'air pollution',
            'water pollution',
            'soil pollution',
            'noise pollution',
        ];

        foreach ($options as $index => $label) {
            Option::firstOrCreate(
                ['prompt_id' => $prompt->id, 'label' => $label],
                [
                    'option_type' => 'text',
                    'option_text' => $label,
                    'image_path' => '',
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}



