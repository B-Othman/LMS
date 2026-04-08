import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Securecy LMS — Admin",
  description: "Securecy LMS Admin & Instructor Portal",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body className="font-sans antialiased">{children}</body>
    </html>
  );
}
