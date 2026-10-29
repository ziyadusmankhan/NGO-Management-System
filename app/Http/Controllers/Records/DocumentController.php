<?php

namespace App\Http\Controllers\Records;

use App\Domain\Documents\Models\DocumentVersion;
use App\Domain\Documents\Models\LibraryDocument;
use App\Domain\Documents\Services\DocumentAccess;
use App\Domain\Documents\Services\DocumentService;
use App\Domain\Security\Services\AccountSecurity;
use App\Domain\Users\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentController
{
    public function __construct(private DocumentService $service) {}

    public function index(Request $r, DocumentAccess $access)
    {
        $f = $r->validate(['search' => 'nullable|string|max:150', 'module' => 'nullable|string|max:40', 'category' => ['nullable', Rule::in(DocumentService::CATEGORIES)], 'reference' => 'nullable|string|max:100', 'uploader' => 'nullable|integer', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'archived' => 'nullable|in:0,1']);
        $q = $access->visible($r->user());
        $q->when(! ($f['archived'] ?? false), fn ($q) => $q->whereNull('archived_at'));
        foreach (['module', 'category'] as $key) {
            if (! empty($f[$key])) {
                $q->where($key, $f[$key]);
            }
        }
        if (! empty($f['search'])) {
            $q->where('title', 'like', '%'.$f['search'].'%');
        }if (! empty($f['reference'])) {
            $q->where('parent_reference', 'like', '%'.$f['reference'].'%');
        }
        if (! empty($f['uploader'])) {
            $q->whereHas('versions', fn ($q) => $q->where('uploaded_by', $f['uploader']));
        }
        if (! empty($f['from'])) {
            $q->whereHas('versions', fn ($q) => $q->where('uploaded_at', '>=', Carbon::parse($f['from'], 'Asia/Karachi')->startOfDay()->utc()));
        }
        if (! empty($f['to'])) {
            $q->whereHas('versions', fn ($q) => $q->where('uploaded_at', '<', Carbon::parse($f['to'], 'Asia/Karachi')->addDay()->startOfDay()->utc()));
        }
        $documents = $q->with('versions')->orderByDesc('id')->paginate(25)->withQueryString();

        return response()->view('records.documents.index', compact('documents', 'f'))->header('Cache-Control', 'private, no-store');
    }

    public function create()
    {
        return view('records.documents.create');
    }

    public function store(Request $r)
    {
        $data = $this->upload($r);
        $d = $this->service->save($r->user(), $data);

        return redirect()->route('records.documents.show', $d)->with('success', 'Document uploaded. Validation only; not malware-scanned.');
    }

    private function upload(Request $r): array
    {
        return $r->validate(['title' => 'required|string|max:200', 'category' => ['required', Rule::in(DocumentService::CATEGORIES)], 'document' => 'required|file|max:10240', 'parent_type' => ['nullable', Rule::in(array_keys(DocumentAccess::PARENTS))], 'parent_id' => 'nullable|required_with:parent_type|integer|min:1', 'revision' => 'nullable|integer']);
    }

    public function show(Request $r, LibraryDocument $document)
    {
        abort_unless($this->service->access->allows($document, $r->user()), 404);
        $document->load('versions', 'roles');
        $roles = Role::where('status', 'active')->orderBy('name')->get();

        return response()->view('records.documents.show', compact('document', 'roles'))->header('Cache-Control', 'private, no-store');
    }

    public function version(Request $r, LibraryDocument $document)
    {
        $data = $this->upload($r);
        $this->service->save($r->user(), $data, $document);

        return back()->with('success', 'A new version was retained alongside the original.');
    }

    public function download(Request $r, LibraryDocument $document, DocumentVersion $version)
    {
        return $this->service->download($r->user(), $document, $version);
    }

    public function change(Request $r, LibraryDocument $document, string $action)
    {
        abort_unless(in_array($action, ['manage', 'share', 'archive', 'restore']), 404);
        $data = $r->validate(['revision' => 'required|integer', 'title' => [Rule::requiredIf($action === 'manage'), 'nullable', 'string', 'max:200'], 'category' => [Rule::requiredIf($action === 'manage'), 'nullable', Rule::in(DocumentService::CATEGORIES)], 'roles' => 'nullable|array', 'roles.*' => ['integer', 'distinct', Rule::exists('roles', 'id')->where('status', 'active')]]);
        if ($action === 'share') {
            app(AccountSecurity::class)->requireRecent($r);
        }
        $this->service->change($r->user(), $document, $action, $data);

        return back()->with('success', 'Document updated.');
    }
}
