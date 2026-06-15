#!/usr/bin/env python3
"""Generate a PDF summary from a Cursor agent transcript JSONL file."""

import json
import re
import sys
from pathlib import Path

from fpdf import FPDF


def clean_text(text: str) -> str:
    text = re.sub(r"\[REDACTED\]", "", text)
    text = re.sub(r"<[^>]+>", "", text)
    text = text.replace("\r\n", "\n").replace("\r", "\n")
    text = re.sub(r"\n{3,}", "\n\n", text)
    return text.strip()


def extract_messages(jsonl_path: Path):
    messages = []
    for line in jsonl_path.read_text(encoding="utf-8", errors="replace").splitlines():
        if not line.strip():
            continue
        try:
            row = json.loads(line)
        except json.JSONDecodeError:
            continue
        role = row.get("role", "")
        content = row.get("message", {}).get("content", [])
        parts = []
        for block in content:
            if isinstance(block, dict) and block.get("type") == "text":
                parts.append(block.get("text", ""))
        text = clean_text("\n".join(parts))
        if text:
            messages.append((role, text))
    return messages


class ChatPDF(FPDF):
    def header(self):
        self.set_font("Helvetica", "B", 11)
        self.cell(0, 8, "The Top Percentile - Cursor Chat Summary", ln=True)
        self.ln(2)

    def footer(self):
        self.set_y(-12)
        self.set_font("Helvetica", "", 8)
        self.cell(0, 8, f"Page {self.page_no()}", align="C")


def wrap_line(text: str, width: int = 95):
    text = text.encode("latin-1", errors="replace").decode("latin-1")
    if len(text) <= width:
        return [text]
    lines = []
    while text:
        if len(text) <= width:
            lines.append(text)
            break
        split_at = text.rfind(" ", 0, width)
        if split_at < 1:
            split_at = width
        lines.append(text[:split_at])
        text = text[split_at:].lstrip()
    return lines


def write_block(pdf: ChatPDF, text: str):
    pdf.set_x(pdf.l_margin)
    width = pdf.epw
    for paragraph in text.split("\n"):
        paragraph = paragraph.strip()
        if not paragraph:
            pdf.ln(2)
            continue
        for line in wrap_line(paragraph):
            if pdf.get_y() > 270:
                pdf.add_page()
            pdf.multi_cell(width, 5, line)
    pdf.ln(2)


def build_pdf(messages, output_path: Path, title: str):
    pdf = ChatPDF()
    pdf.set_margins(15, 15, 15)
    pdf.set_auto_page_break(auto=True, margin=18)
    pdf.add_page()
    pdf.set_font("Helvetica", "B", 14)
    write_block(pdf, title)
    pdf.ln(2)

    for role, text in messages:
        if len(text) > 12000:
            text = text[:12000] + "\n\n[... truncated for PDF ...]"
        label = "USER" if role == "user" else "ASSISTANT"
        if pdf.get_y() > 265:
            pdf.add_page()
        pdf.set_font("Helvetica", "B", 10)
        pdf.set_text_color(40, 40, 40)
        write_block(pdf, label)
        pdf.set_font("Helvetica", "", 9)
        pdf.set_text_color(0, 0, 0)
        write_block(pdf, text)
        pdf.ln(2)

    output_path.parent.mkdir(parents=True, exist_ok=True)
    pdf.output(str(output_path))


def main():
    transcript = Path(
        r"C:\Users\sumit11\.cursor\projects\c-Users-sumit11-Downloads-wordpress-conected-ttp-via-cursor\agent-transcripts\eaed744c-3638-4462-9e40-20bdc2803679\eaed744c-3638-4462-9e40-20bdc2803679.jsonl"
    )
    output = Path(
        r"c:\Users\sumit11\Downloads\wordpress conected ttp via cursor\TTP-WordPress-Fix-Session-Summary.pdf"
    )
    if len(sys.argv) > 1:
        transcript = Path(sys.argv[1])
    if len(sys.argv) > 2:
        output = Path(sys.argv[2])

    messages = extract_messages(transcript)
    build_pdf(
        messages,
        output,
        "WordPress Critical Error & TCY Fix Session\n(Previous Cursor chat summary)",
    )
    print(f"Wrote {output}")


if __name__ == "__main__":
    main()
