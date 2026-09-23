<?php

namespace App\Models;

use Database\Factories\QuestionTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una pregunta del "Banc de preguntes": una plantilla que es copia a les seccions.
 * Les còpies (Question) són independents; editar la plantilla no les modifica.
 */
#[Fillable(['text', 'question_category_id', 'is_required'])]
class QuestionTemplate extends Model
{
    /** @use HasFactory<QuestionTemplateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'question_category_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Crea una còpia d'aquesta plantilla com a pregunta de la secció, al final de la llista.
     */
    public function copyToSection(Section $section, int $order): Question
    {
        return $section->questions()->create([
            'question_template_id' => $this->id,
            'text' => $this->text,
            'question_category_id' => $this->question_category_id,
            'is_required' => $this->is_required,
            'order' => $order,
        ]);
    }
}
