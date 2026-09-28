import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "BuktiTagih AI",
  description: "Platform Kecerdasan Bukti Digital untuk Korban Penagihan Pinjol",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="id">
      <body>{children}</body>
    </html>
  );
}
