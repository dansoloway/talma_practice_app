# True/False Game Setup Guide

## Overview

The True/False game allows students to listen to statements and determine if they're true or false. Questions are generated using AI from lesson content, then reviewed and approved by admins.

**Language Level:** CEFR A1 (Beginner) - All questions use very simple English with basic vocabulary and short sentences (5-10 words).

---

## 🚀 Quick Start

### 1. Generate Questions for a Lesson

```bash
# Generate 6 questions (default) for a lesson
php artisan true-false:generate {lesson_id}

# Generate 8 questions
php artisan true-false:generate {lesson_id} --count=8

# Auto-approve questions (skip manual review)
php artisan true-false:generate {lesson_id} --approve

# Generate questions AND create audio files
php artisan true-false:generate {lesson_id} --generate-audio

# All options together
php artisan true-false:generate {lesson_id} --count=7 --approve --generate-audio
```

**Examples:**
```bash
# By lesson ID
php artisan true-false:generate 1

# By lesson slug
php artisan true-false:generate making-a-volcano

# Generate and approve 8 questions with audio
php artisan true-false:generate 1 --count=8 --approve --generate-audio
```

---

## 📋 Requirements

### Environment Variables

Add to your `.env` file:

```env
# Required for AI question generation
OPENAI_API_KEY=your-openai-api-key

# Optional: For auto-generating audio
ELEVENLABS_API_KEY=your-elevenlabs-api-key
```

---

## 🔄 Workflow

### Step 1: Generate Questions

Run the command to generate 5-8 questions from lesson content:

```bash
php artisan true-false:generate 1
```

**What happens:**
1. Loads lesson vocabulary and prompts
2. Sends content to OpenAI API
3. AI generates True/False questions
4. Questions are saved to database (pending approval)
5. Optionally generates TTS audio for statements

**Output:**
```
Generating 6 True/False questions for: Session 3: Making a Volcano
Calling OpenAI to generate questions...
Generated 6 questions
  ✓ Created: First, you fold a piece of paper in half...
  ✓ Created: You need a measuring tape to make a paper airplane...
  ✓ Created: Ice melts faster in warm water than cold water...
  ✓ Created: To melt means to turn from solid to liquid...
  ✓ Created: You should throw the paper airplane before folding it...
  ✓ Created: An experiment is something you do to learn about science...

✅ Created 6 question(s)
⚠️  Questions are pending approval. Review them in the admin panel.
```

---

### Step 2: Review & Approve Questions

**Admin Interface:** (To be built)
- View all pending questions
- Edit statement, explanation, or answer
- Approve or reject questions
- Create custom questions manually

**For now, approve via command:**
```bash
php artisan tinker
>>> $question = \App\Models\TrueFalseQuestion::find(1);
>>> $question->update(['is_approved' => true]);
```

---

### Step 3: Students Play the Game

**Game Flow:**
1. Student selects True/False game for a lesson
2. Audio plays statement (e.g., "Ice melts faster in warm water")
3. Student can click to show text (optional)
4. Student selects True or False
5. Immediate feedback with explanation
6. Moves to next question
7. Shows completion screen with score

---

## 📊 Database Structure

### `true_false_questions` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `lesson_id` | bigint | Foreign key to lessons |
| `statement` | text | The True/False statement |
| `is_true` | boolean | Whether statement is true |
| `explanation` | text | Explanation shown after answer |
| `category` | string | Question category (optional) |
| `audio_path` | string | Path to TTS audio file |
| `is_approved` | boolean | Admin approval status |
| `is_active` | boolean | Whether question is active |
| `sort_order` | integer | Display order |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

## 🎯 Question Categories

Questions are automatically categorized:

- **science_facts** - Science facts and definitions
- **procedures** - Procedural steps and instructions
- **vocabulary** - Word meanings and definitions
- **process** - Process understanding and sequence
- **misconception** - Common student errors

---

## 🎨 Game Features

### Audio-First Design
- Statement plays as audio first
- Student listens before seeing text
- Option to show text (button click)
- No images (for now)

### CEFR A1 Language Level
- **Very simple English** - Beginner level
- **Short sentences** - 5-10 words maximum
- **Basic vocabulary** - Common words only
- **Simple grammar** - Present tense (is, are, do, have)
- **No complex structures** - No passive voice, conditionals, or idioms

### Feedback
- Immediate visual feedback (✅ Correct / ❌ Incorrect)
- Explanation shown after answer
- Score tracking (X/Y correct)
- Completion percentage

### Progress
- Progress indicator (Question X of Y)
- Can navigate back/forward
- Completion screen with final score

---

## 🔧 Command Options

### `--count={number}`
Number of questions to generate (5-8)
- Default: 6
- Min: 5
- Max: 8

### `--approve`
Auto-approve generated questions
- Default: false (requires manual approval)
- Use when you trust AI output

### `--generate-audio`
Generate TTS audio for statements
- Default: false
- Requires ELEVENLABS_API_KEY
- Creates audio files automatically

---

## 📝 Example Questions Generated (CEFR A1 Level)

Based on lesson "Making a Volcano":

1. ✅ "We fold paper first" → TRUE
   Explanation: "Yes! We fold paper first."

2. ❌ "We need a timer" → FALSE
   Explanation: "No. We need paper."

3. ✅ "Ice is cold" → TRUE
   Explanation: "Yes! Ice is cold."

4. ✅ "Ice melts in sun" → TRUE
   Explanation: "Yes! Ice melts in sun."

5. ❌ "We throw first" → FALSE
   Explanation: "No. We fold first."

6. ✅ "An experiment is fun" → TRUE
   Explanation: "Yes! Experiments are fun."

**Language Features:**
- Simple words only
- Short sentences (5-10 words)
- Simple present tense
- No complex grammar

---

## 🛠️ Manual Question Creation

Admins can also create questions manually:

```php
\App\Models\TrueFalseQuestion::create([
    'lesson_id' => 1,
    'statement' => 'Vinegar and baking soda make a reaction',
    'is_true' => true,
    'explanation' => 'Yes! When you mix them, they react and make bubbles.',
    'category' => 'science_facts',
    'is_approved' => true,
    'is_active' => true,
    'sort_order' => 1,
]);
```

---

## 🎮 Next Steps

1. ✅ **Database & Model** - Created
2. ✅ **AI Question Generation** - Created
3. ✅ **Command to Generate** - Created
4. ⏳ **Admin Interface** - To be built
   - List questions
   - Approve/edit questions
   - Create manual questions
5. ⏳ **Game Play Interface** - To be built
   - Audio-first display
   - True/False buttons
   - Feedback and scoring

---

## 🐛 Troubleshooting

### "OpenAI API key not configured"
- Add `OPENAI_API_KEY` to `.env`
- Run `php artisan config:clear`

### "Failed to generate questions"
- Check OpenAI API key is valid
- Check internet connection
- Check API rate limits
- Review logs: `storage/logs/laravel.log`

### "No audio generated"
- Set `ELEVENLABS_API_KEY` in `.env`
- Use `--generate-audio` flag
- Or generate audio manually later

---

## 📚 Related Documentation

- `docs/TRUE_FALSE_GAME_DETAILED.md` - Detailed game explanation
- `docs/7TH_GRADE_GAME_RECOMMENDATIONS.md` - Why this game fits your content

---

*The True/False game is perfect for testing comprehension and reinforcing vocabulary. Audio-first design ensures students practice listening skills while learning science content.*

