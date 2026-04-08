import type { Metadata } from "next";
import "./globals.css";
import { Providers } from "@/components/providers";
import { WebAppFrame } from "@/components/web-app-frame";

export const metadata: Metadata = {
  title: "Securecy LMS",
  description: "Enterprise Learning Management System",
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
          <WebAppFrame>{children}</WebAppFrame>
        </Providers>
      </body>
    </html>
  );
}
