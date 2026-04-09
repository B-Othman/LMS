"use client";

import { Button } from "@securecy/ui";

const placeholders = [
  "{{learner_name}}",
  "{{course_title}}",
  "{{completion_date}}",
  "{{certificate_id}}",
  "{{verification_code}}",
];

interface PlaceholderInserterProps {
  onInsert: (placeholder: string) => void;
}

export function PlaceholderInserter({ onInsert }: PlaceholderInserterProps) {
  return (
    <div className="flex flex-wrap gap-2">
      {placeholders.map((placeholder) => (
        <Button
          key={placeholder}
          type="button"
          size="sm"
          variant="secondary"
          onClick={() => onInsert(placeholder)}
        >
          {placeholder}
        </Button>
      ))}
    </div>
  );
}
