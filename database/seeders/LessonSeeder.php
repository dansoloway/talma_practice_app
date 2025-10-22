<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Prompt;
use App\Models\Option;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lesson 1: Colors
        $colorsLesson = Lesson::create([
            'title' => 'Favorite Colors',
            'slug' => 'colors',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $colorPrompt = Prompt::create([
            'lesson_id' => $colorsLesson->id,
            'prompt_text' => 'What is your favorite color?',
            'template' => 'My favorite color is {{answer}}.',
            'tts_voice' => 'default',
            'sort_order' => 1,
        ]);

        $colors = ['red', 'blue', 'green', 'yellow', 'purple'];
        foreach ($colors as $index => $color) {
            Option::create([
                'prompt_id' => $colorPrompt->id,
                'label' => $color,
                'image_path' => "images/colors/{$color}.png",
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        // Lesson 2: Animals
        $animalsLesson = Lesson::create([
            'title' => 'Favorite Animals',
            'slug' => 'animals',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $animalPrompt = Prompt::create([
            'lesson_id' => $animalsLesson->id,
            'prompt_text' => 'What is your favorite animal?',
            'template' => 'My favorite animal is the {{answer}}.',
            'tts_voice' => 'default',
            'sort_order' => 1,
        ]);

        $animals = ['dog', 'cat', 'elephant', 'lion', 'dolphin'];
        foreach ($animals as $index => $animal) {
            Option::create([
                'prompt_id' => $animalPrompt->id,
                'label' => $animal,
                'image_path' => "images/animals/{$animal}.png",
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        // Lesson 3: Food
        $foodLesson = Lesson::create([
            'title' => 'Favorite Foods',
            'slug' => 'food',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $foodPrompt = Prompt::create([
            'lesson_id' => $foodLesson->id,
            'prompt_text' => 'What is your favorite food?',
            'template' => 'My favorite food is {{answer}}.',
            'tts_voice' => 'default',
            'sort_order' => 1,
        ]);

        $foods = ['pizza', 'pasta', 'sushi', 'tacos', 'ice cream'];
        foreach ($foods as $index => $food) {
            Option::create([
                'prompt_id' => $foodPrompt->id,
                'label' => $food,
                'image_path' => "images/food/{$food}.png",
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        // Add second prompt to colors lesson
        $colorPrompt2 = Prompt::create([
            'lesson_id' => $colorsLesson->id,
            'prompt_text' => 'What color is the sky?',
            'template' => 'The sky is {{answer}}.',
            'tts_voice' => 'default',
            'sort_order' => 2,
        ]);

        $skyColors = ['blue', 'gray', 'orange', 'pink'];
        foreach ($skyColors as $index => $color) {
            Option::create([
                'prompt_id' => $colorPrompt2->id,
                'label' => $color,
                'image_path' => "images/colors/{$color}.png",
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        // Lesson 4: Pollution
        $pollutionLesson = Lesson::firstOrCreate([
            'slug' => 'pollution'
        ], [
            'title' => 'Pollution',
            'is_active' => true,
            'sort_order' => 4,
        ]);

        $pollutionPrompt = Prompt::firstOrCreate([
            'lesson_id' => $pollutionLesson->id,
            'prompt_text' => 'What makes the earth unhealthy?',
        ], [
            'template' => '{{answer}} makes the earth unhealthy.',
            'tts_voice' => 'default',
            'sort_order' => 1,
        ]);

        $pollutionOptions = [
            'air pollution',
            'water pollution',
            'soil pollution',
            'noise pollution',
        ];

        foreach ($pollutionOptions as $index => $label) {
            Option::firstOrCreate([
                'prompt_id' => $pollutionPrompt->id,
                'label' => $label,
            ], [
                'option_type' => 'text',
                'option_text' => $label,
                'image_path' => '',
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }
}

