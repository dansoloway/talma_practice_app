<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('registration_type', 32)->default('student')->after('allow_self_registration');
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hebrew_name')->nullable();
            $table->timestamps();
        });

        Schema::create('terms_and_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->string('title');
            $table->text('content');
            $table->string('version')->default('1.0');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index('active');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('hebrew_name')->nullable()->after('name');
            $table->string('id_number', 20)->nullable()->after('hebrew_name');
            $table->string('phone_number', 20)->nullable()->after('email');
            $table->foreignId('city_id')->nullable()->after('phone_number')->constrained('cities')->nullOnDelete();
            $table->timestamp('terms_accepted_at')->nullable()->after('voice_recording_consented_at');
            $table->string('terms_version', 50)->nullable()->after('terms_accepted_at');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'student', 'parent') NOT NULL DEFAULT 'teacher'");
        } elseif ($driver === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role_new', 20)->default('teacher');
            });
            DB::table('users')->update(['role_new' => DB::raw('role')]);
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('role_new', 'role');
            });
        }

        Schema::create('parent_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('first_name_english')->nullable();
            $table->string('last_name_english')->nullable();
            $table->date('birth_date')->nullable();
            $table->unsignedTinyInteger('grade')->nullable();
            $table->string('gender', 16)->nullable();
            $table->timestamps();

            $table->index('parent_id');
            $table->index('user_id');
        });

        Schema::create('student_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('parent_students')->cascadeOnDelete();
            $table->string('email')->nullable()->unique();
            $table->string('phone_number', 20)->nullable();
            $table->string('login_type', 16)->default('shared');
            $table->timestamps();

            $table->index('student_id');
            $table->index('phone_number');
        });

        Schema::table('responses', function (Blueprint $table) {
            $table->foreignId('parent_student_id')->nullable()->after('user_id')->constrained('parent_students')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_student_id');
        });

        Schema::dropIfExists('student_identities');
        Schema::dropIfExists('parent_students');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropColumn(['hebrew_name', 'id_number', 'phone_number', 'terms_accepted_at', 'terms_version']);
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::table('users')->where('role', 'parent')->update(['role' => 'student']);
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'teacher'");
        } elseif ($driver === 'sqlite') {
            DB::table('users')->where('role', 'parent')->update(['role' => 'student']);
            Schema::table('users', function (Blueprint $table) {
                $table->string('role_old', 20)->default('teacher');
            });
            DB::table('users')->update(['role_old' => DB::raw('role')]);
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('role_old', 'role');
            });
        }

        Schema::dropIfExists('terms_and_conditions');
        Schema::dropIfExists('cities');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('registration_type');
        });
    }
};
