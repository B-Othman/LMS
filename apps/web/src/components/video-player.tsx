"use client";

interface VideoPlayerProps {
  src: string;
  title: string;
  poster?: string | null;
  onEnded?: () => void;
}

export function VideoPlayer({ src, title, poster, onEnded }: VideoPlayerProps) {
  return (
    <div className="overflow-hidden rounded-[28px] border border-neutral-200 bg-night-900 shadow-card">
      <video
        className="h-full w-full"
        controls
        preload="metadata"
        poster={poster ?? undefined}
        onEnded={onEnded}
      >
        <source src={src} />
        Your browser does not support HTML5 video playback for {title}.
      </video>
    </div>
  );
}
