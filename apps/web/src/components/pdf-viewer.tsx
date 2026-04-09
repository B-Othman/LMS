"use client";

interface PDFViewerProps {
  src: string;
  title: string;
  downloadUrl?: string | null;
}

export function PDFViewer({ src, title, downloadUrl }: PDFViewerProps) {
  return (
    <div>
      <div className="overflow-hidden rounded-[28px] border border-neutral-200 bg-white shadow-card">
        <iframe
          src={src}
          title={title}
          className="h-[72vh] w-full bg-neutral-50"
        />
      </div>

      <p className="mt-4 text-body-sm text-neutral-500">
        If the preview does not load,{" "}
        <a
          href={downloadUrl ?? src}
          target="_blank"
          rel="noreferrer"
          className="font-semibold text-primary-700 underline"
        >
          open the document in a new tab
        </a>
        .
      </p>
    </div>
  );
}
