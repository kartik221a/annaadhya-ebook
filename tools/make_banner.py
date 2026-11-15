#!/usr/bin/env python3
"""Generate a wide abstract hero banner for the store homepage (no text - headings come from WP blocks)."""
import math
import random
from PIL import Image, ImageDraw, ImageFilter

W, H = 1920, 640
img = Image.new("RGB", (W, H))
d = ImageDraw.Draw(img)

# deep navy -> indigo -> violet vertical-diagonal gradient
stops = [(0, (16, 26, 48)), (0.55, (42, 45, 110)), (1.0, (94, 45, 150))]
for y in range(H):
    for seg in range(len(stops) - 1):
        if stops[seg][0] <= y / H <= stops[seg + 1][0]:
            t = (y / H - stops[seg][0]) / (stops[seg + 1][0] - stops[seg][0])
            c1, c2 = stops[seg][1], stops[seg + 1][1]
            col = tuple(int(c1[i] + (c2[i] - c1[i]) * t) for i in range(3))
            d.line([(0, y), (W, y)], fill=col)
            break

# soft glowing orbs
random.seed(7)
glow = Image.new("RGB", (W, H), (0, 0, 0))
gd = ImageDraw.Draw(glow)
for cx, cy, r, col in [
    (1560, 140, 260, (59, 86, 195)),
    (240, 520, 220, (44, 108, 148)),
    (1100, 560, 180, (120, 60, 170)),
]:
    for i in range(r, 0, -6):
        a = int(60 * (i / r))
        c = tuple(min(255, v + a) for v in col)
        gd.ellipse([cx - i, cy - i, cx + i, cy + i], fill=c)
glow = glow.filter(ImageFilter.GaussianBlur(40))
img = Image.blend(img, glow, 0.35)
d = ImageDraw.Draw(img)

# abstract book spines, bottom right - tilted rounded bars with varying widths/tones
def rot_rect(draw, cx, cy, w, h, angle, fill):
    pts = []
    for dx, dy in [(-w/2, -h/2), (w/2, -h/2), (w/2, h/2), (-w/2, h/2)]:
        a = math.radians(angle)
        px = dx * math.cos(a) - dy * math.sin(a) + cx
        py = dx * math.sin(a) + dy * math.cos(a) + cy
        pts.append((px, py))
    draw.polygon(pts, fill=fill)

tones = [(232, 230, 238), (203, 205, 225), (172, 178, 210), (142, 150, 190), (226, 214, 235), (196, 186, 222)]
x = 1210
random.seed(3)
heights = [300, 380, 330, 420, 310, 360]
for i, (hh, tone) in enumerate(zip(heights, tones)):
    w = random.choice([46, 60, 54, 70, 50, 64])
    angle = random.uniform(-9, -3)
    rot_rect(d, x + w / 2, H - hh / 2 - 40, w, hh, angle, tone)
    # spine accent line
    rot_rect(d, x + w / 2 - 6, H - hh / 2 - 40, 4, hh - 60, angle, (16, 26, 48))
    x += w + 26

# thin elegant rules top-left
d.line([(90, 120), (430, 120)], fill=(255, 255, 255), width=2)
d.line([(90, 132), (330, 132)], fill=(214, 204, 235), width=2)

img.save("./hero-banner.png")
print("banner saved", img.size)
