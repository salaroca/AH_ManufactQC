<?php

namespace App\Http\Controllers\Operari;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePhotosRequest;
use App\Http\Requests\UpdateEquipmentObservationsRequest;
use App\Models\Equipment;
use App\Models\OrderFabrication;
use App\Models\Photo;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Http\JsonResponse;

class EquipmentController extends Controller
{
    public function index(OrderFabrication $orderFabrication): JsonResponse
    {
        return response()->json([
            'order_fabrication' => $orderFabrication->load(['project.family', 'project.sections']),
            'equipment' => $orderFabrication->equipment()->withCount('defects')->orderBy('serie_number')->get(),
        ]);
    }

    public function show(Equipment $equipment): JsonResponse
    {
        $equipment->load(['project.sections', 'orderFabrication', 'defects', 'photos']);

        $answers = $equipment->answers()->with('defects.responsibleUser')->get()->keyBy('question_id');

        $sections = $equipment->project->sections()
            ->with(['questions' => fn ($query) => $query->with('category')->orderBy('order')])
            ->get()
            ->map(fn (Section $section) => [
                'id' => $section->id,
                'name' => $section->name,
                'description' => $section->description,
                'questions' => $section->questions->map(function (Question $question) use ($answers) {
                    $answer = $answers->get($question->id);

                    return [
                        'id' => $question->id,
                        'text' => $question->text,
                        'category' => $question->category,
                        'order' => $question->order,
                        'is_required' => $question->is_required,
                        'answer' => $answer ? [
                            'id' => $answer->id,
                            'response' => $answer->response,
                            'language_chosen' => $answer->language_chosen,
                            'defects' => $answer->defects,
                        ] : null,
                    ];
                }),
            ]);

        return response()->json([
            'equipment' => $equipment,
            'sections' => $sections,
        ]);
    }

    public function update(UpdateEquipmentObservationsRequest $request, Equipment $equipment): JsonResponse
    {
        $equipment->update($request->validated());

        return response()->json($equipment);
    }

    public function storePhotos(StorePhotosRequest $request, Equipment $equipment): JsonResponse
    {
        if (! $equipment->isFullyAnswered()) {
            return response()->json([
                'message' => 'Falten preguntes obligatòries per respondre abans de finalitzar.',
            ], 422);
        }

        if ($equipment->hasCurrentDefectAnswer()) {
            return response()->json([
                'message' => 'Hi ha alguna pregunta marcada com a Defecte. Canvia-la a Sí o No abans de finalitzar.',
            ], 422);
        }

        foreach ($request->file('photos', []) as $file) {
            $path = $file->store('', 'photos');

            Photo::create([
                'equipment_id' => $equipment->id,
                'path' => $path,
                'uploaded_at' => now(),
            ]);
        }

        $equipment->update([
            'status' => $equipment->statusFor(finished: true),
            'checked_at' => now(),
        ]);

        return response()->json($equipment->fresh(['photos']));
    }
}
