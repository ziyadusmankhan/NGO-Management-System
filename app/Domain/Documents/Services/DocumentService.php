<?php

namespace App\Domain\Documents\Services;

use App\Domain\Documents\Models\DocumentVersion;
use App\Domain\Documents\Models\LibraryDocument;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentService
{
    public const CATEGORIES = ['Agreement', 'Identity', 'Receipt', 'Quotation', 'Photo', 'Verification', 'Minutes', 'Policy', 'Correspondence', 'Other'];

    public function __construct(public DocumentAccess $access, public DocumentRegistry $registry) {}

    public function authorize(User $u, string $action, ?LibraryDocument $d = null): void
    {
        abort_unless($u->isActive() && ! $u->trashed() && $u->hasPermission('documents.'.$action) && (! $d || $this->access->allows($d, $u)), 403);
    }

    public function save(User $u, array $data, ?LibraryDocument $document = null): LibraryDocument
    {
        $this->authorize($u, $document ? 'manage' : 'upload', $document);
        if ($document && ($document->parent_type || $document->authoritative)) {
            throw ValidationException::withMessages(['document' => 'Linked evidence is maintained through its original workflow.']);
        }
        $meta = app(UploadValidator::class)->inspect($data['document']);
        $path = $data['document']->storeAs('documents', Str::uuid().'.'.$meta['extension'], 'private');
        if (! $path) {
            throw ValidationException::withMessages(['document' => 'The file could not be stored. Try again.']);
        }
        try {
            return DB::transaction(function () use ($u, $data, $document, $meta, $path) {
                if ($document) {
                    $d = LibraryDocument::whereKey($document->id)->lockForUpdate()->firstOrFail();
                    $this->current($d, $data);
                    abort_if($d->archived_at, 422);
                    $d->increment('revision');
                } else {
                    $type = $data['parent_type'] ?? null;
                    $id = $data['parent_id'] ?? null;
                    if ($type) {
                        abort_unless($this->access->parentAllowed($type, (int) $id, $u, true), 403);
                        $parent = $this->access->parent($type, (int) $id);
                    }
                    $d = LibraryDocument::create(['number' => 'DOC-'.Str::ulid(), 'title' => $data['title'], 'category' => $data['category'], 'module' => $type ?: 'organization', 'parent_type' => $type, 'parent_id' => $id, 'parent_reference' => isset($parent) ? $this->registry->reference($parent) : null, 'owner_id' => $u->id]);
                }
                $d->versions()->create(['version' => (int) $d->versions()->max('version') + 1, 'disk' => 'private', 'path' => $path, 'original_name' => $data['document']->getClientOriginalName(), 'mime_type' => $meta['mime_type'], 'size' => $meta['size'], 'sha256' => $meta['sha256'], 'uploaded_by' => $u->id, 'uploaded_at' => now()]);
                $this->registry->audit($d, $document ? 'document_version_added' : 'document_uploaded', ['after' => ['revision' => $d->revision]]);

                return $d;
            }, 5);
        } catch (\Throwable $e) {
            Storage::disk('private')->delete($path);
            throw $e;
        }
    }

    public function current(LibraryDocument $d, array $input): void
    {
        if ((int) ($input['revision'] ?? 0) !== $d->revision) {
            throw ValidationException::withMessages(['revision' => 'This document changed. Reload before trying again.']);
        }
    }

    public function change(User $u, LibraryDocument $document, string $action, array $data): void
    {
        $this->authorize($u, $action, $document);
        abort_if($document->parent_type || $document->authoritative, 403);
        DB::transaction(function () use ($document, $action, $data) {
            $d = LibraryDocument::whereKey($document->id)->lockForUpdate()->firstOrFail();
            $this->current($d, $data);
            $before = $d->only(['title', 'category', 'archived_at']);
            if ($action === 'share') {
                $before = ['roles' => $d->roles()->pluck('slug')->all()];
                $d->roles()->sync($data['roles'] ?? []);
                $after = ['roles' => $d->roles()->pluck('slug')->all()];
            } elseif ($action === 'archive') {
                $d->archived_at = now();
                $after = ['archived' => true];
            } elseif ($action === 'restore') {
                $d->archived_at = null;
                $after = ['archived' => false];
            } else {
                $d->fill(['title' => $data['title'], 'category' => $data['category']]);
                $after = $d->only(['title', 'category']);
            }
            $d->revision++;
            $d->save();
            $this->registry->audit($d, 'document_'.$action, ['before' => $before, 'after' => $after]);
        }, 5);
    }

    public function sourceDownload(User $u, string $key)
    {
        $d = LibraryDocument::where('source_key', $key)->firstOrFail();

        return $this->download($u, $d, $d->versions()->latest('version')->firstOrFail(), false);
    }

    public function download(User $u, LibraryDocument $d, DocumentVersion $v, bool $library = true)
    {
        abort_unless((int) $v->document_id === $d->id && $this->access->allows($d, $u), 404);
        if ($library) {
            $this->authorize($u, 'view', $d);
            if (! $d->parent_type) {
                $this->authorize($u, 'download', $d);
            }
        }
        abort_unless($v->status === 'available' && $this->registry->safePath($v->disk, $v->path) && Storage::disk($v->disk)->exists($v->path), 404);
        abort_unless(hash_equals($v->sha256, hash_file('sha256', Storage::disk($v->disk)->path($v->path))), 409, 'The stored file failed its integrity check.');
        $name = preg_replace('/[^\pL\pN ._()-]/u', '_', basename(str_replace('\\', '/', $v->original_name ?? ($d->number.'-v'.$v->version.'.'.pathinfo($v->path, PATHINFO_EXTENSION)))));
        $this->registry->audit($d, 'document_downloaded', ['after' => ['revision' => $v->version]]);

        return Storage::disk($v->disk)->download($v->path, $name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "sandbox; default-src 'none'", 'Content-Type' => $v->mime_type ?: 'application/octet-stream']);
    }
}
