export type QuestionType = "multiple_choice" | "true_false" | "short_answer";

export interface Quiz {
  id: number;
  lessonId: number;
  title: string;
  description: string | null;
  passingScore: number;
  timeLimitMinutes: number | null;
  maxAttempts: number | null;
  createdAt: string;
  updatedAt: string;
}

export interface QuizQuestion {
  id: number;
  quizId: number;
  type: QuestionType;
  body: string;
  options: QuizOption[] | null;
  correctAnswer: string;
  points: number;
  sortOrder: number;
  createdAt: string;
  updatedAt: string;
}

export interface QuizOption {
  key: string;
  label: string;
}

export interface QuizAttempt {
  id: number;
  quizId: number;
  userId: number;
  score: number;
  passed: boolean;
  startedAt: string;
  completedAt: string | null;
  answers: QuizAnswer[];
  createdAt: string;
  updatedAt: string;
}

export interface QuizAnswer {
  questionId: number;
  answer: string;
  isCorrect: boolean;
  pointsAwarded: number;
}
