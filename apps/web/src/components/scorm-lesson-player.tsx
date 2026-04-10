"use client";

import type { ScormLaunchResult } from "@securecy/types";
import { useEffect, useRef, useState } from "react";
import { Button, EmptyState } from "@securecy/ui";
import { api } from "@/lib/api";

interface ScormLessonPlayerProps {
  packageVersionId: number;
  onExit: () => void;
  onCompleted?: () => void;
}

type PlayerState = "loading" | "ready" | "error";

export function ScormLessonPlayer({ packageVersionId, onExit, onCompleted }: ScormLessonPlayerProps) {
  const [state, setState] = useState<PlayerState>("loading");
  const [launchUrl, setLaunchUrl] = useState<string | null>(null);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const iframeRef = useRef<HTMLIFrameElement>(null);
  const completedRef = useRef(false);

  useEffect(() => {
    let cancelled = false;

    api
      .post<ScormLaunchResult>(`/packages/${packageVersionId}/launch`)
      .then((res) => {
        if (cancelled) return;
        const data = res.data;
        if (!data?.launch_url) throw new Error("No launch URL returned.");
        setLaunchUrl(data.launch_url);
        setState("ready");
      })
      .catch((err: unknown) => {
        if (cancelled) return;
        const msg = err instanceof Error ? err.message : "Could not launch SCORM content.";
        setErrorMsg(msg);
        setState("error");
      });

    return () => {
      cancelled = true;
    };
  }, [packageVersionId]);

  // Listen for a message from the player iframe that signals LMSFinish was called.
  // The player page posts `{ type: "scorm:finish" }` after its LMSFinish runs.
  useEffect(() => {
    function handleMessage(event: MessageEvent) {
      if (event.data?.type === "scorm:finish" && !completedRef.current) {
        completedRef.current = true;
        onCompleted?.();
      }
    }

    window.addEventListener("message", handleMessage);
    return () => window.removeEventListener("message", handleMessage);
  }, [onCompleted]);

  if (state === "error") {
    return (
      <EmptyState
        title="SCORM content unavailable"
        description={errorMsg ?? "The content could not be launched."}
        action={<Button type="button" variant="secondary" onClick={onExit}>Exit</Button>}
      />
    );
  }

  return (
    <div className="flex flex-col">
      {/* Toolbar */}
      <div className="flex items-center justify-between border-b border-neutral-200 bg-neutral-50 px-4 py-2">
        <p className="text-body-sm font-medium text-neutral-600">
          SCORM 1.2 Content
        </p>
        <Button type="button" variant="secondary" size="sm" onClick={onExit}>
          Exit
        </Button>
      </div>

      {/* Player frame */}
      <div className="relative" style={{ height: "600px" }}>
        {state === "loading" ? (
          <div className="flex h-full items-center justify-center bg-neutral-50">
            <div className="flex flex-col items-center gap-3">
              <svg className="h-8 w-8 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
              <p className="text-body-sm text-neutral-500">Launching content…</p>
            </div>
          </div>
        ) : null}

        {launchUrl ? (
          <iframe
            ref={iframeRef}
            src={launchUrl}
            className="absolute inset-0 h-full w-full border-0"
            title="SCORM Content"
            allow="fullscreen"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-pointer-lock allow-downloads"
          />
        ) : null}
      </div>
    </div>
  );
}
