import type { Metadata } from "next";
import "./globals.css";
import { Providers } from "@/components/providers";
import { AdminAppFrame } from "@/components/admin-app-frame";

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
      <body className="font-sans antialiased">
        <Providers>
          <AdminAppFrame>{children}</AdminAppFrame>
        </Providers>
      </body>
    </html>
  );
}
