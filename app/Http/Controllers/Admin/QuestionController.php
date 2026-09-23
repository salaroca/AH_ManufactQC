<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Question;
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
        $question = Question::create($request->validated());

        return response()->json($question->load('category'), 201);
    }

    public function show(Question $question): JsonResponse
    {
        return response()->json($question->load('category'));
    }

    public function update(UpdateQuestionRequest $request, Question $question): JsonResponse
    {
        $question->update($request->validated());

        return response()->json($question->load('category'));
    }

    public function destroy(Question $question): Response
    {
        $question->delete();

        return response()->noContent();
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
