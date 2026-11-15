#!/usr/bin/env python3
"""Generate sample ebook cover images and sample PDF files for the demo store."""
import os
from PIL import Image, ImageDraw, ImageFont
from fpdf import FPDF

OUT_COVERS = "./covers"
OUT_PDFS = "./sample-pdfs"
os.makedirs(OUT_COVERS, exist_ok=True)
os.makedirs(OUT_PDFS, exist_ok=True)

FONT_BOLD = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"
FONT_REG = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"

BOOKS = [
    ("wordpress-mastery", "WordPress Mastery", "Build Sites That Sell", "Technology", "#1d3557", "#457b9d"),
    ("lean-side-hustle", "The Lean Side Hustle", "From Zero to First Sale", "Business", "#264653", "#2a9d8f"),
    ("digital-discipline", "Digital Discipline", "Focus in the Age of Noise", "Self-Help", "#3d2645", "#832161"),
    ("echoes-himalayas", "Echoes of the Himalayas", "A Novel", "Fiction", "#2f3e46", "#606c38"),
    ("ai-for-everyone", "AI for Everyone", "A Practical Guide", "Technology", "#03045e", "#0077b6"),
    ("idea-to-launch", "From Idea to Launch", "30 Days to Your First Product", "Business", "#6a040f", "#e85d04"),
]

W, H = 600, 900


def make_cover(slug, title, subtitle, category, c1, c2):
    img = Image.new("RGB", (W, H))
    d = ImageDraw.Draw(img)
    # vertical gradient background from c1 to c2
    r1, g1, b1 = tuple(int(c1[i:i + 2], 16) for i in (1, 3, 5))
    r2, g2, b2 = tuple(int(c2[i:i + 2], 16) for i in (1, 3, 5))
    for y in range(H):
        t = y / H
        d.line([(0, y), (W, y)], fill=(int(r1 + (r2 - r1) * t), int(g1 + (g2 - g1) * t), int(b1 + (b2 - b1) * t)))
    # decorative circles
    d.ellipse([W - 260, -120, W + 120, 260], outline=(255, 255, 255, 60), width=6)
    d.ellipse([-140, H - 260, 140, H + 140], outline=(255, 255, 255), width=6)
    f_title = ImageFont.truetype(FONT_BOLD, 58)
    f_sub = ImageFont.truetype(FONT_REG, 30)
    f_cat = ImageFont.truetype(FONT_BOLD, 26)
    f_press = ImageFont.truetype(FONT_REG, 22)
    # category tag
    d.rounded_rectangle([50, 70, 50 + d.textlength(category, font=f_cat) + 40, 130], radius=18, fill=(255, 255, 255))
    d.text((70, 86), category, font=f_cat, fill=c1)
    # wrapped title
    words, lines, cur = title.split(), [], ""
    for w_ in words:
        trial = (cur + " " + w_).strip()
        if d.textlength(trial, font=f_title) > W - 100:
            lines.append(cur)
            cur = w_
        else:
            cur = trial
    lines.append(cur)
    y = 330
    for line in lines:
        d.text((50, y), line, font=f_title, fill=(255, 255, 255))
        y += 74
    d.text((50, y + 24), subtitle, font=f_sub, fill=(230, 230, 230))
    d.line([(50, H - 180), (220, H - 180)], fill=(255, 255, 255), width=3)
    d.text((50, H - 150), "Annaadhya Press  |  Sample Edition", font=f_press, fill=(240, 240, 240))
    img.save(f"{OUT_COVERS}/{slug}.png")


def make_pdf(slug, title, subtitle, author_note):
    pdf = FPDF()
    pdf.set_auto_page_break(auto=True, margin=20)
    pdf.add_page()
    pdf.set_fill_color(29, 53, 87)
    pdf.rect(0, 0, 210, 60, "F")
    pdf.set_text_color(255, 255, 255)
    pdf.set_y(20)
    pdf.set_font("Helvetica", "B", 26)
    pdf.cell(0, 14, title, align="C")
    pdf.set_font("Helvetica", "", 14)
    pdf.cell(0, 10, subtitle, align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.set_text_color(40, 40, 40)
    pdf.set_y(80)
    pdf.set_font("Helvetica", "B", 15)
    pdf.cell(0, 10, "Sample Chapter 1", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("Helvetica", "", 11)
    para = ("This is a placeholder sample file generated for the Annaadhya eBooks demo store. "
            "It lets you test the complete buying flow: add to cart, checkout, and the instant "
            "download of a purchased file. Replace this PDF with your real ebook file before launch.") + author_note
    pdf.multi_cell(0, 6, para)
    pdf.ln(4)
    pdf.multi_cell(0, 6, ("When a customer completes checkout, WooCommerce delivers exactly this file "
                          "as a secure download link. Digital products are marked as virtual and "
                          "downloadable, so no shipping is charged and delivery is automatic."))
    pdf.set_y(-30)
    pdf.set_font("Helvetica", "I", 9)
    pdf.set_text_color(120, 120, 120)
    pdf.cell(0, 6, "Annaadhya Press - sample content for store testing", align="C")
    pdf.output(f"{OUT_PDFS}/{slug}.pdf")


for slug, title, subtitle, cat, c1, c2 in BOOKS:
    make_cover(slug, title, subtitle, cat, c1, c2)
    make_pdf(slug, title, subtitle, "")
    print(f"done: {slug}")

print("ALL DONE")
