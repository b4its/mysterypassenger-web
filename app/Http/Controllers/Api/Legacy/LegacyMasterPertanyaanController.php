<?php

namespace App\Http\Controllers\Api\Legacy;

use App\Enums\OutputSection;
use App\Http\Controllers\Controller;
use App\Models\FormTemplate;
use App\Models\QuestionGroup;
use App\Models\TransportMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegacyMasterPertanyaanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->formatQuestions(null);
    }

    public function checklist(Request $request): JsonResponse
    {
        return $this->formatQuestions(OutputSection::Checklist);
    }

    public function laporan(Request $request): JsonResponse
    {
        return $this->formatQuestions(OutputSection::Report);
    }

    public function gone(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Pengelolaan pertanyaan dipindahkan ke panel web.',
        ], 410);
    }

    private function formatQuestions(?OutputSection $section): JsonResponse
    {
        $mode = TransportMode::where('slug', 'kapal-penumpang')->first()
            ?? TransportMode::first();

        if (! $mode) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $template = FormTemplate::query()
            ->where('transport_mode_id', $mode->id)
            ->published()
            ->latest('version')
            ->first();

        if (! $template) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $groupsQuery = QuestionGroup::query()
            ->where('form_template_id', $template->id)
            ->whereNull('parent_id')
            ->with(['questions' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order');

        if ($section) {
            $groupsQuery->where(function ($q) use ($section) {
                $q->where('output_section', $section)
                    ->orWhere('output_section', OutputSection::Both);
            });
        }

        $groups = $groupsQuery->get();
        $data = [];

        foreach ($groups as $group) {
            $parentType = $group->output_section === OutputSection::Report ? 'laporan' : 'ceklist';

            // Representasi kelompok sebagai baris induk (parent_id = null)
            $groupRow = [
                'id' => $group->id,
                'teks' => $group->name,
                'tipe' => $parentType,
                'parent_id' => null,
                'urutan' => $group->sort_order,
                'list_pertanyaan' => [],
            ];

            foreach ($group->questions as $q) {
                $childType = $parentType;

                $groupRow['list_pertanyaan'][] = [
                    'id' => $q->id,
                    'teks' => $q->text,
                    'tipe' => $childType,
                    'parent_id' => $group->id,
                    'urutan' => $q->sort_order,
                ];
            }

            $data[] = $groupRow;
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
