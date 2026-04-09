import type {
  CreateQuizPayload,
  CreateQuizQuestionPayload,
  Quiz,
  UpdateQuizPayload,
  UpdateQuizQuestionPayload,
} from "@securecy/types";

import { api } from "./api";

export async function fetchQuiz(quizId: number | string): Promise<Quiz> {
  const response = await api.get<Quiz>(`/quizzes/${quizId}`);

  if (!response.data) {
    throw new Error("The quiz response was empty.");
  }

  return response.data;
}

export async function createQuiz(payload: CreateQuizPayload): Promise<Quiz> {
  const response = await api.post<Quiz>("/quizzes", payload);

  if (!response.data) {
    throw new Error("The quiz creation response was empty.");
  }

  return response.data;
}

export async function updateQuiz(
  quizId: number | string,
  payload: UpdateQuizPayload,
): Promise<Quiz> {
  const response = await api.put<Quiz>(`/quizzes/${quizId}`, payload);

  if (!response.data) {
    throw new Error("The quiz update response was empty.");
  }

  return response.data;
}

export async function addQuizQuestion(
  quizId: number | string,
  payload: CreateQuizQuestionPayload,
): Promise<Quiz> {
  const response = await api.post<Quiz>(`/quizzes/${quizId}/questions`, payload);

  if (!response.data) {
    throw new Error("The question creation response was empty.");
  }

  return response.data;
}

export async function updateQuizQuestion(
  questionId: number | string,
  payload: UpdateQuizQuestionPayload,
): Promise<Quiz> {
  const response = await api.put<Quiz>(`/questions/${questionId}`, payload);

  if (!response.data) {
    throw new Error("The question update response was empty.");
  }

  return response.data;
}

export async function deleteQuizQuestion(
  questionId: number | string,
): Promise<Quiz> {
  const response = await api.delete<Quiz>(`/questions/${questionId}`);

  if (!response.data) {
    throw new Error("The question deletion response was empty.");
  }

  return response.data;
}

export async function reorderQuizQuestions(
  quizId: number | string,
  questions: Array<{ id: number; sort_order: number }>,
): Promise<Quiz> {
  const response = await api.post<Quiz>(`/quizzes/${quizId}/questions/reorder`, {
    questions,
  });

  if (!response.data) {
    throw new Error("The question reorder response was empty.");
  }

  return response.data;
}
