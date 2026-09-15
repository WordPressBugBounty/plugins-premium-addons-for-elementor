# Lock Scroll Until Fill Ends

## Context

"Fill on Scroll" (internally `clip_scroll`) shipped yesterday in Premium Heading, Premium Dual Heading and Premium Textual Showcase. Words fill one by one as the reader scrolls, driven by a GSAP ScrollTrigger scrub. The handler lives in the Pro plugin (`pa-clip-scroll.js`, added in `602a7fa7`); the controls, CSS and GSAP libraries ship in Free.

Today the fill happens _while the page keeps moving_, so on a short section the reader scrolls past the heading before it finishes filling. ScrollTrigger's `pin` solves exactly this: hold the section in place, spend the scroll on the fill, then release.

This adds one switcher — **Lock Scroll Until Fill Ends** — that turns `pin` on. Outcome: with the option off, nothing about today's behaviour changes; with it on, the section stays put until the words are filled.

Design was settled in a decision-by-decision review; the reasoning is captured in a new gotchas doc so the non-obvious choices (especially the deliberate ScrollTrigger version hold) don't read as oversights later.

---

## Locked decisions

| #   | Decision                                                                                                                                                                                  |
| --- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Pin target = closest Elementor ancestor, `$scope.closest('.e-con, .elementor-section')`; fall back to widget root                                                                         |
| 2   | Lock on ⇒ **one** ScrollTrigger per widget, **one merged timeline** across all groups in document order; distance = **sum** of group speeds. Lock off ⇒ today's per-group path, untouched |
| 3   | Distance reuses existing `clip_scroll_speed` / `desc_clip_scroll_speed`. No new distance control                                                                                          |
| 4   | Locked `start` is a function: target height ≥ viewport ? `"top top"` : `"center center"`, with `invalidateOnRefresh: true`. Unlocked `start` stays `"top 80%"`                            |
| 5   | One widget-level switcher after `clip_scroll_speed` in each widget's main section + a RAW_HTML notice                                                                                     |
| 6   | Label is plain `Lock Scroll Until Fill Ends` — no `pa_pro_label`, no "(Pro)"                                                                                                              |
| 7   | Applies on all devices. Add `ScrollTrigger.config({ ignoreMobileResize: true })`                                                                                                          |
| 8   | `prefers-reduced-motion` not respected — consistent with every other PA effect                                                                                                            |
| 9   | **ScrollTrigger stays at 3.11.2.** Do not bump                                                                                                                                            |
| 10  | Pin runs inside the Elementor editor preview (WYSIWYG)                                                                                                                                    |
| 11  | Default `pinType` (`position: fixed`). No `pinReparent`                                                                                                                                   |
| 12  | No Pro version floor — ships in the same Pro release as fill-on-scroll                                                                                                                    |
| 13  | Dedupe by pin target; second widget on an already-pinned node falls back to `pin: false`                                                                                                  |
| 14  | No sticky-header / admin-bar offset handling                                                                                                                                              |
| 15  | Both repos in one pass                                                                                                                                                                    |
| 16  | Update `docs/widgets/title.md`; create `docs/gotchas/clip-scroll-lock/gotchas.md`. Do not write the missing dual-header / showcase widget docs                                            |
| 17  | `scrub` stays `1.2` on both paths                                                                                                                                                         |

---

## Repo 1 — Free (`pa-develop`, branch `develop`)

Controls and one render attribute. No JS, no CSS, no asset changes.

### New controls, per widget

```php
$this->add_control(
    'clip_scroll_lock',
    array(
        'label'       => __( 'Lock Scroll Until Fill Ends', 'premium-addons-for-elementor' ),
        'description' => __( 'Pin the section in place while the words fill, then release the scroll.', 'premium-addons-for-elementor' ),
        'type'        => Controls_Manager::SWITCHER,
        'render_type' => 'template',
        /* conditions — see per-widget table below */
    )
);

$this->add_control(
    'clip_scroll_lock_notice',
    array(
        'type'            => Controls_Manager::RAW_HTML,
        'raw'             => __( '…', 'premium-addons-for-elementor' ),
        'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
        'condition'       => array( 'clip_scroll_lock' => 'yes' ),
    )
);
```

| Widget           | File                                                                 | Insert after                                                          | Switcher condition                                                                                    | Notice text                                                                         |
| ---------------- | -------------------------------------------------------------------- | --------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| Heading          | [premium-title.php](widgets/premium-title.php)                       | `clip_scroll_speed`, ends L510 (before `end_controls_section()` L512) | `conditions` / `relation: or` → `clip_scroll = yes` **or** `desc_clip_scroll = yes`                   | "The section stays pinned until both the title and the description finish filling." |
| Dual Heading     | [premium-dual-header.php](widgets/premium-dual-header.php)           | `clip_scroll_speed`, ends L823                                        | same                                                                                                  | same                                                                                |
| Textual Showcase | [premium-textual-showcase.php](widgets/premium-textual-showcase.php) | `clip_scroll_speed`, ends L2049                                       | **none** — mirrors the unconditioned speed slider above it, because `clip_scroll` is a repeater field | "The section stays pinned until every filling item finishes."                       |

`relation: or` is what [docs/common-mistakes/add-option.md](docs/common-mistakes/add-option.md) mistake #4 prescribes for a switcher whose sibling (`desc_clip_scroll`) lives in a conditioned section. Stale saved values are already neutralised downstream — all three widgets re-verify in PHP before emitting anything.

### Render attribute

Add `data-clip-lock` to the existing `container` render attribute, **only when at least one group will be emitted** — i.e. inside the same branch that already sets `data-clip-speed`, or immediately after it once both group checks have run.

- Heading — `render()`, the `$clip_on_scroll` / `$desc_clip` block around L2949–2976
- Dual Heading — `render()`, the `$clip_on_scroll` block at L2019–2027 and the `$desc_clip` block at L2056–2066; also the editor `content_template()` at L2120–2193, which mirrors the whole thing
- Textual Showcase — `render()`, the `array_filter( $content, … 'item_fills_on_scroll' )` guard at L2416–2418

Reuse the existing `fills_on_scroll()` / `description_fills_on_scroll()` / `item_fills_on_scroll()` helpers — do not add new condition checks.

### Free gating

Nothing new. `clip_scroll_lock` is only reachable when `clip_scroll` or `desc_clip_scroll` is on, and those already hard-block on Free with `.premium-error-notice` + `return false` ([premium-title.php:2952](widgets/premium-title.php#L2952), [premium-dual-header.php:1969](widgets/premium-dual-header.php#L1969), [premium-textual-showcase.php:2430](widgets/premium-textual-showcase.php#L2430)).

`get_script_depends()` needs no change — `pa-clip-scroll` is already declared whenever fill-on-scroll is active.

---

## Repo 2 — Pro (`premium-addons-pro`, branch `develop`)

Single file: `assets/frontend/js/pa-clip-scroll.js` (101 lines today). Source only — **do not** hand-edit `assets/frontend/min-js/pa-clip-scroll.min.js`; the build is yours to run.

### Structure

```
module scope:
  pinnedTargets = new Set()              // decision 13
  ScrollTrigger.config({ ignoreMobileResize: true })   // decision 7, once

PremiumClipScrollHandler($scope, $):
  bail if gsap or ScrollTrigger undefined          (unchanged)
  idPrefix = "pa-clip-scroll-" + $scope.data("id")

  kill previous triggers for this idPrefix with kill(true)   // decision 10
    └ and drop their pin target from pinnedTargets

  groups = $scope.find("[data-clip-speed]")        (unchanged, incl. wrapWords for [data-clip-split])
  if no groups → return

  locked = $scope.find("[data-clip-lock]").length > 0
  pinTarget = $scope.closest(".e-con, .elementor-section")[0] || $scope[0]   // decision 1
  if locked && pinnedTargets.has(pinTarget) → locked = false                 // decision 13

  if (!locked)  → existing per-group loop, byte-for-byte unchanged
  if (locked)   → single merged timeline (below)

  ScrollTrigger.refresh()                          // decision 10
```

Locked path:

- Walk groups in document order; for each, collect its own `.pa-clip-word`s using the existing `closest('[data-clip-speed]')` ownership filter.
- Append every word to **one** timeline at a running index, so the title's words run, then the description's — the merged reading order from decision 2.
- `totalSpeed` = sum of each group's `data-clip-speed` (`NaN` → `0.5`, same fallback as today).
- One ScrollTrigger:
  ```js
  {
    id: idPrefix + "-locked",
    trigger: pinTarget,
    pin: pinTarget,
    start: function () {
      return pinTarget.offsetHeight >= window.innerHeight ? "top top" : "center center";
    },
    end: "+=" + totalSpeed * 100 + "%",
    scrub: 1.2,
    invalidateOnRefresh: true,
  }
  ```
- Add `pinTarget` to `pinnedTargets` on create; remove it in the kill loop.
- `pinType` and `pinSpacing` left at defaults (decision 11).

---

## Known risks accepted in this design

Each was raised, weighed and accepted — they belong in the gotchas doc, not in a future bug report.

1. **`scrub: 1.2` under pin** (decision 17). The timeline lags the scrollbar by ~1.2s, so the last word may still be filling as the pin releases. The feature's own label promises otherwise. Accepted for consistency of feel.
2. **ScrollTrigger 3.11.2 with GSAP 3.14.2** (decision 9). GSAP's rule is that plugin and core versions match; `pin` is the most intricate path and got fixes across 3.12–3.14. Held deliberately to keep the blast radius off `premium-svg-drawer.php:107,114`, the other `pa-scrolltrigger` consumer.
3. **Pinning inside the editor** (decision 10). ScrollTrigger inserts a `.pin-spacer` between the Elementor container and its parent — a node Elementor manages for sortable, element handles and the right-click menu. Mitigated by `kill(true)` plus `ScrollTrigger.refresh()` on every re-run; needs explicit editor testing (below).
4. **Default `pinType: fixed`** (decision 11). A `transform` / `will-change` on any ancestor breaks `position: fixed` — Elementor entrance animations, Motion Effects, Sticky, and PA Floating Effects all do this. Symptom: the pinned section jumps then scrolls away instead of holding.
5. **No sticky-header offset** (decision 14). A tall (≥100vh) locked section under a sticky header pins flush to the viewport top and is clipped. Short sections use `"center center"` and are unaffected.
6. **Elementor flex containers** — pin spacing adds padding to the spacer; GSAP warns this may not push siblings in flex/absolute parents. Elementor containers are flex column by default, where it does work, but grid containers and `position: absolute` are untested.

---

## Documentation

- Update [docs/widgets/title.md](docs/widgets/title.md) — the Fill on Scroll section, the controls reference, and the JavaScript Lifecycle section.
- Create `docs/gotchas/clip-scroll-lock/gotchas.md` — the _why_ behind decisions 1, 2, 4, 9, 10, 11, 13, 17 and the six risks above. Per repo rule, nothing in shipped code links to `docs/`.
- Not in scope: `docs/widgets/dual-header.md` and `docs/widgets/textual-showcase.md` don't exist. Creating them is its own task (`leap-create-widget-doc`).

---

## Verification

Local site `http://ab.local`, Elementor test page `post=94020`. Build (`grunt minify-js` in Pro) is triggered by you, not by me.

**Editor** — the highest-risk surface, since decision 10 pins in the preview.

1. Enable Fill on Scroll + Lock on a Heading. Confirm the switcher and the notice appear, and that the notice text names title _and_ description.
2. Toggle the lock off and on ~10 times. Inspect the container's parent chain — exactly zero or one `.pin-spacer` should exist at any time. Accumulating spacers means `kill(true)` isn't reached.
3. With the lock on: drag the widget to another container, drag a new widget in beside it, and open the right-click menu. Element handles and sortable must still work.
4. Change the speed slider with the lock on — the pin distance must follow without a page reload.

**Frontend**

5. Short section (heading only, ~200px): locks centred, fills, releases. Content below must not jump on release.
6. Tall section (≥100vh): locks with the section top at the viewport top.
7. Dual Heading with **both** title and description fill on — the nested-group case. One lock, title words fill first, then description words, all inside the same pin.
8. Textual Showcase with 3+ filling repeater items — one lock, items fill in order.
9. **Two locking widgets in one container** — first locks, second fills unlocked, layout intact (decision 13).
10. Two locking widgets in _different_ containers — both lock, in sequence.
11. Lock off — diff the behaviour against current `develop`. Must be identical; the unlocked path is not to change.
12. Resize the window mid-page and re-scroll; then check on a phone that address-bar collapse doesn't jump the pin (decision 7).
13. Free plugin only (Pro deactivated): the `.premium-error-notice` still renders, unchanged.

**Static**

14. `composer f` on the three widget files (PHPCS WordPress-Extra). Run by you.
