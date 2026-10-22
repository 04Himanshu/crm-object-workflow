<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('target_object')->default('lead')->index();
            $t->string('status')->default('draft')->index();      // draft | active | inactive
            $t->json('graph')->nullable();                         // working draft (what the builder edits)
            $t->unsignedInteger('revision')->default(1);           // bumped on every draft save (optimistic lock)
            $t->json('published_graph')->nullable();               // frozen copy the engine runs
            $t->unsignedInteger('published_version')->default(0);
            $t->timestamp('published_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('workflow_runs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->string('status')->default('running');             // running | completed | failed
            $t->text('error')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();
            $t->unique(['workflow_id', 'subject_type', 'subject_id', 'version'], 'wf_run_unique');
            $t->index(['subject_type', 'subject_id']);
        });

        Schema::create('workflow_run_steps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('workflow_run_id')->constrained()->cascadeOnDelete();
            $t->string('node_id', 64);
            $t->string('node_type', 32);
            $t->string('branch', 8)->nullable();
            $t->string('status', 16);                              // completed | failed
            $t->json('output')->nullable();
            $t->text('error')->nullable();
            $t->timestamp('executed_at')->nullable();
            $t->index('workflow_run_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_run_steps');
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('workflows');
    }
};
