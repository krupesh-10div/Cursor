#!/usr/bin/env python3
"""
Fit the tools image onto a grey background image and export result plus a reference.

Usage (auto-discovery):
  python3 scripts/fit_tools_image.py

Usage (explicit):
  python3 scripts/fit_tools_image.py \
    --tools assets/input/tools.jpg \
    --bg assets/input/background.jpg \
    --out assets/output/fit.png \
    --ref assets/output/reference.png

Behavior:
- Scales the tools image to "contain" within the background size (preserving aspect ratio)
  and centers it on top of the background. This matches the sample where height fits
  and grey sidebars remain.
- Generates a reference side-by-side image with labels for quick visual comparison.
"""

from __future__ import annotations

import argparse
import os
import sys
from typing import Optional, Tuple

from PIL import Image, ImageDraw, ImageFont


DEFAULT_TOOLS_CANDIDATES = (
    "assets/input/tools.png",
    "assets/input/tools.jpg",
    "assets/input/tools.jpeg",
    "assets/input/tools.webp",
)

DEFAULT_BG_CANDIDATES = (
    "assets/input/background.png",
    "assets/input/background.jpg",
    "assets/input/background.jpeg",
    "assets/input/grey.png",
    "assets/input/grey.jpg",
)

DEFAULT_OUT = "assets/output/fit.png"
DEFAULT_REF = "assets/output/reference.png"


def find_first_existing(paths: Tuple[str, ...]) -> Optional[str]:
    for p in paths:
        if os.path.exists(p):
            return p
    return None


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Fit tools image onto grey background.")
    parser.add_argument("--tools", dest="tools_path", default=None, help="Path to tools image")
    parser.add_argument("--bg", dest="bg_path", default=None, help="Path to background image")
    parser.add_argument("--out", dest="out_path", default=DEFAULT_OUT, help="Path to output composite image")
    parser.add_argument("--ref", dest="ref_path", default=DEFAULT_REF, help="Path to output reference image")
    parser.add_argument(
        "--mode",
        choices=["contain", "cover"],
        default="contain",
        help="Scaling mode: contain (no crop) or cover (crop to fill).",
    )
    return parser.parse_args()


def load_image(path: str) -> Image.Image:
    try:
        img = Image.open(path).convert("RGBA")
        return img
    except Exception as exc:
        raise RuntimeError(f"Failed to open image '{path}': {exc}")


def ensure_dirs_for(path: str) -> None:
    directory = os.path.dirname(path)
    if directory and not os.path.exists(directory):
        os.makedirs(directory, exist_ok=True)


def compute_resize_contain(src_size: Tuple[int, int], dst_size: Tuple[int, int]) -> Tuple[int, int]:
    src_w, src_h = src_size
    dst_w, dst_h = dst_size
    scale = min(dst_w / src_w, dst_h / src_h)
    return max(1, int(round(src_w * scale))), max(1, int(round(src_h * scale)))


def compute_resize_cover(src_size: Tuple[int, int], dst_size: Tuple[int, int]) -> Tuple[int, int]:
    src_w, src_h = src_size
    dst_w, dst_h = dst_size
    scale = max(dst_w / src_w, dst_h / src_h)
    return max(1, int(round(src_w * scale))), max(1, int(round(src_h * scale)))


def paste_centered(base: Image.Image, overlay: Image.Image) -> None:
    bx, by = base.size
    ox, oy = overlay.size
    x = (bx - ox) // 2
    y = (by - oy) // 2
    base.alpha_composite(overlay, (x, y))


def draw_label(img: Image.Image, text: str) -> None:
    draw = ImageDraw.Draw(img)
    padding = 10
    # Try to load a default font; fall back to basic if unavailable
    try:
        font = ImageFont.truetype("DejaVuSans.ttf", 22)
    except Exception:
        font = ImageFont.load_default()
    text_w, text_h = draw.textsize(text, font=font)
    rect_w = text_w + padding * 2
    rect_h = text_h + padding
    draw.rectangle([(0, 0), (rect_w, rect_h)], fill=(0, 0, 0, 160))
    draw.text((padding, padding // 2), text, fill=(255, 255, 255, 230), font=font)


def make_reference(tools: Image.Image, bg: Image.Image, result: Image.Image) -> Image.Image:
    # Normalize heights for side-by-side view
    target_h = max(tools.height, bg.height, result.height)
    def scale_to_h(img: Image.Image) -> Image.Image:
        scale = target_h / img.height
        new_w = int(round(img.width * scale))
        return img.resize((new_w, target_h), Image.LANCZOS)

    tools_s = scale_to_h(tools.convert("RGBA"))
    bg_s = scale_to_h(bg.convert("RGBA"))
    res_s = scale_to_h(result.convert("RGBA"))

    draw_label(tools_s, "Tools (source)")
    draw_label(bg_s, "Grey background")
    draw_label(res_s, "Composite result")

    total_w = tools_s.width + bg_s.width + res_s.width
    canvas = Image.new("RGBA", (total_w, target_h), (0, 0, 0, 0))
    x = 0
    for part in (tools_s, bg_s, res_s):
        canvas.alpha_composite(part, (x, 0))
        x += part.width
    return canvas


def main() -> int:
    args = parse_args()

    tools_path = args.tools_path or find_first_existing(DEFAULT_TOOLS_CANDIDATES)
    bg_path = args.bg_path or find_first_existing(DEFAULT_BG_CANDIDATES)

    if not tools_path or not os.path.exists(tools_path):
        print(
            "[image-fit] Tools image not found. Place it at one of: \n  - "
            + "\n  - ".join(DEFAULT_TOOLS_CANDIDATES),
            file=sys.stderr,
        )
        return 2

    if not bg_path or not os.path.exists(bg_path):
        print(
            "[image-fit] Background image not found. Place it at one of: \n  - "
            + "\n  - ".join(DEFAULT_BG_CANDIDATES),
            file=sys.stderr,
        )
        return 2

    tools_img = load_image(tools_path)
    bg_img = load_image(bg_path)

    # Prepare canvas from background (ensure has alpha channel)
    canvas = bg_img.convert("RGBA")

    # Resize tools according to mode
    if args.mode == "contain":
        new_size = compute_resize_contain(tools_img.size, canvas.size)
    else:
        new_size = compute_resize_cover(tools_img.size, canvas.size)

    tools_resized = tools_img.resize(new_size, Image.LANCZOS)

    # If cover mode produced larger image than canvas, center-crop overlay to canvas
    if tools_resized.width > canvas.width or tools_resized.height > canvas.height:
        left = (tools_resized.width - canvas.width) // 2
        top = (tools_resized.height - canvas.height) // 2
        right = left + canvas.width
        bottom = top + canvas.height
        tools_resized = tools_resized.crop((left, top, right, bottom))

    paste_centered(canvas, tools_resized)

    ensure_dirs_for(args.out_path)
    canvas.save(args.out_path)

    # Build reference composite
    reference = make_reference(tools_img, bg_img, canvas)
    ensure_dirs_for(args.ref_path)
    reference.save(args.ref_path)

    print(
        f"[image-fit] Wrote composite to {args.out_path} and reference to {args.ref_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

