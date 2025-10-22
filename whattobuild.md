Got it. Here’s a tighter, build-ready spec centered on Laravel + MySQL, with pre-generated ElevenLabs audio (limited option sets).

App: Sentence Speaking (Laravel + MySQL)

Core assumptions
	•	Each lesson has 3–5 prompts.
	•	Each prompt has a small, finite set of options (e.g., colors).
	•	For each (prompt, option) we pre-generate:
	•	the model sentence via string templating
	•	a TTS MP3 using ElevenLabs (stored in S3 or /storage/app/public).
	•	Student recording is optional and saved locally (no cloud upload unless explicitly enabled in env).

⸻

Data model (MySQL)

Tables

lessons
	•	id PK
	•	title varchar(255)
	•	slug varchar(255) unique
	•	is_active tinyint(1) default 1
	•	sort_order int

prompts
	•	id PK
	•	lesson_id FK → lessons.id (cascade)
	•	prompt_text varchar(255)           // e.g., “What is your favorite color?”
	•	template varchar(255)              // e.g., “My favorite color is {{answer}}.”
	•	tts_voice varchar(64)              // which ElevenLabs voice was used
	•	sort_order int

options
	•	id PK
	•	prompt_id FK → prompts.id (cascade)
	•	label varchar(64)                  // e.g., “red”
	•	image_path varchar(255)            // e.g., “images/colors/red.png”
	•	is_active tinyint(1) default 1
	•	sort_order int

prompt_option_assets  // assets per (prompt, option)
	•	id PK
	•	prompt_id FK
	•	option_id FK
	•	generated_sentence varchar(255)    // resolved template (“My favorite color is red.”)
	•	audio_path varchar(255)            // pre-generated MP3 path
	•	duration_ms int nullable

responses  (optional progress tracking)
	•	id PK
	•	user_id nullable int               // if auth exists
	•	lesson_id int
	•	prompt_id int
	•	option_id int
	•	generated_sentence varchar(255)
	•	recording_path varchar(255) nullable // local file if saved
	•	created_at timestamp

settings (optional feature flags)
	•	id PK
	•	key varchar(120) unique
	•	value text                         // JSON for privacy, uploads, etc.

⸻

Content seeding & pre-generation
	1.	Seed lessons, prompts, options (Laravel seeders).
	2.	Precompute assets:
	•	For each (prompt, option):
	•	Render generated_sentence: replace {{answer}} with options.label.
	•	Generate TTS MP3 in a one-time CLI command:

php artisan tts:build-assets --lesson=colors


	•	Save file to storage/app/public/tts/<lesson>/<prompt>_<option>.mp3
	•	Insert into prompt_option_assets.

	3.	CDN / S3 (optional): run php artisan storage:link or push to S3; store URL in audio_path.

Keep ElevenLabs usage entirely offline from the runtime app. The app only reads paths.

⸻

Laravel structure

Routes (web)
	•	GET /lessons → list lessons
	•	GET /lessons/{slug} → start lesson, returns 3–5 prompts (with options)
	•	GET /prompts/{id} → JSON: prompt, options (label, image), and no audio yet (audio depends on option)
	•	GET /prompts/{id}/options/{optionId}/model → JSON: generated_sentence, audio_url
	•	POST /responses → save selection (+ optional recording path)

Controllers
	•	LessonController@index/show
	•	PromptController@show
	•	PromptModelController@show($promptId, $optionId)  // fetches from prompt_option_assets
	•	ResponseController@store

Blade (or Inertia) pages
	•	Lessons list
	•	Lesson runner (stepper): Prompt → Options → Model sentence → Play/Record

Components (Blade or Vue/React via Inertia)
	•	<PromptCard :prompt />
	•	<OptionGrid :options />
	•	<ModelSentence :text :audioUrl />
	•	<Recorder :maxDuration /> (uses MediaRecorder API)

⸻

Frontend logic (runtime)

Flow per prompt
	1.	Show prompt_text and option images.
	2.	On select → GET /prompts/{id}/options/{optionId}/model
	•	Receive { generated_sentence, audio_url }.
	3.	Show sentence; autoplay or play on click.
	4.	Practice:
	•	Replay audio
	•	Record (optional): use browser MediaRecorder → save to Blob → upload to /responses only if PRIVACY_ALLOW_UPLOAD=true.
	•	If uploads disabled, let students play back locally without POST.

POST /responses body

{
  "lesson_id": 1,
  "prompt_id": 10,
  "option_id": 3,
  "generated_sentence": "My favorite color is red.",
  "recording_path": "/storage/recordings/u123/p10_20251020_153055.webm"  // optional
}


⸻

Privacy & storage
	•	Env flags
	•	PRIVACY_ALLOW_UPLOAD=false (default)
	•	RECORDING_MAX_SECONDS=20
	•	MEDIA_DISK=public|s3
	•	If uploads disabled: do not POST blobs; keep playback in-memory only.
	•	If enabled: validate MIME (audio/webm, audio/mpeg), size limit, virus scan (optional), and store under /storage/app/private/recordings/{user}/{date}/… (or S3 private bucket).

⸻

Example Eloquent relations

class Lesson extends Model {
  public function prompts() { return $this->hasMany(Prompt::class)->orderBy('sort_order'); }
}

class Prompt extends Model {
  public function options() { return $this->hasMany(Option::class)->orderBy('sort_order'); }
  public function assets()  { return $this->hasMany(PromptOptionAsset::class); }
}

class PromptOptionAsset extends Model {
  protected $fillable = ['prompt_id','option_id','generated_sentence','audio_path','duration_ms'];
}


⸻

CLI: asset builder (outline)

// app/Console/Commands/BuildTtsAssets.php
public function handle() {
  $prompts = Prompt::with('options')->get();
  foreach ($prompts as $prompt) {
    foreach ($prompt->options as $opt) {
      $sentence = Str::of($prompt->template)->replace('{{answer}}', $opt->label);
      $file = "tts/lesson{$prompt->lesson_id}/p{$prompt->id}_o{$opt->id}.mp3";

      // (1) call local script that already generated MP3s or
      // (2) if you keep ElevenLabs client locally for batch runs:
      // $this->elevenLabs->synthesize($sentence, $prompt->tts_voice, storage_path("app/public/$file"));

      PromptOptionAsset::updateOrCreate(
        ['prompt_id'=>$prompt->id,'option_id'=>$opt->id],
        ['generated_sentence'=>$sentence,'audio_path'=>"/storage/$file",'duration_ms'=>null]
      );
    }
  }
}

(If TTS files are produced externally, just place them at the agreed path and run a command that only writes DB rows.)

⸻

Minimal migrations (sketch)

Schema::create('lessons', function (Blueprint $t) {
  $t->id(); $t->string('title'); $t->string('slug')->unique();
  $t->boolean('is_active')->default(true); $t->integer('sort_order')->default(0); $t->timestamps();
});

Schema::create('prompts', function (Blueprint $t) {
  $t->id(); $t->foreignId('lesson_id')->constrained()->cascadeOnDelete();
  $t->string('prompt_text'); $t->string('template'); $t->string('tts_voice')->default('default');
  $t->integer('sort_order')->default(0); $t->timestamps();
});

Schema::create('options', function (Blueprint $t) {
  $t->id(); $t->foreignId('prompt_id')->constrained()->cascadeOnDelete();
  $t->string('label'); $t->string('image_path'); $t->boolean('is_active')->default(true);
  $t->integer('sort_order')->default(0); $t->timestamps();
});

Schema::create('prompt_option_assets', function (Blueprint $t) {
  $t->id();
  $t->foreignId('prompt_id')->constrained()->cascadeOnDelete();
  $t->foreignId('option_id')->constrained()->cascadeOnDelete();
  $t->string('generated_sentence');
  $t->string('audio_path');
  $t->integer('duration_ms')->nullable();
  $t->unique(['prompt_id','option_id']);
  $t->timestamps();
});

Schema::create('responses', function (Blueprint $t) {
  $t->id(); $t->unsignedBigInteger('user_id')->nullable();
  $t->foreignId('lesson_id'); $t->foreignId('prompt_id'); $t->foreignId('option_id');
  $t->string('generated_sentence'); $t->string('recording_path')->nullable();
  $t->timestamps(); $t->index(['user_id','lesson_id']);
});


⸻

QA checklist
	•	All (prompt, option) combos have a matching TTS file and prompt_option_assets row.
	•	Audio URLs resolve (public disk or signed S3 URL).
	•	Recording disabled by default (env); local playback works without upload.
	•	Lessons render exactly 3–5 prompts in order.
	•	Mobile mic permissions flow tested (Safari iOS + Chrome Android).

⸻

If you want, I can turn this into a starter Laravel repo plan (routes, controllers, seeders, migrations) you can drop into a fresh project.