import sys
import pdfplumber
from pathlib import Path

pdf_path = Path(sys.argv[1])
txt_path = Path(sys.argv[2])

texto = []

with pdfplumber.open(pdf_path) as pdf:
    for page in pdf.pages:
        t = page.extract_text()
        if t:
            texto.append(t)

txt_path.write_text("\n\n".join(texto), encoding="utf-8")
