import type { ReactNode } from "react";

import { Card } from "./card";

interface AuthPageShellProps {
  eyebrow?: string;
  title: string;
  description: string;
  children: ReactNode;
  footer?: ReactNode;
}

export function AuthPageShell({
  eyebrow = "Securecy",
  title,
  description,
  children,
  footer,
}: AuthPageShellProps) {
  return (
    <main className="flex min-h-screen items-center justify-center bg-primary-50 px-4 py-10 sm:px-6">
      <div className="w-full max-w-md">
        <div className="mb-6 text-center">
          <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-500 text-xl font-bold text-white shadow-card">
            S
          </div>
          <p className="mt-4 text-overline uppercase tracking-[0.28em] text-primary-700">{eyebrow}</p>
          <h1 className="mt-3 text-h2 text-night-900">{title}</h1>
          <p className="mt-2 text-body-lg text-neutral-600">{description}</p>
        </div>

        <Card padded={false} className="p-6 sm:p-8">{children}</Card>

        {footer ? <div className="mt-5 text-center text-body-md text-neutral-600">{footer}</div> : null}
      </div>
    </main>
  );
}
