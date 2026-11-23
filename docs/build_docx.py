"""Builds an editable Word version of the report: docs/SRMS_Project_Report.docx

    python docs/build.py        # first: fills tokens and renders docs/build/report.html
    python docs/build_docx.py   # then: diagrams -> PNG, HTML -> DOCX, then formatting

Diagrams are rasterised with headless Chrome (Word cannot show Mermaid), converted with
pandoc, then python-docx adds table borders, header shading, page numbers and the cover layout.
"""
import re, subprocess
from pathlib import Path

import pypandoc
from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Pt, RGBColor, Cm

DOCS = Path(__file__).resolve().parent
BUILD = DOCS / "build"
DIA = BUILD / "diagrams"
CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
GREEN = RGBColor(0x14, 0x53, 0x2D)
MAX_W_IN = 6.3  # usable width on A4 with 2 cm margins


def chrome(*args):
    return subprocess.run([CHROME, "--headless=new", "--disable-gpu", "--hide-scrollbars", *args],
                          capture_output=True, text=True, encoding="utf-8", errors="replace")


def url(p: Path) -> str:
    return "file:///" + str(p).replace("\\", "/")


def screenshot_svg(svg: str, w: float, h: float, out: Path):
    page = out.with_suffix(".html")
    page.write_text(f'<!doctype html><html><body style="margin:0;background:#fff">'
                    f'<div style="width:{w}px;height:{h}px">{svg}</div></body></html>', encoding="utf-8")
    chrome(f"--window-size={int(w)},{int(h)}", "--force-device-scale-factor=2", f"--screenshot={out}", url(page))


def rasterise(html: str) -> str:
    DIA.mkdir(exist_ok=True)
    n = 0

    def repl(m):
        nonlocal n
        n += 1
        svg = m.group(1)
        vb = re.search(r'viewBox="([\d.\-]+) ([\d.\-]+) ([\d.]+) ([\d.]+)"', svg)
        w, h = float(vb.group(3)), float(vb.group(4))
        svg = re.sub(r'<svg([^>]*?)style="[^"]*"', r'<svg\1', svg, count=1)
        svg = re.sub(r'<svg([^>]*?)width="[^"]*"', r'<svg\1', svg, count=1)
        svg = svg.replace("<svg", f'<svg width="{w}" height="{h}"', 1)
        out = DIA / f"d{n:02d}.png"
        screenshot_svg(svg, w, h, out)
        width_in = min(MAX_W_IN, w / 96 * 0.9)
        if h / w * width_in > 8.3:  # keep tall diagrams on one page
            width_in = 8.3 * w / h
        return f'<p><img src="diagrams/{out.name}" style="width:{width_in:.2f}in"></p>'

    html = re.sub(r'<pre class="mermaid"[^>]*>\s*(<svg.*?</svg>)\s*</pre>', repl, html, flags=re.S)
    print(f"{n} diagrams rasterised")

    uc = (DOCS / "usecase.svg").read_text(encoding="utf-8")
    vb = re.search(r'viewBox="([\d.\-]+) ([\d.\-]+) ([\d.]+) ([\d.]+)"', uc)
    w, h = float(vb.group(3)), float(vb.group(4))
    screenshot_svg(uc.replace("<svg ", f'<svg width="{w}" height="{h}" ', 1), w, h, DIA / "usecase.png")
    html = re.sub(r'<img src="\.\./usecase\.svg"[^>]*>', f'<img src="diagrams/usecase.png" style="width:{MAX_W_IN}in">', html)
    return html


def prepare(html: str) -> str:
    html = re.sub(r"<script.*?</script>", "", html, flags=re.S)
    # manual contents list -> Word's own table of contents (inserted by pandoc --toc)
    html = re.sub(r'<h1 style="page-break-before: always">Contents</h1>\s*<ul class="toc">.*?</ul>', "", html, flags=re.S)
    # screenshots: fixed sizes
    html = re.sub(r'<img src="screens/([^"]+)"[^>]*>', r'<img src="screens/\1" style="width:3.05in">', html)
    html = html.replace('<img src="screens/student-results.png" style="width:3.05in">',
                        '<img src="screens/student-results.png" style="width:5in">')
    # cover: plain paragraphs, laid out later
    m = re.search(r'<section class="cover">(.*?)</section>', html, flags=re.S)
    cover = m.group(1)
    inst = re.search(r'class="inst">(.*?)<', cover).group(1)
    dept = re.search(r'class="dept">(.*?)<', cover).group(1)
    rows = re.findall(r"<tr><td>(.*?)</td><td>(.*?)</td></tr>", cover)
    parts = [f'<p>COVER:INST {inst}</p>', f'<p>COVER:DEPT {dept}</p>',
             '<p>COVER:TITLE Student Result Management System</p>',
             '<p>COVER:SUB Full Software Development Lifecycle Project · Final Report</p>']
    parts += [f"<p>COVER:ROW {k}|{v}</p>" for k, v in rows]
    html = html.replace(m.group(0), "\n".join(parts))
    return html


def set_cell_shading(cell, hex_fill):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), hex_fill)
    tcPr.append(shd)


def table_borders(table):
    tblPr = table._tbl.tblPr
    borders = OxmlElement("w:tblBorders")
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        el = OxmlElement(f"w:{edge}")
        el.set(qn("w:val"), "single")
        el.set(qn("w:sz"), "4")
        el.set(qn("w:space"), "0")
        el.set(qn("w:color"), "B7C2CC")
        borders.append(el)
    # schema order: tblBorders goes before shd / tblLayout / tblCellMar / tblLook
    for later in ("shd", "tblLayout", "tblCellMar", "tblLook", "tblCaption", "tblDescription"):
        anchor = tblPr.find(qn(f"w:{later}"))
        if anchor is not None:
            anchor.addprevious(borders)
            return
    tblPr.append(borders)


def add_page_number(paragraph):
    paragraph.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    run = paragraph.add_run("Page ")
    for code in ("PAGE",):
        r = paragraph.add_run()
        b = OxmlElement("w:fldChar"); b.set(qn("w:fldCharType"), "begin")
        t = OxmlElement("w:instrText"); t.set(qn("xml:space"), "preserve"); t.text = code
        e = OxmlElement("w:fldChar"); e.set(qn("w:fldCharType"), "end")
        r._r.append(b); r._r.append(t); r._r.append(e)
    for r in paragraph.runs:
        r.font.size = Pt(9)
        r.font.color.rgb = RGBColor(0x5D, 0x68, 0x76)


def page_break_before(paragraph):
    paragraph.paragraph_format.page_break_before = True  # python-docx keeps schema order


def format_docx(path: Path):
    doc = Document(str(path))
    sec = doc.sections[0]
    sec.page_width, sec.page_height = Cm(21), Cm(29.7)
    for s in doc.sections:
        s.left_margin = s.right_margin = Cm(2)
        s.top_margin = Cm(2)
        s.bottom_margin = Cm(2)
        s.header_distance = s.footer_distance = Cm(1.25)
        pgMar = s._sectPr.find(qn("w:pgMar"))
        if pgMar.get(qn("w:gutter")) is None:
            pgMar.set(qn("w:gutter"), "0")

    styles = doc.styles
    for name in ("Normal", "Body Text", "First Paragraph", "Compact"):
        if name in [s.name for s in styles]:
            st = styles[name]
            st.font.name = "Calibri"
            st.font.size = Pt(11)
            st._element.rPr.rFonts.set(qn("w:eastAsia"), "Calibri")
    for name, size in (("Heading 1", 18), ("Heading 2", 14), ("Heading 3", 12)):
        st = styles[name]
        st.font.name = "Calibri"
        st.font.size = Pt(size)
        st.font.bold = True
        st.font.color.rgb = GREEN if name != "Heading 3" else RGBColor(0x1A, 0x22, 0x30)
        st._element.rPr.rFonts.set(qn("w:ascii"), "Calibri")
        st._element.rPr.rFonts.set(qn("w:hAnsi"), "Calibri")
        st.paragraph_format.space_before = Pt(14 if name != "Heading 3" else 10)
        st.paragraph_format.space_after = Pt(6)
        st.paragraph_format.keep_with_next = True

    # cover page
    cover_end = None
    for p in doc.paragraphs:
        t = p.text
        if not t.startswith("COVER:"):
            if cover_end is not None:
                break
            continue
        kind, _, val = t[6:].partition(" ")
        for r in p.runs[1:]:
            r._r.getparent().remove(r._r)
        run = p.runs[0]
        run.text = val
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        if kind == "INST":
            run.font.size, run.font.bold, run.font.color.rgb = Pt(16), True, GREEN
            run.text = val.upper()
            p.paragraph_format.space_before = Pt(36)
        elif kind == "DEPT":
            run.font.size = Pt(12)
        elif kind == "TITLE":
            run.font.size, run.font.bold = Pt(28), True
            p.paragraph_format.space_before = Pt(150)
        elif kind == "SUB":
            run.font.size, run.font.color.rgb = Pt(14), RGBColor(0x5D, 0x68, 0x76)
            p.paragraph_format.space_after = Pt(120)
        elif kind == "ROW":
            k, _, v = val.partition("|")
            run.text = f"{k}: "
            run.font.color.rgb = RGBColor(0x5D, 0x68, 0x76)
            r2 = p.add_run(v)
            r2.bold = True
            p.paragraph_format.space_after = Pt(4)
        cover_end = p

    # every chapter starts on a new page; the TOC starts right after the cover
    first_h1 = True
    for p in doc.paragraphs:
        if p.style.name == "Heading 1":
            if first_h1 and p.text.startswith("Executive"):
                pass
            page_break_before(p)
            first_h1 = False
    # the TOC block pandoc inserted sits before the first heading: break before it too
    body = doc.element.body
    sdt = body.find(qn("w:sdt"))
    if sdt is not None:
        pb = OxmlElement("w:p"); r = OxmlElement("w:r"); br = OxmlElement("w:br"); br.set(qn("w:type"), "page")
        r.append(br); pb.append(r)
        sdt.addprevious(pb)

    # images centred, captions small and grey
    for p in doc.paragraphs:
        if p._p.xpath(".//w:drawing"):
            p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        if re.match(r"^(Figure|Table) \d", p.text):
            p.alignment = WD_ALIGN_PARAGRAPH.CENTER
            for r in p.runs:
                r.font.size = Pt(9)
                r.font.italic = True
                r.font.color.rgb = RGBColor(0x5D, 0x68, 0x76)

    # tables: borders, shaded header row, smaller text
    for table in doc.tables:
        table_borders(table)
        for i, row in enumerate(table.rows):
            for cell in row.cells:
                if i == 0 or cell._tc.xpath(".//w:b"):
                    pass
                for p in cell.paragraphs:
                    p.paragraph_format.space_after = Pt(2)
                    for r in p.runs:
                        r.font.size = Pt(9.5)
            if i == 0:
                for cell in row.cells:
                    set_cell_shading(cell, "E6F2EA")
                    for p in cell.paragraphs:
                        for r in p.runs:
                            r.bold = True

    # footer with page number on every page
    footer = doc.sections[0].footer
    fp = footer.paragraphs[0] if footer.paragraphs else footer.add_paragraph()
    left = fp.add_run("Student Result Management System · Project Report\t\t")
    left.font.size = Pt(9)
    left.font.color.rgb = RGBColor(0x5D, 0x68, 0x76)
    add_page_number(fp)
    fp.alignment = WD_ALIGN_PARAGRAPH.LEFT

    # ask Word to refresh the table of contents when the file opens
    settings = doc.settings.element
    upd = OxmlElement("w:updateFields"); upd.set(qn("w:val"), "true")
    # schema order: updateFields must come before hdrShapeDefaults/footnotePr/.../rsids
    for later in ("hdrShapeDefaults", "footnotePr", "endnotePr", "compat", "docVars", "rsids",
                  "mathPr", "attachedSchema", "themeFontLang", "clrSchemeMapping"):
        anchor = settings.find(qn(f"w:{later}"))
        if anchor is not None:
            anchor.addprevious(upd)
            break
    else:
        settings.append(upd)
    doc.save(str(path))


if __name__ == "__main__":
    src = BUILD / "report.html"
    dumped = chrome("--virtual-time-budget=30000", "--run-all-compositor-stages-before-draw", "--dump-dom", url(src)).stdout
    html = rasterise(dumped)
    html = prepare(html)
    mid = BUILD / "report_docx.html"
    mid.write_text(html, encoding="utf-8")
    out = DOCS / "SRMS_Project_Report.docx"
    pypandoc.convert_file(str(mid), "docx", outputfile=str(out),
                          extra_args=["--toc", "--toc-depth=2", f"--resource-path={BUILD}",
                                      "--metadata", "toc-title=Contents", "--metadata", "title="])
    format_docx(out)
    print(f"Wrote {out}")
