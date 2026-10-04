# DYT - Design Your Textile

A **graphic design studio specialized in textile businesses**. Textile design, production-ready artwork, 3D CAD visualization and motion design for fashion, home and technical textiles.

**Positioning:** One studio for the whole textile idea - design it, produce it, see it in 3D, and bring it to life in motion.

## About DYT

DYT works in one industry: textile. We do not split attention across FMCG, packaging or general branding. Everything we make serves cloth and the businesses that produce and sell it:

- **Textile Design** - hand-painted and digital surface patterns, seamless repeat engineering (half-drop, brick, mirror, tossed, full-drop), engineered and placement prints
- **Production-Ready Artwork** - colour separation, repeat metadata, Pantone matching, prepress artwork, layered source files and mill-ready tech packs
- **3D CAD Visualization** - fabric drape and material renders, garment and home product models, colourway and variant studies, digital twins for approval and catalogues
- **Motion Design** - animated drape and flow, print repeat motion, product turntables, collection showreels and social-ready motion assets

## Project Structure

```
.
├── index.html        # Home - hero, disciplines, process teaser, featured work, CTA
├── services.html     # Four services (design, production, 3D CAD, motion) + deliverables
├── portfolio.html    # Selected work + categories + FAQ
├── process.html      # 5-stage workflow + working principles + FAQ
├── about.html        # Studio story, full brief coverage, tools & craft, who we work with
├── contact.html      # Enquiry form + brief template + FAQ
├── styles.css        # Textile design system (warp/weft grid, dyelot palette, loom motif)
├── script.js         # Shared behaviour: nav, scroll reveals, active links, form validation
├── contact.php       # Server-side enquiry form handler (PHP mail())
└── README.md         # This file
```

## Running Locally

No build step required. Serve the folder and open it in a browser:

```bash
# Python 3
python -m http.server 8000

# or Node.js
npx serve .
```

Then visit http://localhost:8000

> Note: the enquiry form (`contact.php`) requires a PHP host to submit. Everything else is static and works without a server.

## Tech

- Plain HTML5 + CSS3 + vanilla JavaScript (no frameworks, no build step)
- Google Fonts: Fraunces (display), Inter (body), JetBrains Mono (technical labels)
- SEO: meta tags, Open Graph, Twitter cards, canonical URLs, and schema.org structured data (ProfessionalService, FAQPage, ContactPage)
- Accessible enquiry form with client-side + server-side validation, honeypot, timing and rate-limit spam protection

## Design System

A textile studio aesthetic built on warp-and-weft geometry:

- Deep ink base (`#0f0d0b`) with a repeating vertical "warp thread" background grid
- Dye-bleed aurora glows in indigo, madder and turmeric
- **Dyelot palette:** indigo, madder, turmeric, moss, clay, thread (gold)
- Loom-grid swatch panel and woven motif overlays in the hero
- Technical, documentation-style microcopy in JetBrains Mono (repeat sizes, DPI, Pantone, files, render and motion formats)

## Contact

**admin@designyourtextile.com** - the only point of contact used across the site.
