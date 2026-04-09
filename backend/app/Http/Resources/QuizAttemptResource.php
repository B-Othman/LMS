<?php

namespace App\Http\Resources;

use App\Models\QuestionOption;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/** @mixin QuizAttempt */
class QuizAttemptResource extends JsonResource
{
    private bool $showResults = false;

    public function withResults(bool $showResults = true): static
    {
        $this->showResults = $showResults;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $answerMap = $this->answers->keyBy('question_id');

        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'enrollment_id' => $this->enrollment_id,
            'user_id' => $this->user_id,
            'started_at' => $this->started_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'expires_at' => $this->quiz->time_limit_minutes
                ? $this->started_at?->copy()->addMinutes((int) $this->quiz->time_limit_minutes)?->toIso8601String()
                : null,
            'score' => $this->score !== null ? (float) $this->score : null,
            'total_points' => (int) $this->total_points,
            'passed' => $this->passed,
            'time_spent_seconds' => (int) $this->time_spent_seconds,
            'status' => $this->status->value,
            'results_available' => $this->showResults && $this->quiz->show_results_to_learner,
            'quiz' => [
                'id' => $this->quiz->id,
                'course_id' => $this->quiz->course_id,
                'lesson_id' => $this->quiz->lesson_id,
                'title' => $this->quiz->title,
                'description' => $this->quiz->description,
                'pass_score' => (int) $this->quiz->pass_score,
                'time_limit_minutes' => $this->quiz->time_limit_minutes,
                'attempts_allowed' => (int) $this->quiz->attempts_allowed,
                'shuffle_questions' => $this->quiz->shuffle_questions,
                'show_results_to_learner' => $this->quiz->show_results_to_learner,
                'question_count' => (int) ($this->quiz->questions_count ?? $this->quiz->questions->count()),
            ],
            'questions' => $this->orderedQuestions()->map(function (QuizQuestion $question) use ($answerMap) {
                $answer = $answerMap->get($question->id);

                return [
                    'id' => $question->id,
                    'quiz_id' => $question->quiz_id,
                    'question_type' => $question->question_type->value,
                    'prompt' => $question->prompt,
                    'explanation' => $this->showResults && $this->quiz->show_results_to_learner
                        ? $question->explanation
                        : null,
                    'points' => (int) $question->points,
                    'sort_order' => (int) $question->sort_order,
                    'options' => $question->options->map(function (QuestionOption $option) {
                        $data = [
                            'id' => $option->id,
                            'question_id' => $option->question_id,
                            'label' => $option->label,
                            'sort_order' => (int) $option->sort_order,
                        ];

                        if ($this->showResults && $this->quiz->show_results_to_learner) {
                            $data['is_correct'] = $option->is_correct;
                        }

                        return $data;
                    })->values()->all(),
                    'answer' => [
                        'answer_payload' => $answer?->answer_payload ?? [],
                        'is_correct' => $this->showResults && $this->quiz->show_results_to_learner
                            ? $answer?->is_correct
                            : null,
                        'awarded_points' => $this->showResults && $this->quiz->show_results_to_learner
                            ? (int) ($answer?->awarded_points ?? 0)
                            : null,
                    ],
                ];
            })->values()->all(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /** @return Collection<int, QuizQuestion> */
    private function orderedQuestions(): Collection
    {
        $questions = $this->quiz->questions;

        if (! $this->quiz->shuffle_questions) {
            return $questions->values();
        }

        return $questions
            ->sortBy(fn (QuizQuestion $question) => md5($this->id.'-'.$question->id))
            ->values();
    }
}
