<?php

namespace Tests\Feature\Quiz;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonType;
use App\Events\CourseCompleted;
use App\Events\QuizCompleted;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithRbac;
use Tests\TestCase;

class QuizManagementTest extends TestCase
{
    use InteractsWithRbac, RefreshDatabase;

    public function test_admin_can_create_manage_and_reorder_quiz_questions(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $manager = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($manager, 'content_manager');

        [$course, $quizLesson] = $this->createPublishedQuizLessonCourse($tenant, $manager);

        Sanctum::actingAs($manager);

        $createResponse = $this->postJson('/api/v1/quizzes', [
            'course_id' => $course->id,
            'lesson_id' => $quizLesson->id,
            'title' => 'Final Knowledge Check',
            'description' => 'Quiz learners must pass before completing the module.',
            'pass_score' => 80,
            'time_limit_minutes' => 15,
            'attempts_allowed' => 2,
            'shuffle_questions' => true,
            'show_results_to_learner' => true,
            'status' => 'draft',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.lesson_id', $quizLesson->id)
            ->assertJsonPath('data.title', 'Final Knowledge Check')
            ->assertJsonPath('data.question_count', 0);

        $quizId = (int) $createResponse->json('data.id');

        $addFirstQuestion = $this->postJson("/api/v1/quizzes/{$quizId}/questions", [
            'question_type' => 'multiple_choice',
            'prompt' => '<p>Which team owns the current tenant?</p>',
            'points' => 3,
            'options' => [
                ['label' => 'Security', 'is_correct' => true, 'sort_order' => 1],
                ['label' => 'Finance', 'is_correct' => false, 'sort_order' => 2],
                ['label' => 'Operations', 'is_correct' => false, 'sort_order' => 3],
            ],
        ]);

        $addSecondQuestion = $this->postJson("/api/v1/quizzes/{$quizId}/questions", [
            'question_type' => 'multi_select',
            'prompt' => '<p>Select the secure defaults.</p>',
            'points' => 2,
            'options' => [
                ['label' => 'MFA enabled', 'is_correct' => true, 'sort_order' => 1],
                ['label' => 'Shared credentials', 'is_correct' => false, 'sort_order' => 2],
                ['label' => 'Audit logging', 'is_correct' => true, 'sort_order' => 3],
            ],
        ]);

        $addFirstQuestion->assertOk()
            ->assertJsonPath('data.question_count', 1)
            ->assertJsonPath('data.questions.0.options.0.is_correct', true);
        $addSecondQuestion->assertOk()
            ->assertJsonPath('data.question_count', 2);

        $firstQuestionId = (int) $addSecondQuestion->json('data.questions.0.id');
        $secondQuestionId = (int) $addSecondQuestion->json('data.questions.1.id');

        $updateResponse = $this->putJson("/api/v1/questions/{$secondQuestionId}", [
            'prompt' => '<p>Select every secure default.</p>',
            'explanation' => '<p>Strong defaults reduce preventable incidents.</p>',
            'options' => [
                [
                    'id' => $addSecondQuestion->json('data.questions.1.options.0.id'),
                    'label' => 'MFA enabled',
                    'is_correct' => true,
                    'sort_order' => 1,
                ],
                [
                    'id' => $addSecondQuestion->json('data.questions.1.options.1.id'),
                    'label' => 'Shared credentials',
                    'is_correct' => false,
                    'sort_order' => 2,
                ],
                [
                    'id' => $addSecondQuestion->json('data.questions.1.options.2.id'),
                    'label' => 'Audit logging',
                    'is_correct' => true,
                    'sort_order' => 3,
                ],
            ],
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.questions.1.prompt', '<p>Select every secure default.</p>')
            ->assertJsonPath('data.questions.1.explanation', '<p>Strong defaults reduce preventable incidents.</p>');

        $reorderResponse = $this->postJson("/api/v1/quizzes/{$quizId}/questions/reorder", [
            'questions' => [
                ['id' => $firstQuestionId, 'sort_order' => 2],
                ['id' => $secondQuestionId, 'sort_order' => 1],
            ],
        ]);

        $showResponse = $this->getJson("/api/v1/quizzes/{$quizId}");
        $deleteResponse = $this->deleteJson("/api/v1/questions/{$firstQuestionId}");

        $reorderResponse->assertOk();
        $showResponse->assertOk()
            ->assertJsonPath('data.questions.0.id', $secondQuestionId)
            ->assertJsonPath('data.questions.1.id', $firstQuestionId);
        $deleteResponse->assertOk()
            ->assertJsonPath('data.question_count', 1);

        $this->assertDatabaseHas('quizzes', [
            'id' => $quizId,
            'lesson_id' => $quizLesson->id,
            'pass_score' => 80,
            'attempts_allowed' => 2,
        ]);

        $this->assertDatabaseHas('quiz_questions', [
            'quiz_id' => $quizId,
            'prompt' => '<p>Select every secure default.</p>',
        ]);
    }

    public function test_learner_can_start_and_submit_quiz_without_seeing_answers_before_submission(): void
    {
        Event::fake([QuizCompleted::class, CourseCompleted::class]);

        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $learner = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($admin, 'tenant_admin');
        $this->assignRole($learner, 'learner');

        [$course, $quizLesson] = $this->createPublishedQuizLessonCourse($tenant, $admin);
        $quiz = $this->createPublishedQuiz($tenant, $course, $quizLesson, [
            [
                'question_type' => 'multiple_choice',
                'prompt' => '<p>Which control is strongest?</p>',
                'points' => 5,
                'options' => [
                    ['label' => 'MFA', 'is_correct' => true],
                    ['label' => 'Password reuse', 'is_correct' => false],
                ],
            ],
        ]);

        $enrollment = Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'enrolled_by' => $admin->id,
            'enrolled_at' => now()->subHour(),
            'status' => EnrollmentStatus::Active,
            'progress_percent' => 0,
            'completed_lessons_count' => 0,
        ]);

        Sanctum::actingAs($learner);

        $startResponse = $this->postJson("/api/v1/quizzes/{$quiz->id}/attempts");

        $startResponse->assertCreated()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.quiz.id', $quiz->id)
            ->assertJsonMissingPath('data.questions.0.options.0.is_correct');

        $correctOptionId = (int) QuestionOption::query()
            ->where('question_id', $quiz->questions()->value('id'))
            ->where('is_correct', true)
            ->value('id');

        $attemptId = (int) $startResponse->json('data.id');
        $questionId = (int) $startResponse->json('data.questions.0.id');

        $submitResponse = $this->postJson("/api/v1/attempts/{$attemptId}/submit", [
            'answers' => [
                [
                    'question_id' => $questionId,
                    'answer_payload' => [
                        'selected_option_ids' => [$correctOptionId],
                    ],
                ],
            ],
        ]);

        $submitResponse->assertOk()
            ->assertJsonPath('data.status', 'graded')
            ->assertJsonPath('data.score', 100)
            ->assertJsonPath('data.passed', true)
            ->assertJsonPath('data.results_available', true)
            ->assertJsonPath('data.questions.0.options.0.is_correct', true);

        $this->assertDatabaseHas('quiz_attempts', [
            'id' => $attemptId,
            'status' => 'graded',
            'passed' => true,
        ]);

        $this->assertDatabaseHas('lesson_progress', [
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $quizLesson->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'status' => EnrollmentStatus::Completed->value,
            'progress_percent' => 100,
            'completed_lessons_count' => 1,
        ]);

        Event::assertDispatched(QuizCompleted::class, fn (QuizCompleted $event) => $event->attempt->id === $attemptId);
        Event::assertDispatched(CourseCompleted::class, fn (CourseCompleted $event) => $event->enrollment->id === $enrollment->id);
    }

    public function test_learner_submission_can_hide_results(): void
    {
        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $learner = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($admin, 'tenant_admin');
        $this->assignRole($learner, 'learner');

        [$course, $quizLesson] = $this->createPublishedQuizLessonCourse($tenant, $admin);
        $quiz = $this->createPublishedQuiz($tenant, $course, $quizLesson, [
            [
                'question_type' => 'true_false',
                'prompt' => '<p>Shared credentials are acceptable.</p>',
                'points' => 1,
                'options' => [
                    ['label' => 'True', 'is_correct' => false],
                    ['label' => 'False', 'is_correct' => true],
                ],
            ],
        ], [
            'show_results_to_learner' => false,
        ]);

        Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'enrolled_by' => $admin->id,
            'enrolled_at' => now()->subHour(),
            'status' => EnrollmentStatus::Active,
            'progress_percent' => 0,
            'completed_lessons_count' => 0,
        ]);

        Sanctum::actingAs($learner);

        $startResponse = $this->postJson("/api/v1/quizzes/{$quiz->id}/attempts");
        $attemptId = (int) $startResponse->json('data.id');
        $questionId = (int) $startResponse->json('data.questions.0.id');
        $correctOptionId = (int) $quiz->questions()->first()->options()->where('is_correct', true)->value('id');

        $submitResponse = $this->postJson("/api/v1/attempts/{$attemptId}/submit", [
            'answers' => [
                [
                    'question_id' => $questionId,
                    'answer_payload' => [
                        'selected_option_ids' => [$correctOptionId],
                    ],
                ],
            ],
        ]);

        $showResponse = $this->getJson("/api/v1/attempts/{$attemptId}");

        $submitResponse->assertOk()
            ->assertJsonPath('data.results_available', false)
            ->assertJsonMissingPath('data.questions.0.options.0.is_correct');

        $showResponse->assertOk()
            ->assertJsonPath('data.results_available', false)
            ->assertJsonMissingPath('data.questions.0.options.0.is_correct');
    }

    public function test_short_answer_attempt_is_marked_for_manual_grading(): void
    {
        Event::fake([QuizCompleted::class]);

        $tenant = Tenant::factory()->create();
        $this->seedRbac();

        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $learner = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->assignRole($admin, 'tenant_admin');
        $this->assignRole($learner, 'learner');

        [$course, $quizLesson] = $this->createPublishedQuizLessonCourse($tenant, $admin);
        $quiz = $this->createPublishedQuiz($tenant, $course, $quizLesson, [
            [
                'question_type' => 'short_answer',
                'prompt' => '<p>Explain why tenant isolation matters.</p>',
                'points' => 5,
                'options' => [],
            ],
        ]);

        $enrollment = Enrollment::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $learner->id,
            'course_id' => $course->id,
            'enrolled_by' => $admin->id,
            'enrolled_at' => now()->subHour(),
            'status' => EnrollmentStatus::Active,
            'progress_percent' => 0,
            'completed_lessons_count' => 0,
        ]);

        Sanctum::actingAs($learner);

        $startResponse = $this->postJson("/api/v1/quizzes/{$quiz->id}/attempts");
        $attemptId = (int) $startResponse->json('data.id');
        $questionId = (int) $startResponse->json('data.questions.0.id');

        $submitResponse = $this->postJson("/api/v1/attempts/{$attemptId}/submit", [
            'answers' => [
                [
                    'question_id' => $questionId,
                    'answer_payload' => [
                        'text' => 'Tenant isolation prevents data leakage between customers.',
                    ],
                ],
            ],
        ]);

        $submitResponse->assertOk()
            ->assertJsonPath('data.status', 'needs_grading')
            ->assertJsonPath('data.score', null)
            ->assertJsonPath('data.passed', null)
            ->assertJsonPath('data.results_available', false);

        $this->assertDatabaseHas('quiz_attempts', [
            'id' => $attemptId,
            'status' => 'needs_grading',
        ]);

        $this->assertDatabaseMissing('lesson_progress', [
            'enrollment_id' => $enrollment->id,
            'lesson_id' => $quizLesson->id,
            'status' => 'completed',
        ]);

        Event::assertDispatched(QuizCompleted::class, fn (QuizCompleted $event) => $event->attempt->id === $attemptId);
    }

    /**
     * @param  array<int, array{question_type: string, prompt: string, points: int, options: array<int, array{label: string, is_correct: bool}>}>  $questions
     * @param  array<string, mixed>  $overrides
     */
    private function createPublishedQuiz(
        Tenant $tenant,
        Course $course,
        Lesson $lesson,
        array $questions,
        array $overrides = [],
    ): Quiz {
        $quiz = Quiz::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'title' => 'Quiz Lesson',
            'description' => 'Attached quiz.',
            'pass_score' => 80,
            'time_limit_minutes' => 20,
            'attempts_allowed' => 2,
            'shuffle_questions' => false,
            'show_results_to_learner' => true,
            'status' => 'published',
        ], $overrides));

        foreach ($questions as $index => $questionData) {
            $question = QuizQuestion::query()->create([
                'quiz_id' => $quiz->id,
                'question_type' => $questionData['question_type'],
                'prompt' => $questionData['prompt'],
                'points' => $questionData['points'],
                'sort_order' => $index + 1,
            ]);

            foreach ($questionData['options'] as $optionIndex => $optionData) {
                QuestionOption::query()->create([
                    'question_id' => $question->id,
                    'label' => $optionData['label'],
                    'is_correct' => $optionData['is_correct'],
                    'sort_order' => $optionIndex + 1,
                ]);
            }
        }

        return $quiz->fresh(['questions.options']) ?? $quiz;
    }

    /**
     * @return array{0: Course, 1: Lesson}
     */
    private function createPublishedQuizLessonCourse(Tenant $tenant, User $creator): array
    {
        $course = Course::query()->create([
            'tenant_id' => $tenant->id,
            'title' => 'Quiz Course',
            'slug' => 'quiz-course',
            'status' => CourseStatus::Published,
            'visibility' => 'private',
            'created_by' => $creator->id,
        ]);

        $module = Module::query()->create([
            'course_id' => $course->id,
            'title' => 'Quiz Module',
            'sort_order' => 1,
        ]);

        $quizLesson = Lesson::query()->create([
            'module_id' => $module->id,
            'title' => 'Quiz Lesson',
            'type' => LessonType::Quiz,
            'sort_order' => 1,
        ]);

        return [$course, $quizLesson];
    }
}
