export type QuizStatus = "draft" | "published";
export type QuizQuestionType =
  | "multiple_choice"
  | "multi_select"
  | "true_false"
  | "short_answer";
export type QuizAttemptStatus =
  | "in_progress"
  | "submitted"
  | "needs_grading"
  | "graded";

export interface QuestionOption {
  id: number;
  question_id: number;
  label: string;
  sort_order: number;
  is_correct?: boolean;
}

export interface QuizLatestAttemptSummary {
  id: number;
  status: QuizAttemptStatus;
  score: number | null;
  passed: boolean | null;
  started_at: string | null;
  submitted_at: string | null;
}

export interface QuizSummary {
  id: number;
  course_id: number | null;
  lesson_id: number | null;
  title: string;
  description: string | null;
  pass_score: number;
  time_limit_minutes: number | null;
  attempts_allowed: number;
  shuffle_questions: boolean;
  show_results_to_learner: boolean;
  status: QuizStatus;
  question_count: number;
  attempts_used?: number;
  attempts_remaining?: number | null;
  latest_attempt?: QuizLatestAttemptSummary | null;
  created_at: string;
  updated_at: string;
}

export interface QuizAttemptQuizSummary {
  id: number;
  course_id: number | null;
  lesson_id: number | null;
  title: string;
  description: string | null;
  pass_score: number;
  time_limit_minutes: number | null;
  attempts_allowed: number;
  shuffle_questions: boolean;
  show_results_to_learner: boolean;
  question_count: number;
}

export interface QuizQuestion {
  id: number;
  quiz_id: number;
  question_type: QuizQuestionType;
  prompt: string;
  explanation: string | null;
  points: number;
  sort_order: number;
  options: QuestionOption[];
  created_at: string;
  updated_at: string;
}

export interface Quiz extends QuizSummary {
  tenant_id: number;
  questions: QuizQuestion[];
}

export interface QuizAttemptAnswerPayload {
  selected_option_ids?: number[];
  text?: string;
}

export interface QuizAttemptQuestionAnswer {
  answer_payload: QuizAttemptAnswerPayload;
  is_correct: boolean | null;
  awarded_points: number | null;
}

export interface QuizAttemptQuestion {
  id: number;
  quiz_id: number;
  question_type: QuizQuestionType;
  prompt: string;
  explanation: string | null;
  points: number;
  sort_order: number;
  options: QuestionOption[];
  answer: QuizAttemptQuestionAnswer;
}

export interface QuizAttempt {
  id: number;
  quiz_id: number;
  enrollment_id: number;
  user_id: number;
  started_at: string;
  submitted_at: string | null;
  expires_at: string | null;
  score: number | null;
  total_points: number;
  passed: boolean | null;
  time_spent_seconds: number;
  status: QuizAttemptStatus;
  results_available: boolean;
  quiz: QuizAttemptQuizSummary;
  questions: QuizAttemptQuestion[];
  created_at: string;
  updated_at: string;
}

export interface QuizAttemptListItem {
  id: number;
  quiz_id: number;
  status: QuizAttemptStatus;
  score: number | null;
  total_points: number;
  passed: boolean | null;
  started_at: string;
  submitted_at: string | null;
  time_spent_seconds: number;
  quiz: {
    id: number;
    title: string;
    lesson_id: number | null;
    course_id: number | null;
    show_results_to_learner: boolean;
  } | null;
  course: {
    id: number;
    title: string;
    slug: string;
  } | null;
}

export interface CreateQuizPayload {
  course_id?: number;
  lesson_id?: number;
  title: string;
  description?: string | null;
  pass_score?: number;
  time_limit_minutes?: number | null;
  attempts_allowed?: number;
  shuffle_questions?: boolean;
  show_results_to_learner?: boolean;
  status?: QuizStatus;
}

export interface UpdateQuizPayload {
  title?: string;
  description?: string | null;
  pass_score?: number;
  time_limit_minutes?: number | null;
  attempts_allowed?: number;
  shuffle_questions?: boolean;
  show_results_to_learner?: boolean;
  status?: QuizStatus;
}

export interface QuizQuestionOptionInput {
  id?: number;
  label: string;
  is_correct: boolean;
  sort_order?: number;
}

export interface CreateQuizQuestionPayload {
  question_type: QuizQuestionType;
  prompt: string;
  explanation?: string | null;
  points?: number;
  sort_order?: number;
  options?: QuizQuestionOptionInput[];
}

export interface UpdateQuizQuestionPayload {
  question_type?: QuizQuestionType;
  prompt?: string;
  explanation?: string | null;
  points?: number;
  sort_order?: number;
  options?: QuizQuestionOptionInput[];
}

export interface SubmitQuizAttemptPayload {
  answers: Array<{
    question_id: number;
    answer_payload: QuizAttemptAnswerPayload;
  }>;
}
