<?php

namespace App\Http\Controllers\Api\V2;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V2\FormTemplateResource;
use App\Models\FormTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class FormTemplateController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = FormTemplate::query()
            ->published()
            ->with(['transportMode']);

        if ($user && $user->role === UserRole::Surveyor) {
            $hasAnyAssignment = $user->assignments()->exists();
            if ($hasAnyAssignment) {
                $query->whereHas('assignments', fn ($q) => $q->where('user_id', $user->id));
            }
        }

        if ($request->filled('transport_mode_id')) {
            $query->where('transport_mode_id', $request->integer('transport_mode_id'));
        }

        if ($request->filled('updated_since')) {
            $since = $this->parseDate($request->input('updated_since'));

            if ($since !== null) {
                $query->where('updated_at', '>=', $since);
            }
        }

        return FormTemplateResource::collection($query->orderByDesc('version')->get());
    }

    public function show(FormTemplate $template): FormTemplateResource
    {
        abort_unless($template->isPublished(), 404, 'Template belum diterbitkan.');

        $template->load([
            'transportMode',
            'sections' => fn ($q) => $q->orderBy('sort_order'),
            'fields' => fn ($q) => $q->orderBy('sort_order'),
            'rootGroups' => fn ($q) => $q->orderBy('sort_order'),
            'rootGroups.questions' => fn ($q) => $q->orderBy('sort_order'),
            'rootGroups.questions.questionOptions' => fn ($q) => $q->orderBy('sort_order'),
            'rootGroups.children' => fn ($q) => $q->orderBy('sort_order'),
            'rootGroups.children.questions' => fn ($q) => $q->orderBy('sort_order'),
            'rootGroups.children.questions.questionOptions' => fn ($q) => $q->orderBy('sort_order'),
            'rootGroups.children.children' => fn ($q) => $q->orderBy('sort_order'),
            'rootGroups.children.children.questions' => fn ($q) => $q->orderBy('sort_order'),
            'rootGroups.children.children.questions.questionOptions' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        return FormTemplateResource::make($template);
    }

    /** Parse tanggal filter dengan aman; abaikan nilai tak valid. */
    private function parseDate(mixed $value): ?Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
