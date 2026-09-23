<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderQuestionCategoriesRequest;
use App\Http\Requests\StoreQuestionCategoryRequest;
use App\Http\Requests\UpdateQuestionCategoryRequest;
use App\Models\QuestionCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class QuestionCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json($this->orderedCategories());
    }

    public function store(StoreQuestionCategoryRequest $request): JsonResponse
    {
        $category = QuestionCategory::create([
            ...$request->validated(),
            'order' => $request->validated('order') ?? QuestionCategory::max('order') + 1,
        ]);

        return response()->json($category->loadCount('questions'), 201);
    }

    public function update(UpdateQuestionCategoryRequest $request, QuestionCategory $questionCategory): JsonResponse
    {
        $questionCategory->update($request->validated());

        return response()->json($questionCategory->loadCount('questions'));
    }

    public function destroy(QuestionCategory $questionCategory): Response
    {
        if ($questionCategory->questions()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Aquesta categoria està en ús per alguna pregunta i no es pot eliminar.',
            ]);
        }

        $questionCategory->delete();

        return response()->noContent();
    }

    public function reorder(ReorderQuestionCategoriesRequest $request): JsonResponse
    {
        foreach ($request->validated('category_ids') as $index => $id) {
            QuestionCategory::whereKey($id)->update(['order' => $index]);
        }

        return response()->json($this->orderedCategories());
    }

    private function orderedCategories()
    {
        return QuestionCategory::withCount('questions')->orderBy('order')->orderBy('id')->get();
    }
}
