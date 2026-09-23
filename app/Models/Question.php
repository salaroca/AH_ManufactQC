<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['section_id', 'question_template_id', 'text', 'question_category_id', 'order', 'is_required'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'question_category_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(QuestionTemplate::class, 'question_template_id');
    }

    /**
     * Desa aquesta pregunta com a plantilla nova al banc i l'enllaça-hi.
     * Si ja ve del banc, no fa res (no es dupliquen plantilles).
     */
    public function saveToBank(): void
    {
        if ($this->question_template_id !== null) {
            return;
        }

        $template = QuestionTemplate::create([
            'text' => $this->text,
            'question_category_id' => $this->question_category_id,
            'is_required' => $this->is_required,
        ]);

        $this->update(['question_template_id' => $template->id]);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
