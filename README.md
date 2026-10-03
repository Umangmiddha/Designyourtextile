# DesignYourTextile (DYT)

Production-grade textile print design and color management services.

**Positioning:** I make sure what the buyer approves is exactly what comes off the production line.

## About

DesignYourTextile (DYT) bridges the gap between design studio and factory floor — specializing in:

- Cross-substrate color matching (cotton, polyester, silk, blends)
- Pantone / TCX matching for brand clients
- Delta-E correction and tolerance management
- ICC profile management for digital, rotary, and sublimation printing
- Dye-lot consistency across large runs

## Project Structure

```
.
├── index.html      # Single-page marketing site (semantic HTML + embedded JSON-LD schema)
├── styles.css      # Site styles (dark base palette, responsive, WCAG 2.2 AA)
├── Prompt.txt      # Original project brief
├── README.md       # This file
└── .gitignore
```

## Running Locally

No build step required. Simply open `index.html` in a browser, or serve the folder:

```bash
# Python 3
python -m http.server 8000

# or Node.js
npx serve .
```

Then visit http://localhost:8000

## Tech

- Plain HTML5 + CSS3 (no JS frameworks)
- Google Fonts (Fraunces, Inter, JetBrains Mono)
- SEO: meta tags, Open Graph, and schema.org structured data (ProfessionalService, FAQPage, Service)

## Contact

**help@designyourtextile.com**
