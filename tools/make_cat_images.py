#!/usr/bin/env python3
"""Generate abstract category tile images for the store (name shown by Woo label, so no text needed)."""
import math
import random
from PIL import Image, ImageDraw, ImageFilter

OUT = "./cat-images"
import os
os.makedirs(OUT, exist_ok=True)

W, H = 600, 420

CATS = {
    "cat-technology": ([(0, (0, 60, 110)), (1.0, (3, 120, 190))], "#7fd4ff"),
    "cat-business": ([(0, (16, 46, 38)), (1.0, (42, 157, 143))], "#d9f2ec"),
    "cat-self-help": ([(0, (64, 9, 105)), (1.0, (131, 33, 97))], "#f0c4ee"),
    "cat-fiction": ([(0, (30, 30, 46)), (1.0, (94, 45, 150))], "#d9c6f2"),
}


def rot_rect(d, cx, cy, w, h, angle, fill):
    pts = []
    for dx, dy in [(-w/2, -h/2), (w/2, -h/2), (w/2, h/2), (-w/2, h/2)]:
        a = math.radians(angle)
        pts.append((dx*math.cos(a)-dy*math.sin(a)+cx, dx*math.sin(a)+dy*math.cos(a)+cy))
    d.polygon(pts, fill=fill)


for slug, (stops, accent) in CATS.items():
    img = Image.new("RGB", (W, H))
    d = ImageDraw.Draw(img)
    for y in range(H):
        t = y / H
        c1, c2 = stops[0][1], stops[1][1]
        col = tuple(int(c1[i] + (c2[i]-c1[i])*t) for i in range(3))
        d.line([(0, y), (W, y)], fill=col)
    # glow
    glow = Image.new("RGB", (W, H), (0, 0, 0))
    gd = ImageDraw.Draw(glow)
    gd.ellipse([W-220, -80, W+140, 280], fill=accent)
    glow = glow.filter(ImageFilter.GaussianBlur(60))
    img = Image.blend(img, glow, 0.25)
    d = ImageDraw.Draw(img)
    # three tilted bars (book spines motif)
    random.seed(hash(slug) % 1000)
    x = 380
    for hh, tone in [(240, (255, 255, 255)), (300, (226, 226, 238)), (270, (198, 198, 218))]:
        w = random.choice([34, 42, 38])
        rot_rect(d, x + w/2, H - hh/2 - 30, w, hh, random.uniform(-8, -2), tone)
        x += w + 18
    d.line([(40, 60), (200, 60)], fill=accent, width=3)
    img.save(f"{OUT}/{slug}.png")
    print("saved", slug)
print("DONE")
