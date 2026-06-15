#!/usr/bin/env python3
"""Generate TTP WordPress session summary PDF."""

from fpdf import FPDF
from datetime import date

OUT = r"c:\Users\sumit11\Downloads\wordpress conected ttp via cursor\TTP-WordPress-Session-Summary-Jun-2026.pdf"

SECTIONS = [
    ("Affiliate Hub critical error (fixed)", [
        "Cause: affiliate-notification-hub was updated on the server but ttp-affiliate was still an old version.",
        "Missing methods on live server: get_access_source_label, revoke_all_access, regenerate_referral_code, and others.",
        "Affiliate Links and Referral Members pages called these methods and crashed with a WordPress critical error.",
        "Fix deployed: full ttp-affiliate v1.0.8 and affiliate-notification-hub v1.0.8 uploaded via FTP.",
        "Earlier fix retained: TTPA_Referral_Service namespace uses leading backslash in ANH\\Admin namespace.",
    ]),
    ("Unauthorized referral access (kalokaraditya39@gmail.com)", [
        "Cause: old ttp-affiliate treated WordPress role 'affiliate' as auto referral access.",
        "influencer_roles() was ['influencer', 'affiliate'] - anyone with the affiliate role got access without admin approval.",
        "Fix: only 'influencer' role grants automatic access now. Legacy affiliate role is flagged as 'Legacy WP role only'.",
        "Audit: WordPress Admin > Affiliate Hub > Referral Members shows access source, granted date, and granted by admin.",
        "To revoke: Referral Members > Revoke, or Affiliate Links > select user > Save with access disabled.",
    ]),
    ("Edit profile on /login/ (fixed)", [
        "Edit profile button previously linked to WooCommerce /my-account/my-profile/ which redirects students back to /login/.",
        "Fix: button now opens /login/?tpsp_edit_profile=1 with User Registration [user_registration_edit_profile] form.",
        "Deployed in top-percentile-student-portal plugin.",
    ]),
    ("Malware / hack (removed earlier)", [
        "Fake popup 'verify your request / USE KEYBOARD' from plugin: wp-content/plugins/litspeed-beta/litspeed-beta.php",
        "Older backdoors: analytics_1781073135, backup_1781073135",
        "Security mu-plugin: wp-content/mu-plugins/00-ttp-security-recovery.php blocks known malware plugins.",
        "Homepage crash fixed: astra/functions.php is_checkout() guarded when WooCommerce not loaded.",
        "Remove Filester / File Organizer file managers - common re-hack entry point.",
    ]),
    ("SFTP / FTP connection", [
        "Host: 89.116.133.138 | Port: 21 (FTPS) | User: u345187203",
        "Config saved in workspace .vscode/sftp.json (two profiles: full site and plugins-only).",
        "Use Cursor SFTP extension or upload via FTPS with passive mode.",
        "Rotate FTP password after any chat share - treat shared credentials as compromised.",
    ]),
    ("What to do now", [
        "1. Refresh wp-admin and open Affiliate Hub > Dashboard - should load without critical error.",
        "2. Open Referral Members - review who has access; revoke kalokaraditya39@gmail.com if not intended.",
        "3. Purge LiteSpeed / Hostinger cache after plugin updates.",
        "4. Enable 2FA on WordPress admin; check Users for unknown admins.",
        "5. Delete Filester plugin; keep UpdraftPlus backups current.",
        "6. Test Edit profile at https://thetoppercentile.co.in/login/ while logged in as a student.",
    ]),
]


class PDF(FPDF):
    def header(self):
        self.set_font("Helvetica", "B", 14)
        self.cell(0, 10, "The Top Percentile - WordPress Fix Session Summary", new_x="LMARGIN", new_y="NEXT")
        self.set_font("Helvetica", "", 9)
        self.cell(0, 6, f"Generated: {date.today().isoformat()}", new_x="LMARGIN", new_y="NEXT")
        self.ln(4)

    def footer(self):
        self.set_y(-15)
        self.set_font("Helvetica", "I", 8)
        self.cell(0, 10, f"Page {self.page_no()}", align="C")

    def section(self, title, bullets):
        w = self.epw
        self.set_font("Helvetica", "B", 11)
        self.multi_cell(w, 7, title)
        self.ln(1)
        self.set_font("Helvetica", "", 10)
        for b in bullets:
            self.multi_cell(w, 6, f"  - {b}")
        self.ln(4)


pdf = PDF()
pdf.set_auto_page_break(auto=True, margin=15)
pdf.add_page()
pdf.set_font("Helvetica", "", 10)
pdf.multi_cell(
    pdf.epw,
    6,
    "This document summarizes fixes applied to thetoppercentile.co.in during the Cursor support session "
    "(Affiliate Hub errors, referral access audit, student Edit profile, malware removal, and deployment).",
)
pdf.ln(4)

for title, bullets in SECTIONS:
    pdf.section(title, bullets)

pdf.output(OUT)
print(f"Wrote {OUT}")
