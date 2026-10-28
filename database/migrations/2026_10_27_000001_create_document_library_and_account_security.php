<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_documents', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->string('title');
            $t->string('category', 40);
            $t->string('module', 40)->default('organization');
            $t->string('parent_type', 60)->nullable();
            $t->unsignedBigInteger('parent_id')->nullable();
            $t->string('parent_reference')->nullable();
            $t->string('source_key', 190)->nullable()->unique();
            $t->boolean('authoritative')->default(false);
            $t->unsignedBigInteger('owner_id')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamp('archived_at')->nullable();
            $t->timestamps();
            $t->index(['module', 'parent_id']);
            $t->index(['category', 'created_at']);
        });
        Schema::create('library_document_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained('library_documents')->restrictOnDelete();
            $t->unsignedInteger('version');
            $t->string('disk', 30);
            $t->string('path');
            $t->string('original_name')->nullable();
            $t->string('mime_type', 150)->nullable();
            $t->unsignedBigInteger('size')->nullable();
            $t->string('sha256', 64)->nullable();
            $t->string('status', 30)->default('available');
            $t->string('validation_status', 40)->default('validation_only');
            $t->unsignedBigInteger('uploaded_by')->nullable();
            $t->timestamp('uploaded_at')->nullable();
            $t->timestamps();
            $t->unique(['document_id', 'version']);
        });
        Schema::create('library_document_roles', function (Blueprint $t) {
            $t->foreignId('document_id')->constrained('library_documents')->restrictOnDelete();
            $t->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $t->primary(['document_id', 'role_id']);
        });
        Schema::table('users', function (Blueprint $t) {
            $t->text('two_factor_secret')->nullable();
            $t->text('two_factor_recovery_codes')->nullable();
            $t->timestamp('two_factor_confirmed_at')->nullable();
            $t->unsignedInteger('session_version')->default(1);
            $t->boolean('must_change_password')->default(false);
            $t->timestamp('temporary_password_expires_at')->nullable();
        });
        Schema::table('qard_agreements', function (Blueprint $t) {
            $t->json('witnesses')->nullable();
            $t->boolean('legacy_witnesses')->default(false);
        });
        DB::table('qard_agreements')->update(['legacy_witnesses' => true]);
    }

    public function down(): void
    {
        throw new RuntimeException('Phase 11 stores retained evidence and security state. Restore a verified backup instead of dropping it.');
    }
};
