# Protector Package — Developer Notes

## Permission check was unreachable for direct content loads (fixed 2026-08-02)
`protector_content_load()` filters content-loading queries via a row-level WHERE clause
(anyone lacking the right role just gets zero rows back) and separately calls
`protector_content_verify_access()`, which is meant to catch that case up front and call
`$gBitSystem->fatalPermission()` (login prompt for anonymous, "Permission denied" for a
logged-in user lacking the role) instead of leaving the caller to fall through to a
generic "page not found".

Two bugs combined to make that path dead code everywhere except `LibertyComment`:

1. Every other `content_load_sql_function` call site (`BitPage::load()`, `BitBlog`,
   `BitBlogPost`, `FisheyeGallery`, `FisheyeImage`, `StockAssembly`, `StockComponent`,
   `StockMovement`, `RoleUser`) omitted the 6th `$pObject` argument to `getServicesSql()`,
   so `protector_content_load( $pContent = null )` always received `null` and could never
   run its check. Fixed by passing `$this` at each call site, matching the pattern
   `LibertyComment.php` already used.
2. `protector_content_verify_access()` then guarded on `$pContent->isValid()` — but
   `isValid()` is overridden per content type, and some overrides (e.g. `BitPage::isValid()`
   checks `mPageId`) only become true *after* a successful `load()`. Protector's actual
   precondition is just "do we have a valid content_id to check against
   `liberty_content_role_map`" — fixed by checking `verifyId( $pContent->mContentId )`
   directly instead of trusting each subclass's `isValid()` semantics.

Confirmed `mContentId` (unlike `mPageId`) is populated before `load()` runs whenever
content is resolved the normal way — via `LibertyBase::getLibertyObject( $contentId )` →
`getNewObject()` → `new $class( null, $contentId )` — which is what every real dispatch
path (page name, page_id, or content_id in the URL) goes through for wiki pages. Direct
`new BitPage( $pageId )` construction without going through that resolution path (a few
admin/list scripts do this) still won't get the early permission check — same as before,
not a regression.

Verified live on desktop against `/wiki/New+map+sources` for all three states: anonymous
(login prompt), logged-in without the required role ("Permission denied"), logged-in with
the required role (page loads normally) — using the no-password `users_cnxn` cookie
technique (see top-level CLAUDE.md's Session/Auth section).
