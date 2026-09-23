<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionTemplateRequest;
use App\Http\Requests\UpdateQuestionTemplateRequest;
use App\Models\QuestionTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class QuestionTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $templates = QuestionTemplate::query()
            ->select('question_templates.*')
            ->with('category')
            ->withCount('questions')
            ->join('question_categories', 'question_templates.question_category_id', '=', 'question_categories.id')
            ->orderBy('question_categories.order')
            ->orderBy('question_templates.text')
            ->get();

        return response()->json($templates);
    }

    public function store(StoreQuestionTemplateRequest $request): JsonResponse
    {
        $template = QuestionTemplate::create($request->validated());

        return response()->json($template->load('category')->loadCount('questions'), 201);
    }

    public function update(UpdateQuestionTemplateRequest $request, QuestionTemplate $questionTemplate): JsonResponse
    {
        // Només canvia la plantilla: les preguntes ja copiades a seccions no es toquen.
        $questionTemplate->update($request->validated());

        return response()->json($questionTemplate->load('category')->loadCount('questions'));
    }

    public function destroy(QuestionTemplate $questionTemplate): Response
    {
        // Les còpies a les seccions es mantenen; la FK nullOnDelete només en treu l'enllaç.
        $questionTemplate->delete();

        return response()->noContent();
    }
}
