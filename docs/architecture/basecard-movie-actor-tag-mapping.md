# BaseCard → MovieCard / ActorCard / TagCard Mapping

## 1) ASCII design map

### BaseCard (current)

```text
+--------------------------------------------------+
| .ui-card (cardClass)                             |
|  click => emit('click')                          |
|                                                  |
|  +--------------------------------------------+  |
|  | .ui-card-body (bodyClass)                  |  |
|  |                                            |  |
|  |  <slot />  (all content injected by child) |  |
|  +--------------------------------------------+  |
+--------------------------------------------------+

Shared style from BaseCard:
- deep(.ui-card-img-top) margin-bottom: 0.3rem
- deep(.tag-cover-placeholder) margin-bottom: 0.3rem
```

### MovieCard (current)

```text
+--------------------------------------------------+
| BaseCard(movie-card)                             |
|  [Cover image]                                   |
|   └─ top-right overlay: views, downloads         |
|                                                  |
|  [Heading row]                                   |
|   ├─ formatted_code/code                         |
|   └─ release date                                |
|  [Title line]                                    |
|  [Meta badge row] size                           |
|  [Recommendation badges] actors/tags             |
|  [Actor badges] +N                               |
|  [Tag badges] +N (active-highlight support)      |
|  [Action row]                                    |
|   ├─ download/login                              |
|   └─ like + watchlist + featured(admin) + rating |
|  [Description block]                             |
+--------------------------------------------------+
```

### ActorCard (current)

```text
+--------------------------------------------------+
| BaseCard(actor-card)                             |
|  [Cover image]                                   |
|   ├─ top-left overlay: JAV count                 |
|   ├─ top-right overlay: favorites, views, rate   |
|   └─ bottom-left chip: age filter                |
|                                                  |
|  [Heading row]                                   |
|   ├─ actor name                                  |
|   └─ birth date + age text                       |
|  [Action row]                                    |
|   └─ like + featured(admin) + rating(display)    |
|  [Bio description block] (max 4 lines)           |
|   └─ Height/Size/From/Blood/Hobby/Skill...       |
+--------------------------------------------------+
```

### TagCard (current)

```text
+--------------------------------------------------+
| BaseCard(tag-card)                               |
|  [Placeholder cover with tag icon]               |
|   └─ top-left overlay: JAV count                 |
|                                                  |
|  [Heading row]                                   |
|   └─ tag name                                    |
|  [Action row]                                    |
|   └─ like + featured(admin) + rating(display)    |
+--------------------------------------------------+
```

---

## 2) Field mapping table (expected vs current)

Legend:
- ✓ = field exists/mapped in this card
- ~ = equivalent but with different data/behavior
- ✗ = missing in this card

| Field / Slot Area | BaseCard | MovieCard | ActorCard | TagCard |
|---|---|---|---|---|
| `cardClass` prop | ✓ | ✓ (passes value) | ✓ (passes value) | ✓ (passes value) |
| `bodyClass` prop | ✓ | ✓ (passes value) | ✓ (passes value) | ✓ (passes value) |
| `click` emit from shell | ✓ | ✓ (`@click` openDetail) | ✓ (`@click` openDetail) | ✓ (`@click` openDetail) |
| Cover media container | ~ (slot only) | ✓ image | ✓ image | ✓ placeholder icon |
| Top overlay metrics | ✗ | ✓ views/downloads (right) | ✓ javs (left) + favorites/views/rate (right) | ✓ javs (left) |
| Primary heading | ✗ | ✓ code + title | ✓ actor name | ✓ tag name |
| Secondary heading/date line | ✗ | ✓ release date | ~ birth date + age | ✗ |
| Meta badges row | ✗ | ✓ size | ✗ | ✗ |
| Recommendation badges | ✗ | ✓ because-liked actors/tags | ✗ | ✗ |
| Related entities badges row | ✗ | ✓ actor badges + tag badges | ✗ | ✗ |
| Extra count badge (+N) | ✗ | ✓ for extra actors/tags | ✗ | ✗ |
| Download/Login CTA | ✗ | ✓ | ✗ | ✗ |
| Like action | ✗ | ✓ toggle API | ✓ toggle API | ✓ toggle API |
| Watchlist action | ✗ | ✓ toggle API | ✗ | ✗ |
| Featured action (admin) | ✗ | ✓ toggle API | ✓ toggle API | ✓ toggle API |
| Rating action | ✗ | ✓ interactive write/update | ~ display only (disabled) | ~ display only (disabled) |
| Description block | ✗ | ✓ movie description | ~ bio lines (max 4) | ✗ |
| Active-tag highlight logic | ✗ | ✓ | ✗ | ✗ |
| Tooltip system | ✗ | ✓ `movie-tooltip-*` | ✓ `actor-tooltip-*` | ✓ `tag-tooltip-*` |

---

## 3) Gap summary against expectation

Expectation: BaseCard should hold all fields/structure, and Movie/Actor/Tag should mainly provide content/value mapping.

Current state:
1. BaseCard is only a shell wrapper (`.ui-card` + `.ui-card-body` + slot).
2. Most UI structure and feature logic are implemented separately in each specific card.
3. ActorCard and TagCard are not strict structural clones of MovieCard; they differ in rows/blocks/actions.

So, the current implementation does **not** yet satisfy “BaseCard has all fields and others only map values”.
