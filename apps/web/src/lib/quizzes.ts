import type {
  QuizAttempt,
  QuizAttemptListItem,
  SubmitQuizAttemptPayload,
} from "@securecy/types";

import { api } from "./api";

export async function startQuizAttempt(quizId: number | string): Promise<QuizAttempt> {
  const response = await api.post<QuizAttempt>(`/quizzes/${quizId}/attempts`);

  if (!response.data) {
    throw new Error("The quiz attempt response was empty.");
  }

  return response.data;
}

export async function submitQuizAttempt(
  attemptId: number | string,
  payload: SubmitQuizAttemptPayload,
): Promise<QuizAttempt> {
  const response = await api.post<QuizAttempt>(`/attempts/${attemptId}/submit`, payload);

  if (!response.data) {
    throw new Error("The quiz submission response was empty.");
  }

  return response.data;
}

export async function fetchQuizAttempt(attemptId: number | string): Promise<QuizAttempt> {
  const response = await api.get<QuizAttempt>(`/attempts/${attemptId}`);

  if (!response.data) {
    throw new Error("The quiz attempt detail response was empty.");
  }

  return response.data;
}

export async function fetchMyQuizAttempts(): Promise<QuizAttemptListItem[]> {
  const response = await api.get<QuizAttemptListItem[]>("/my/attempts");

  return response.data ?? [];
}
