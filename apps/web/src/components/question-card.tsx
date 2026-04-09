"use client";

import type { QuizAttemptAnswerPayload, QuizAttemptQuestion } from "@securecy/types";

interface QuestionCardProps {
  question: QuizAttemptQuestion;
  index: number;
  value: QuizAttemptAnswerPayload;
  disabled?: boolean;
  onChange: (value: QuizAttemptAnswerPayload) => void;
}

export function QuestionCard({
  question,
  index,
  value,
  disabled = false,
  onChange,
}: QuestionCardProps) {
  const selectedOptionIds = value.selected_option_ids ?? [];

  return (
    <div className="rounded-[28px] border border-neutral-200 bg-white p-5 shadow-card">
      <div className="flex flex-wrap items-center gap-2">
        <span className="rounded-full bg-primary-50 px-2.5 py-1 text-body-sm font-semibold text-primary-700">
          Question {index + 1}
        </span>
        <span className="rounded-full bg-neutral-100 px-2.5 py-1 text-body-sm font-semibold text-neutral-600">
          {question.points} pts
        </span>
      </div>

      <div
        className="mt-4 text-body-lg font-semibold leading-7 text-night-900"
        dangerouslySetInnerHTML={{ __html: question.prompt }}
      />

      {question.question_type === "short_answer" ? (
        <textarea
          rows={4}
          value={value.text ?? ""}
          disabled={disabled}
          onChange={(event) => onChange({ text: event.target.value })}
          className="mt-5 w-full rounded-2xl border border-neutral-300 px-4 py-3 text-body-md text-night-900 placeholder:text-neutral-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-neutral-100"
          placeholder="Write your answer here"
        />
      ) : (
        <div className="mt-5 space-y-3">
          {question.options.map((option) => {
            const isChecked = selectedOptionIds.includes(option.id);

            return (
              <label
                key={option.id}
                className={`flex cursor-pointer items-start gap-3 rounded-2xl border px-4 py-3 transition-colors ${
                  isChecked
                    ? "border-primary-300 bg-primary-50"
                    : "border-neutral-200 bg-neutral-50 hover:border-primary-200 hover:bg-white"
                } ${disabled ? "cursor-not-allowed opacity-70" : ""}`}
              >
                <input
                  type={question.question_type === "multi_select" ? "checkbox" : "radio"}
                  name={`question-${question.id}`}
                  value={option.id}
                  checked={isChecked}
                  disabled={disabled}
                  onChange={() => {
                    if (question.question_type === "multi_select") {
                      onChange({
                        selected_option_ids: isChecked
                          ? selectedOptionIds.filter((id) => id !== option.id)
                          : [...selectedOptionIds, option.id],
                      });
                      return;
                    }

                    onChange({ selected_option_ids: [option.id] });
                  }}
                  className="mt-1 h-4 w-4 shrink-0"
                />
                <span className="min-w-0 text-body-md text-night-900">{option.label}</span>
              </label>
            );
          })}
        </div>
      )}
    </div>
  );
}
