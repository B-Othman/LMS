"use client";

import { useEffect, useMemo, useRef, useState } from "react";

interface QuizTimerProps {
  expiresAt: string | null;
  onExpire: () => void;
}

export function QuizTimer({ expiresAt, onExpire }: QuizTimerProps) {
  const expiredRef = useRef(false);
  const [secondsRemaining, setSecondsRemaining] = useState(() =>
    calculateSecondsRemaining(expiresAt),
  );

  useEffect(() => {
    expiredRef.current = false;
    setSecondsRemaining(calculateSecondsRemaining(expiresAt));
  }, [expiresAt]);

  useEffect(() => {
    if (!expiresAt) {
      return;
    }

    const interval = window.setInterval(() => {
      const nextSecondsRemaining = calculateSecondsRemaining(expiresAt);
      setSecondsRemaining(nextSecondsRemaining);

      if (nextSecondsRemaining <= 0 && !expiredRef.current) {
        expiredRef.current = true;
        onExpire();
      }
    }, 1000);

    return () => {
      window.clearInterval(interval);
    };
  }, [expiresAt, onExpire]);

  const toneClassName = useMemo(() => {
    if (secondsRemaining <= 60) {
      return "border-warning-200 bg-warning-50 text-warning-700";
    }

    return "border-primary-200 bg-primary-50 text-primary-700";
  }, [secondsRemaining]);

  if (!expiresAt) {
    return null;
  }

  return (
    <div className={`inline-flex items-center rounded-full border px-3 py-1.5 text-body-sm font-semibold ${toneClassName}`}>
      Time Left: {formatCountdown(secondsRemaining)}
    </div>
  );
}

function calculateSecondsRemaining(expiresAt: string | null): number {
  if (!expiresAt) {
    return 0;
  }

  return Math.max(0, Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000));
}

function formatCountdown(value: number): string {
  const minutes = Math.floor(value / 60);
  const seconds = value % 60;

  return `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
}
