---
name: work-trello-items
description: Pick up ready work from the WebLab Trello board and actually build it — for each card carrying the green "Ready to start" label, read the card, implement its requirements in this WebLab repo, verify the build and tests, then move the card to the Value Review list and swap the green label for the sky "In progress" label so a human can review it. Use this whenever the user asks to work the board, pick up the next ready card, do the green cards, implement what's ready in Trello, or push finished cards for review — even if they don't name the board, list, or labels exactly. The card is only moved after its code is written and verified; moving a card without implementing it defeats the purpose.
---

# Work the WebLab board: implement ready cards, then hand them to Value Review

The WebLab board is how WebLab work is tracked. A green label means "this is specified and
ready to be built." This skill closes the loop: green card in, working code out, card parked
in **Value Review** for a human to look at.

The single most important thing to understand: **moving a card to Value Review is a claim
that the work exists in the repo and builds.** It is not a claim that the work is correct —
that judgment is the reviewer's, which is exactly why the card stops here instead of going to
Done. Never move a card on label mechanics alone; implementation is the job, the move is
bookkeeping at the end of it.

**Done is the human's call, not yours.** Don't move cards into Done and don't apply the blue
`Done` label. A reviewer promotes the card from Value Review once they're satisfied.

## Board coordinates

Stable ARIs — use them to skip discovery. If one 404s, see **Re-resolving coordinates**.

| Role | Thing | ARI |
|---|---|---|
| Board | `WebLab` | `ari:cloud:trello::board/workspace/68772d4471812c8969fb9e53/6aac49b7204e380c78d2d62d` |
| **Destination list** | `Value Review` | `ari:cloud:trello::list/workspace/68772d4471812c8969fb9e53/6aac6e99d91b97cc28d265db` |
| **Destination label** | sky `In progress` | `ari:cloud:trello::label/workspace/68772d4471812c8969fb9e53/6aac6f184879d7ec8b06e316` |
| Source label | green `Ready to start` | `ari:cloud:trello::label/workspace/68772d4471812c8969fb9e53/6aac49b7204e380c78d2d633` |
| Read-only, for skip checks | `Done` list | `ari:cloud:trello::list/workspace/68772d4471812c8969fb9e53/6aac49d1da8132f643cfd9df` |
| Read-only, never apply | blue `Done` label | `ari:cloud:trello::label/workspace/68772d4471812c8969fb9e53/6aac49b7204e380c78d2d638` |

Board URL: https://trello.com/b/XIHqSuKL/weblab

Two easy mistakes worth pre-empting: the label is `In progress` with a lowercase p, and its
color is **sky**, not blue — blue belongs to `Done`. Match the source label on the color
`green` and treat its name as a hint, since colors are what people see and names get edited.

The board also carries `Description needed` (yellow), `Blocked` (red), `Questions from prod`
(orange), and `Getting info` (purple). This skill doesn't apply them, but they tell you the
team's vocabulary for a card that can't move forward.

## Tools

The Trello tools are namespaced `mcp__<uuid>__trello*` and that UUID changes between sessions,
so never hardcode it. Load by name suffix in one call:

```
ToolSearch query: "select:trelloReadCard,trelloWriteCard,trelloReadBoard"
```

Nothing returned means the Trello connector isn't enabled in this session — say so and point
the user at the Connectors UI rather than trying to reach Trello with curl.

## Procedure

Work **one card at a time, start to finish**. Batching all the reads, then all the code, then
all the moves sounds efficient but a failure halfway through leaves the board in a state
nobody can interpret. One card fully landed is always a good place to stop.

### 1. Find the ready cards

`trelloReadCard` with `action: "list_by_board"`, the board ARI, `limit: 50`. Paginate with
`nextCursor` while `hasNextPage` — a partial read looks exactly like "nothing to do".

Keep cards whose `labels` contain a `green` label. If a card shows `labelsHasMore: true`, its
labels were truncated: call `action: "get"` on it before judging. Skip anything already sitting
in Value Review or Done — those have been through this once already.

Tell the user which cards you're about to work, in order, then start. You don't need their
approval to begin — they invoked this — but they should be able to see what you picked up.

### 2. Read the card properly

Call `trelloReadCard` with `action: "get"` on the card. The `desc` is the spec; the checklists
and comments frequently carry the real acceptance criteria and later corrections. Read all of
it before writing code.

If the card is too vague to implement, or its description contradicts what's already in the
repo, **do not guess at an interpretation and build it anyway**. Leave the card green and in
place, and report what's ambiguous. A wrong implementation costs more to unpick than a card
that sat still. (This is real: the "Diagnosticos" card lists invoicing sub-items that
duplicate the Facturación card, almost certainly copy-paste.)

### 3. Implement it in this repo

Read `CLAUDE.md` at the project root for the stack and conventions before touching anything —
it covers Inertia page resolution, the sidebar layout, the Tailwind `bg-brand` tokens, and the
Catálogos Index/Create/Edit pattern. Follow the surrounding code's idiom rather than importing
patterns from elsewhere.

Most cards on this board are UI/nav work and touch these:

- Sidebar nav groups: `resources/js/Layouts/AuthenticatedLayout.jsx` — collapsible groups,
  each with its own `useState`, `groupBtn(...)`, and a list of `NavLink`s. There is a second
  copy of the nav in the mobile menu further down the same file; update both, or the change
  only exists on desktop.
- Pages: `resources/js/Pages/<Section>/<Name>.jsx`, resolved from the string passed to
  `Inertia::render()`.
- Routes: `routes/web.php`; controllers in `app/Http/Controllers/`.

When a card asks for menu items whose destination pages don't exist yet, mirror how the
existing `Facturación` group does it — `NavLink href="#"` placeholders — rather than inventing
routes that will 500 when clicked. Where a route genuinely does exist, link it for real and
gate it with `puedeVer('<modulo>')` the way the Catálogos group does. Match the neighbours;
don't half-build a feature the card didn't ask for.

### 4. Verify before you touch the board

This gate is what makes the handoff trustworthy — the reviewer should be looking at the work,
not discovering it doesn't compile. Run what's relevant to the change:

```bash
npm run build          # frontend changes must compile
composer run test      # backend/route changes
./vendor/bin/pint      # PHP formatting, if PHP changed
```

A frontend-only card doesn't need the full PHP test suite, but it does need to build. Use
judgment about scope, not about whether to verify at all.

**If verification fails, stop on that card.** Fix it if the cause is your own change. If it's
pre-existing breakage you can't attribute to this card, leave the card green and in place and
report it — handing a reviewer a red build is exactly the false claim this skill exists to
avoid.

Leave the changes in the working tree. Don't commit or branch: this repo carries a lot of
uncommitted work, so an automatic commit would sweep up unrelated changes. The user commits on
their own flow.

### 5. Move the card to Value Review

Only once step 4 passed, with `trelloWriteCard`:

1. `action: "move"` — `cardId: <card ARI>`, `listId: <Value Review list ARI>`, `pos: "top"`
2. `action: "attach_label"` — `cardId`, `labelId: <sky In progress label ARI>`
3. `action: "detach_label"` — `cardId`, `labelId: <green label ARI>`

Sky on before green off: a card wearing both reads as "in transit" if something fails between
the calls, whereas a card wearing neither looks like untracked work.

Pass the card's `id` (`ari:cloud:trello::card/...`) as `cardId` — URLs and short links are
rejected.

Then move to the next card and return to step 2.

### 6. Report

Per card, say what you built, not just that you moved it. The reviewer reads this to know
where to look:

```
Create a menu Configuracion General — https://trello.com/c/N9pFAQ79/...
  Added the Configuración General group to AuthenticatedLayout.jsx (sidebar + mobile menu)
  with 5 items; Catálogo de Áreas links to areas.index, the rest are "#" placeholders.
  npm run build passed. → moved to Value Review, labeled In progress.
```

Then list, separately and plainly, every card you did **not** move and why — ambiguous spec,
failing build, pre-existing breakage. That list is the most useful part of the report, so
don't bury it under the successes. If nothing was green: "No cards are ready right now."

## Re-resolving coordinates

If a hardcoded ARI 404s, or this is pointed at another board:

- Board: `trelloSearch`, `action: "search_boards"`.
- Lists: `trelloReadList`, `action: "list_by_board"` — match Value Review case-insensitively.
- Labels: `trelloReadBoard`, `action: "list_labels"` — green for the source; for the
  destination match the name `In progress` first, since sky and blue are easy to confuse and
  picking blue would wrongly mark the card Done.

Tell the user what drifted, then update the table above so the next run is fast again.

## Scope

Implement what the card asks for and no more. Don't archive cards, mark them complete, move
anything to Done, reassign members, edit descriptions, or "while I'm in here" refactor
surrounding code — a handoff that quietly does extra makes the board and the diff both harder
to trust.
