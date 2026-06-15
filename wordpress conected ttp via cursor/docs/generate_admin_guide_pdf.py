"""Generate TTP Admin Quick Guide PDF with simple UI illustrations."""

from __future__ import annotations

import os
from pathlib import Path

from fpdf import FPDF
from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent
IMG_DIR = ROOT / "guide-images"
PDF_PATH = ROOT / "TTP-Admin-Quick-Guide.pdf"

BRAND = (233, 30, 99)
DARK = (30, 41, 59)
MUTED = (100, 116, 139)
WHITE = (255, 255, 255)
LIGHT = (248, 250, 252)
PANEL = (252, 228, 236)


def font(size: int, bold: bool = False):
    try:
        name = "arialbd.ttf" if bold else "arial.ttf"
        return ImageFont.truetype(name, size)
    except OSError:
        return ImageFont.load_default()


def draw_sidebar(draw: ImageDraw.ImageDraw, items: list[str], active: int = 0) -> None:
    draw.rectangle((0, 0, 180, 420), fill=(241, 245, 249))
    draw.text((14, 14), "WordPress Admin", fill=DARK, font=font(13, True))
    y = 48
    for i, item in enumerate(items):
        if i == active:
            draw.rectangle((8, y - 4, 172, y + 22), fill=WHITE)
            draw.text((16, y), item, fill=BRAND, font=font(12, True))
        else:
            draw.text((16, y), item, fill=MUTED, font=font(12))
        y += 30


def save_img(name: str, img: Image.Image) -> str:
    IMG_DIR.mkdir(parents=True, exist_ok=True)
    path = IMG_DIR / name
    img.save(path, "PNG")
    return str(path)


def img_menu_overview() -> str:
    img = Image.new("RGB", (900, 420), WHITE)
    d = ImageDraw.Draw(img)
    items = [
        "Affiliate Hub",
        "  Dashboard",
        "  Affiliate Links",
        "  Create Notification",
        "  Site Popup",
        "  Notifications",
        "TTP Dashboard",
        "  Email Campaigns",
        "WooCommerce",
        "  Marketing > Coupons",
    ]
    draw_sidebar(d, items, active=0)
    d.text((210, 24), "Where to find everything", fill=DARK, font=font(22, True))
    boxes = [
        ("Affiliate Links", "Affiliate Hub → Affiliate Links"),
        ("Notifications", "Affiliate Hub → Create Notification"),
        ("Site Popup", "Affiliate Hub → Site Popup"),
        ("Coupons", "WooCommerce → Marketing → Coupons"),
        ("Klaviyo", "TTP Dashboard → Email Campaigns"),
    ]
    y = 70
    for title, path in boxes:
        d.rounded_rectangle((210, y, 860, y + 52), radius=10, fill=LIGHT, outline=(226, 232, 240))
        d.text((228, y + 8), title, fill=BRAND, font=font(14, True))
        d.text((228, y + 28), path, fill=MUTED, font=font(12))
        y += 62
    return save_img("01-menu-overview.png", img)


def img_affiliate_links() -> str:
    img = Image.new("RGB", (900, 420), WHITE)
    d = ImageDraw.Draw(img)
    draw_sidebar(d, ["Affiliate Hub", "Affiliate Links", "Referral Program"], active=1)
    d.text((210, 24), "Create affiliate / referral link", fill=DARK, font=font(20, True))
    d.rounded_rectangle((210, 70, 860, 380), radius=12, fill=LIGHT, outline=(226, 232, 240))
    d.text((230, 92), "1. Search user by name or email", fill=DARK, font=font(13, True))
    d.rounded_rectangle((230, 118, 620, 148), radius=6, fill=WHITE, outline=(203, 213, 225))
    d.text((240, 126), "Type name or email...", fill=MUTED, font=font(12))
    d.text((230, 168), "2. Enable referral access + set commission %", fill=DARK, font=font(13, True))
    d.text((250, 198), "[x] Enable referral program", fill=DARK, font=font(12))
    d.text((250, 222), "Commission: 10%", fill=DARK, font=font(12))
    d.rounded_rectangle((230, 258, 380, 292), radius=6, fill=BRAND)
    d.text((252, 268), "Generate link", fill=WHITE, font=font(12, True))
    d.text((230, 312), "3. Copy link: thetoppercentile.co.in/?ref=CODE", fill=MUTED, font=font(12))
    d.rounded_rectangle((400, 258, 820, 292), radius=6, fill=WHITE, outline=(203, 213, 225))
    d.text((412, 268), "https://thetoppercentile.co.in/?ref=ABC123", fill=DARK, font=font(11))
    return save_img("02-affiliate-links.png", img)


def img_notifications() -> str:
    img = Image.new("RGB", (900, 420), WHITE)
    d = ImageDraw.Draw(img)
    draw_sidebar(d, ["Affiliate Hub", "Create Notification", "Notifications Log"], active=1)
    d.text((210, 24), "Send in-app notification", fill=DARK, font=font(20, True))
    d.rounded_rectangle((210, 70, 860, 380), radius=12, fill=LIGHT, outline=(226, 232, 240))
    fields = [
        ("Title", "New course batch open"),
        ("Message", "Enroll before Sunday for 50% off"),
        ("Send to", "All users / Single user / CSV / Affiliates"),
        ("Optional link", "https://thetoppercentile.co.in/shop/"),
    ]
    y = 92
    for label, value in fields:
        d.text((230, y), label, fill=MUTED, font=font(11))
        d.rounded_rectangle((230, y + 16, 820, y + 44), radius=6, fill=WHITE, outline=(203, 213, 225))
        d.text((240, y + 24), value, fill=DARK, font=font(12))
        y += 58
    d.rounded_rectangle((230, 318, 380, 352), radius=6, fill=BRAND)
    d.text((248, 328), "Send notification", fill=WHITE, font=font(12, True))
    d.text((400, 328), "Students see bell icon on site", fill=MUTED, font=font(12))
    return save_img("03-notifications.png", img)


def img_popup() -> str:
    img = Image.new("RGB", (900, 420), (40, 40, 40))
    d = ImageDraw.Draw(img)
    d.rounded_rectangle((80, 30, 820, 390), radius=14, fill=WHITE)
    d.ellipse((780, 42, 812, 74), fill=BRAND)
    d.text((792, 48), "X", fill=WHITE, font=font(16, True))
    d.rounded_rectangle((80, 30, 420, 390), radius=14, fill=WHITE)
    d.text((110, 70), "Flash Sale", fill=DARK, font=font(16, True))
    d.text((110, 100), "50% OFF", fill=BRAND, font=font(28, True))
    d.text((110, 145), "ON ENTIRE ORDER", fill=DARK, font=font(13, True))
    d.rounded_rectangle((110, 190, 380, 230), radius=8, fill=BRAND)
    d.text((130, 202), "Shop The Flash Sale Now", fill=WHITE, font=font(12, True))
    d.rounded_rectangle((420, 30, 820, 390), radius=0, fill=PANEL)
    d.text((460, 170), "Promo image", fill=BRAND, font=font(18, True))
    d.text((460, 200), "(upload in Site Popup settings)", fill=MUTED, font=font(12))
    d.text((80, 8), "Affiliate Hub → Site Popup → Enable + Save", fill=WHITE, font=font(11))
    return save_img("04-site-popup.png", img)


def img_coupons() -> str:
    img = Image.new("RGB", (900, 420), WHITE)
    d = ImageDraw.Draw(img)
    draw_sidebar(d, ["WooCommerce", "Marketing", "Coupons"], active=2)
    d.text((210, 24), "Create coupon code", fill=DARK, font=font(20, True))
    d.rounded_rectangle((210, 70, 860, 380), radius=12, fill=LIGHT, outline=(226, 232, 240))
    d.text((230, 92), "WooCommerce → Marketing → Coupons → Add coupon", fill=DARK, font=font(13, True))
    rows = [
        ("Code", "FLASH50"),
        ("Discount type", "Percentage discount"),
        ("Amount", "50"),
        ("Usage limit", "1 per user (optional)"),
        ("Expiry", "Set end date for sale"),
    ]
    y = 130
    for label, val in rows:
        d.text((230, y), label, fill=MUTED, font=font(11))
        d.rounded_rectangle((360, y - 4, 820, y + 22), radius=6, fill=WHITE, outline=(203, 213, 225))
        d.text((372, y + 2), val, fill=DARK, font=font(12))
        y += 38
    d.text((230, 330), "Share code on popup button URL or social media", fill=MUTED, font=font(12))
    return save_img("05-coupons.png", img)


def img_klaviyo() -> str:
    img = Image.new("RGB", (900, 420), WHITE)
    d = ImageDraw.Draw(img)
    draw_sidebar(d, ["TTP Dashboard", "Email Campaigns", "WooCommerce > Klaviyo"], active=1)
    d.text((210, 24), "Klaviyo email setup", fill=DARK, font=font(20, True))
    d.rounded_rectangle((210, 70, 860, 380), radius=12, fill=LIGHT, outline=(226, 232, 240))
    steps = [
        "1. WooCommerce → Marketing → Klaviyo → add Public API key",
        "2. TTP Dashboard → Email Campaigns → Sync all users (one time)",
        "3. In Klaviyo: Audience → Profiles (your contact list)",
        "4. Create Flows using metrics like TTP Welcome Lead",
        "5. Use {{ first_name|default:\"Aspirant\" }} in emails",
    ]
    y = 95
    for step in steps:
        d.text((230, y), step, fill=DARK, font=font(13))
        y += 36
    d.rounded_rectangle((230, 280, 520, 314), radius=6, fill=BRAND)
    d.text((248, 290), "Sync all WordPress users to Klaviyo", fill=WHITE, font=font(12, True))
    d.rounded_rectangle((230, 330, 820, 364), radius=6, fill=WHITE, outline=(203, 213, 225))
    d.text((242, 342), "Metric: TTP Welcome Lead  |  TTP Purchase  |  TTP Not Started", fill=MUTED, font=font(11))
    return save_img("06-klaviyo.png", img)


class GuidePDF(FPDF):
    def footer(self) -> None:
        self.set_y(-15)
        self.set_font("Helvetica", "I", 9)
        self.set_text_color(120, 120, 120)
        self.cell(0, 10, f"The Top Percentile Admin Guide  |  Page {self.page_no()}", align="C")


def add_section(pdf: GuidePDF, title: str, steps: list[str], image_path: str) -> None:
    pdf.add_page()
    pdf.set_font("Helvetica", "B", 18)
    pdf.set_text_color(*DARK)
    pdf.cell(0, 12, title, new_x="LMARGIN", new_y="NEXT")
    pdf.ln(2)
    pdf.set_font("Helvetica", "", 11)
    pdf.set_text_color(60, 60, 60)
    for step in steps:
        pdf.multi_cell(0, 7, f"- {step}")
        pdf.ln(1)
    pdf.ln(4)
    pdf.image(image_path, w=190)


def build_pdf() -> None:
    images = {
        "menu": img_menu_overview(),
        "affiliate": img_affiliate_links(),
        "notifications": img_notifications(),
        "popup": img_popup(),
        "coupons": img_coupons(),
        "klaviyo": img_klaviyo(),
    }

    pdf = GuidePDF()
    pdf.set_auto_page_break(auto=True, margin=18)
    pdf.add_page()
    pdf.set_fill_color(*BRAND)
    pdf.rect(0, 0, 210, 70, "F")
    pdf.set_y(22)
    pdf.set_font("Helvetica", "B", 26)
    pdf.set_text_color(255, 255, 255)
    pdf.cell(0, 12, "The Top Percentile", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("Helvetica", "", 16)
    pdf.cell(0, 10, "Admin Quick Guide", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.ln(20)
    pdf.set_text_color(*DARK)
    pdf.set_font("Helvetica", "", 12)
    intro = (
        "Simple steps for affiliate links, notifications, site popup, "
        "coupon codes, and Klaviyo emails on thetoppercentile.co.in"
    )
    pdf.multi_cell(0, 8, intro, align="C")
    pdf.ln(8)
    pdf.image(images["menu"], w=170, x=20)

    add_section(
        pdf,
        "1. Affiliate Links",
        [
            "Log in to WordPress admin -> Affiliate Hub -> Affiliate Links.",
            "Search the user by name or email (type at least 2 characters).",
            "Check Enable referral program (or Mark as influencer).",
            "Set commission % if needed, then click Generate link.",
            "Copy the link and share it. Format: yoursite.com/?ref=CODE",
            "Members can also see their link under My Account -> Referral Link.",
        ],
        images["affiliate"],
    )

    add_section(
        pdf,
        "2. Notifications",
        [
            "Go to Affiliate Hub -> Create Notification.",
            "Enter Title and Message. Add an optional link (course page, etc.).",
            "Choose Send to: single user, CSV file, all users, or all affiliates.",
            "Click Send notification - users see it in the bell icon on the site.",
            "View or delete past notifications: Affiliate Hub -> Notifications.",
            "For large sends, browser push is queued automatically in the background.",
        ],
        images["notifications"],
    )

    add_section(
        pdf,
        "3. Site Popup (Flash Sale)",
        [
            "Go to Affiliate Hub -> Site Popup.",
            "Check Show popup on the website to turn ON (uncheck to turn OFF).",
            "Set headline, image, button text, button URL, and countdown date.",
            "Click Save popup settings, then hard-refresh the site (Ctrl+F5).",
            "Visitors see the popup after a short delay. X button closes it.",
            "Tip: point the button URL to your shop page or a coupon landing page.",
        ],
        images["popup"],
    )

    add_section(
        pdf,
        "4. Coupon Codes",
        [
            "Go to WooCommerce -> Marketing -> Coupons -> Add coupon.",
            "Enter Code (e.g. FLASH50) - this is what customers type at checkout.",
            "Choose discount type: Percentage or Fixed cart discount.",
            "Set amount, optional usage limits, and expiry date.",
            "Publish the coupon, then share the code in popup, email, or social.",
            "Smart Coupons plugin adds extra options (auto-apply, BOGO) if installed.",
        ],
        images["coupons"],
    )

    add_section(
        pdf,
        "5. Klaviyo (Email Campaigns)",
        [
            "Connect Klaviyo: WooCommerce -> Marketing -> Klaviyo -> Public API key.",
            "Open TTP Dashboard -> Email Campaigns (or WooCommerce -> TTP Email Campaigns).",
            "Click Sync all WordPress users to Klaviyo (run once for existing users).",
            "In Klaviyo: Audience -> Profiles to see all contacts.",
            "Create Flows triggered by metrics (e.g. TTP Welcome Lead, TTP Purchase).",
            "Personalize emails with: {{ first_name|default:'Aspirant' }}",
        ],
        images["klaviyo"],
    )

    pdf.add_page()
    pdf.set_font("Helvetica", "B", 16)
    pdf.set_text_color(*DARK)
    pdf.cell(0, 10, "Quick reference", new_x="LMARGIN", new_y="NEXT")
    pdf.ln(4)
    pdf.set_font("Helvetica", "", 11)
    refs = [
        ("Affiliate links", "Affiliate Hub -> Affiliate Links"),
        ("Referral members", "Affiliate Hub -> Referral Members"),
        ("Notifications", "Affiliate Hub -> Create Notification"),
        ("Popup on/off", "Affiliate Hub -> Site Popup"),
        ("Coupons", "WooCommerce -> Marketing -> Coupons"),
        ("Klaviyo sync", "TTP Dashboard -> Email Campaigns"),
        ("Live site", "https://thetoppercentile.co.in/"),
    ]
    for label, where in refs:
        pdf.set_font("Helvetica", "B", 11)
        pdf.cell(55, 8, label)
        pdf.set_font("Helvetica", "", 11)
        pdf.cell(0, 8, where, new_x="LMARGIN", new_y="NEXT")

    pdf.output(str(PDF_PATH))
    print(f"Created: {PDF_PATH}")


if __name__ == "__main__":
    build_pdf()
