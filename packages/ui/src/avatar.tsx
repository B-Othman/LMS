"use client";

import type { HTMLAttributes, ImgHTMLAttributes } from "react";

interface AvatarProps extends HTMLAttributes<HTMLDivElement> {
  src?: string | null;
  alt?: string;
  name?: string;
  size?: "sm" | "md" | "lg";
  imageProps?: Omit<ImgHTMLAttributes<HTMLImageElement>, "src" | "alt">;
}

const sizeClasses = {
  sm: "h-9 w-9 text-body-sm",
  md: "h-10 w-10 text-body-md",
  lg: "h-12 w-12 text-body-lg",
};

export function Avatar({
  src,
  alt,
  name = "",
  size = "md",
  className = "",
  imageProps,
  ...props
}: AvatarProps) {
  const initials = name
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((value) => value[0]?.toUpperCase() ?? "")
    .join("");

  return (
    <div
      className={`inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary-100 font-semibold text-primary-700 ${sizeClasses[size]} ${className}`}
      {...props}
    >
      {src ? (
        <img
          src={src}
          alt={alt ?? name}
          className="h-full w-full object-cover"
          {...imageProps}
        />
      ) : (
        <span>{initials || "S"}</span>
      )}
    </div>
  );
}
