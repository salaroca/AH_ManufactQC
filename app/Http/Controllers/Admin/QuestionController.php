<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddQuestionsFromTemplatesRequest;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Question;
use App\Models\QuestionTemplate;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $questions = Question::query()
            ->with('category')
            ->when($request->filled('section_id'), fn ($query) => $query->where('section_id', $request->integer('section_id')))
            ->orderBy('order')
            ->get();

        return response()->json($questions);
    }

    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $question = Question::create($request->safe()->except('save_to_bank'));

        if ($request->boolean('save_to_bank')) {
            $question->saveToBank();
        }

        return response()->json($question->load('category'), 201);
    }

    public function show(Question $question): JsonResponse
    {
        return response()->json($question->load('category'));
    }

    public function update(UpdateQuestionRequest $request, Question $question): JsonResponse
    {
        $question->update($request->safe()->except('save_to_bank'));

        if ($request->boolean('save_to_bank')) {
            $question->saveToBank();
        }

        return response()->json($question->load('category'));
    }

    public function destroy(Question $question): Response
    {
        $question->delete();

        return response()->noContent();
    }

    /**
     * Copia preguntes del banc a la secció, al final de la llista. Les que la secció ja
     * té (copiades abans de la mateixa plantilla) es descarten per no duplicar-les.
     */
    public function storeFromTemplates(AddQuestionsFromTemplatesRequest $request, Section $section): JsonResponse
    {
        $alreadyInSection = $section->questions()->whereNotNull('question_template_id')->pluck('question_template_id');
        $nextOrder = ($section->questions()->max('order') ?? -1) + 1;

        QuestionTemplate::whereIn('id', $request->validated('template_ids'))
            ->whereNotIn('id', $alreadyInSection)
            ->get()
            ->sortBy(fn (QuestionTemplate $template) => array_search($template->id, $request->validated('template_ids')))
            ->each(function (QuestionTemplate $template) use ($section, &$nextOrder) {
                $template->copyToSection($section, $nextOrder++);
            });

        return response()->json(
            $section->questions()->with('category')->orderBy('order')->get()
        );
    }

    public function reorder(Request $request, Section $section): JsonResponse
    {
        $data = $request->validate([
            'question_ids' => ['required', 'array'],
            'question_ids.*' => ['integer', Rule::exists('questions', 'id')->where('section_id', $section->id)],
        ]);

        foreach ($data['question_ids'] as $index => $id) {
            Question::whereKey($id)->update(['order' => $index]);
        }

        return response()->json(
            $section->questions()->with('category')->orderBy('order')->get()
        );
    }
}
