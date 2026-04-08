import { Button } from "@securecy/ui";
import Link from "next/link";

export default function Home() {
  return (
    <main className="flex min-h-screen flex-col items-center justify-center gap-6">
      <h1 className="text-h1 text-primary-500">Securecy LMS — Admin</h1>
      <p className="text-body-lg text-neutral-500">Admin &amp; Instructor Portal</p>
      <Link href="/dashboard">
        <Button>Go to Dashboard</Button>
      </Link>
    </main>
  );
}
