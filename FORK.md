# Fork: filament/spatie-laravel-tags-plugin

This is a fork of [`filament/spatie-laravel-tags-plugin`](https://github.com/filamentphp/spatie-laravel-tags-plugin) maintained at [`drifteaur/spatie-laravel-tags-plugin`](https://github.com/drifteaur/spatie-laravel-tags-plugin) on the `feature/searchable-tags` branch.

## Why This Fork Exists

The upstream plugin provides a `SpatieTagsInput` Filament form component backed by `spatie/laravel-tags`. It works well for small tag sets, but for projects with **100k+ tags**, the default `<datalist>`-based suggestions are impractical. This fork adds:

1. **Searchable tag input** with server-side async search and a dropdown UI
2. **Comma paste splitting** so pasting `"tag1, tag2, tag3"` creates 3 separate tags
3. **Copy tags button** to copy all current tags as a comma-separated string

## Changes from Upstream

### Commit `a893d56` — Searchable tag input with async search

**Problem:** The upstream component uses a `<datalist>` element for tag suggestions, which requires loading all suggestions at page render time. With 100k+ tags this is not viable.

**Solution:** Added a `searchable()` method to `SpatieTagsInput` that enables a completely different UI path with server-side search.

#### Files changed

**`src/SpatieTagsInput.php`**
- Added `searchable()` configuration method (enables server-side search mode)
- Added `searchDebounce()` — configurable debounce in ms (default: 300)
- Added `minSearchLength()` — minimum characters before search triggers (default: 2)
- Added `noSearchResultsMessage()`, `searchingMessage()`, `searchPromptMessage()` — UI text
- Added `getSearchResultsForJs()` — Livewire-callable method that queries `Tag::scopeContaining()` and returns matching tag names, filtered by the component's configured tag type

**`resources/js/components/spatie-tags-input.js`** (new Alpine component)
- Full Alpine.js component with debounced async search via `$wire.callSchemaComponentMethod()`
- Keyboard navigation (ArrowUp/Down, Enter to select, Escape to close)
- Highlighted result tracking
- Race condition protection via `activeSearchId` counter
- Paste handler to split pasted text by configured `splitKeys`
- All standard tag operations: create, delete, reorder

**`resources/views/components/spatie-tags-input.blade.php`**
- When `$isSearchable` is true: renders the new Alpine component with a search dropdown (loading state, prompt message, no results message, results list with hover/keyboard highlighting)
- When `$isSearchable` is false: renders the original `<datalist>`-based UI unchanged

**`dist/components/spatie-tags-input.js`**
- Minified esbuild bundle of the new Alpine component

**`package.json`** (new)
- Added esbuild as devDependency for building the Alpine component bundle

**`src/SpatieTagsInputServiceProvider.php`**
- Registered the new Alpine component asset (`spatie-tags-input`) via `FilamentAsset::registerScriptData()`

#### Usage

```php
SpatieTagsInput::make('tags')
    ->searchable()           // enables server-side search
    ->searchDebounce(300)    // ms debounce (default: 300)
    ->minSearchLength(2)     // min chars before search (default: 2)
    ->type('public')         // optional: filter by tag type
```

---

### Commit `b680c3c` — Comma paste splitting and copy-tags button

**Problem:** When `splitKeys` is empty (the default), pasting `"tag1, tag2, tag3"` creates a single tag with the entire string. Also, there was no way to copy existing tags for transfer between records.

**Solution:** Modified the paste handler to always split by comma regardless of `splitKeys`, and added a copy button.

#### Files changed

**`resources/js/components/spatie-tags-input.js`**
- Modified `x-on:paste` handler: always includes comma (`,`) as a split character alongside any configured `splitKeys`
- Added `showCopiedFeedback` state (boolean, for UI feedback)
- Added `copyTags()` method: joins tags with `", "`, writes to clipboard via `navigator.clipboard.writeText()`, shows a checkmark icon for 2 seconds

**`resources/views/components/spatie-tags-input.blade.php`**
- Added copy button (clipboard icon) positioned absolutely at the right side of the tags area
- Uses `x-show` to toggle between clipboard icon and green checkmark on copy
- Inline SVG icons with explicit `width`/`height` attributes (Tailwind utility classes don't work in vendor Blade files since they aren't scanned by Tailwind v4)
- Tags container gets `padding-right: 2rem` to avoid overlap with the copy button
- Button is vertically centered with `top: 50%; transform: translateY(-50%)`

**`dist/components/spatie-tags-input.js`**
- Rebuilt minified bundle

#### Technical notes

- SVG icons use inline `width="16" height="16"` attributes instead of Tailwind classes because vendor Blade files are not in Tailwind v4's content scan path
- The copy button uses `x-show` (not `<template x-if>`) for icon toggling because nested `x-if` templates inside another `x-if` template cause rendering issues in Alpine.js
- The button uses absolute positioning to avoid breaking the `fi-fo-tags-input-tags-ctn` top border (horizontal divider line)

## Building the JS Bundle

```bash
cd vendor/filament/spatie-laravel-tags-plugin
npx esbuild resources/js/components/spatie-tags-input.js \
  --bundle --minify --format=esm \
  --outfile=dist/components/spatie-tags-input.js
```

Or inside DDEV:

```bash
ddev exec "cd /var/www/html/vendor/filament/spatie-laravel-tags-plugin && npx esbuild resources/js/components/spatie-tags-input.js --bundle --minify --format=esm --outfile=dist/components/spatie-tags-input.js"
```

## Merging Upstream Changes

This fork diverges from upstream in these key areas:

1. **`SpatieTagsInput.php`** — significant additions (searchable methods, search result handler). Upstream changes to this file will likely need manual merge.
2. **Blade template** — the searchable path is entirely new (`@if ($isSearchable) ... @else ... @endif`). The non-searchable path mirrors upstream, so changes there should merge cleanly.
3. **Alpine JS component** — entirely new file (`spatie-tags-input.js`). Won't conflict with upstream unless they add a similar feature.
4. **Service provider** — minor addition for asset registration. Should merge easily.
5. **`package.json`** — new file for esbuild. Won't conflict.

**Recommended merge strategy:** When pulling upstream changes, review `SpatieTagsInput.php` and the Blade template carefully. The JS component and build tooling are isolated additions that shouldn't conflict.
