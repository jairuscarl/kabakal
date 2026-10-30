# Animation plan - Kabakal Fitness Equipment

Planned 2026-10-30. Work through it one phase per commit. Phases 1 and 2 built 2026-10-30.

**Feel:** gym energy - quick, punchy movement that lands with a short orange glow, not slow floaty fades.
**Where:** `assets/css/style.css` plus about 40 lines in `assets/js/main.js`. No new libraries.

## Ground rules (every animation)

- Animate only `transform` and `opacity`, so it stays smooth on cheap phones.
- One shared set of timings as CSS variables:
  - fast, 150 ms: hovers and button presses
  - medium, 250 ms: menus, chips, toggles
  - slow, 500 ms: things appearing as you scroll
  - plus one shared "snappy" easing curve
- Respect "reduce motion": if it is turned on in the visitor's phone or computer settings, everything here switches off (extend the existing `prefers-reduced-motion` rule in `style.css`).
- Customer shop only. The admin panel stays calm, except for the dashboard chart.

## Phase 1 - Home page first impression

- [x] **Banner intro.** "BUILD YOUR" slides up, then STRENGTH lands with a quick scale-down and an orange glow. Subtitle, Shop button and social icons fade in one after another. About 1 s in total, once per visit.
- [x] **Slow zoom on the background photo.** About 5% over 20 s, so the page feels alive without distracting.
- [x] **Sections appear on scroll.** Category tiles and product cards fade up in a quick wave, 60 ms apart (small IntersectionObserver helper in `main.js`). The orange bar beside each section title draws in from the top.

## Phase 2 - Shopping interactions

- [x] **Product cards.** On hover: lift 4 px, photo zooms slightly inside its frame, border turns orange. Buy Now presses down a little when clicked.
- [x] **Add to cart.** The card photo shrinks and flies to the header cart icon; the cart count bounces and flashes orange (~25 lines of JS).
- [x] **Filter chips.** The active chip's orange fill slides to the newly picked chip; product cards fade in again in a quick wave.
- [x] **Header search.** Widens slightly on focus; the orange button shrinks a little when pressed.
- [x] **Cart page.** A removed row collapses and fades out; the total counts up or down to the new amount.

## Phase 3 - Feedback and status

- [ ] **Messages** (e.g. "You have been logged out."): slide in from the top with a thin orange bar underneath that shrinks over the 5 seconds before they disappear.
- [ ] **Order progress tracker.** The green line fills step by step on load, each icon pops as it is reached, the current step pulses softly.
- [ ] **Phone menu.** Slides down instead of appearing instantly, links fade in one after another, the hamburger icon turns into an X.
- [ ] **Product photo gallery.** Photos crossfade instead of jumping; the full-screen view zooms open from the thumbnail.

## Phase 4 - Admin (small touches only)

- [ ] **Dashboard.** Sales bars grow up from the bottom when the chart comes into view; the numbers in the stat boxes count up from 0.

## Avoid

- Page-change animations or loading screens - they slow down every click.
- Animated backgrounds behind text - they hurt readability and battery life.
- Bouncy, springy movement everywhere - save it for the cart, where it means "something happened".
- Anything over 600 ms on things people click.

## Testing each phase

Screenshots at desktop and phone width, with "reduce motion" both off and on.
