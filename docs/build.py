"""Builds the report and slides PDFs.

    python docs/build.py            # uses the latest tests/results.json
    python docs/build.py --retest   # re-runs the test suite first

Fills the {{TOKENS}} in report.html / slides.html from details.json, git history and
test results, prints them to PDF with headless Chrome, then adds page numbers.
"""
import html, io, json, re, subprocess, sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DOCS = ROOT / "docs"
BUILD = DOCS / "build"
PHP = r"C:\xampp\php\php.exe"
CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"


def run(cmd, **kw):
    return subprocess.run(cmd, cwd=ROOT, capture_output=True, text=True, encoding="utf-8", **kw)


def tokens():
    t = json.loads((DOCS / "details.json").read_text(encoding="utf-8"))
    if "--retest" in sys.argv:
        run([PHP, "tests/run.php"])
    res = json.loads((ROOT / "tests" / "results.json").read_text(encoding="utf-8"))
    rows, summary = [], {}
    for r in res["results"]:
        level = r["name"].capitalize()
        ok = r["status"] == "PASS"
        s = summary.setdefault(level, [0, 0])
        s[0] += 1
        s[1] += ok
        rows.append(f'<tr><td>{r["id"]}</td><td>{level}</td><td>{html.escape(r["desc"])}</td>'
                    f'<td>{r["ms"]} ms</td><td class="{"pass" if ok else "fail"}">{r["status"]}</td></tr>')
    t["TEST_ROWS"] = "\n".join(rows)
    t["TEST_SUMMARY"] = "\n".join(f"<tr><td>{k}</td><td>{v[0]}</td><td>{v[1]}</td><td>{v[0]-v[1]}</td></tr>" for k, v in summary.items())
    t["TEST_TOTAL"] = str(len(res["results"]))
    t["TEST_PASS"] = str(sum(r["status"] == "PASS" for r in res["results"]))
    t["TEST_RUN"] = res["run_at"]
    t["PHP"] = res["php"]

    log = run(["git", "log", "--reverse", "--format=%h|%ad|%s", "--date=format:%Y-%m-%d %H:%M"]).stdout.strip().splitlines()
    t["COMMITS"] = "\n".join(f"<tr><td><code>{h}</code></td><td>{d}</td><td>{html.escape(m)}</td></tr>"
                             for h, d, m in (l.split("|", 2) for l in log))
    t["FIX1"] = next((l.split("|")[0] for l in log if "DEF-01" in l), "")
    t["FIX2"] = next((l.split("|")[0] for l in log if "DEF-03" in l), "")
    t["COMMIT_COUNT"] = str(len(log))

    loc = 0
    for f in list((ROOT / "src").rglob("*.php")) + list((ROOT / "views").rglob("*.php")) + list((ROOT / "public").glob("*.php")) + list((ROOT / "tests").rglob("*.php")) + list((ROOT / "database").glob("*.php")):
        loc += sum(1 for line in f.read_text(encoding="utf-8").splitlines() if line.strip())
    t["LOC"] = f"{round(loc, -2):,}"
    return t


def fill(src: Path, t: dict) -> Path:
    s = src.read_text(encoding="utf-8")
    for k, v in t.items():
        s = s.replace("{{" + k + "}}", v)
    s = s.replace('src="build/screens/', 'src="screens/')
    left = re.findall(r"\{\{[A-Z_]+\}\}", s)
    if left:
        print("Unfilled tokens:", set(left))
    out = BUILD / src.name
    out.write_text(s, encoding="utf-8")
    return out


def to_pdf(page: Path, pdf: Path):
    url = "file:///" + str(page).replace("\\", "/")
    r = subprocess.run([CHROME, "--headless=new", "--disable-gpu", "--no-pdf-header-footer", "--virtual-time-budget=30000",
                        "--run-all-compositor-stages-before-draw", f"--print-to-pdf={pdf}", url], capture_output=True, text=True)
    if not pdf.exists():
        raise SystemExit(r.stderr)


def number_pages(pdf: Path, label: str, skip_first=True, landscape=False):
    from pypdf import PdfReader, PdfWriter
    from reportlab.pdfgen import canvas
    reader = PdfReader(str(pdf))
    writer = PdfWriter()
    total = len(reader.pages)
    for i, page in enumerate(reader.pages):
        if not (skip_first and i == 0):
            w, h = float(page.mediabox.width), float(page.mediabox.height)
            buf = io.BytesIO()
            c = canvas.Canvas(buf, pagesize=(w, h))
            c.setFont("Helvetica", 8)
            c.setFillGray(0.45)
            c.drawString(48, 24, label)
            c.drawRightString(w - 48, 24, f"Page {i + 1} of {total}")
            c.save()
            buf.seek(0)
            page.merge_page(PdfReader(buf).pages[0])
        writer.add_page(page)
    with open(pdf, "wb") as f:
        writer.write(f)
    return total


def check(pdf: Path):
    from pypdf import PdfReader
    text = "\n".join(p.extract_text() or "" for p in PdfReader(str(pdf)).pages)
    for bad in ("Syntax error", "Parse error", "{{"):
        if bad in text:
            print(f"WARNING: '{bad}' found in {pdf.name}")


if __name__ == "__main__":
    BUILD.mkdir(exist_ok=True)
    t = tokens()
    rep = fill(DOCS / "report.html", t)
    pdf = DOCS / "SRMS_Project_Report.pdf"
    to_pdf(rep, pdf)
    n = number_pages(pdf, "Student Result Management System · Project Report")
    check(pdf)
    print(f"Report: {pdf} ({n} pages)")
    if (DOCS / "slides.html").exists():
        sl = fill(DOCS / "slides.html", t)
        spdf = DOCS / "SRMS_Presentation.pdf"
        to_pdf(sl, spdf)
        check(spdf)
        from pypdf import PdfReader
        print(f"Slides: {spdf} ({len(PdfReader(str(spdf)).pages)} slides)")
