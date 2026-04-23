# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Plugin Does

A WordPress media offloading plugin that integrates the WordPress media library with Publitio cloud storage/CDN. On media upload, files are sent to Publitio and all local URLs (in content, srcsets, featured images) are replaced with Publitio CDN URLs. Supports images, videos, audio, and documents.

## Development Commands

There is no build system. This is a pure PHP/JS WordPress plugin.

**Composer** (PHP dependencies):
```bash
composer install          # Install dependencies (Publitio SDK, guzzlehttp/psr7)
composer update           # Update dependencies
```

There are no automated tests, linting tools, or CI/CD workflows.

## Architecture

### Service Bootstrap

`publitio-offloading.php` (plugin root) defines constants and registers activation/deactivation hooks, then delegates everything to `PWPO_Init`.

`includes/class-publitio-offloading-init.php` — `PWPO_Init::pwpo_get_services()` acts as a simple service container, instantiating and returning `PWPO_Admin` and `PWPO_Offload`.

### Core Classes

| File | Class | Role |
|------|-------|------|
| `includes/class-publitio-offloading.php` | `PWPO_Offload` | Main engine: hooks into WP upload/display pipeline, URL transformation, srcset generation |
| `includes/publitio_api_service.php` | `PublitioApiService` | Wraps the Publitio PHP SDK; handles uploads, metadata reads/writes, URL generation |
| `admin/class-publitio-offloading-admin.php` | `PWPO_Admin` | Admin settings page, AJAX handlers for bulk sync/delete/restore |
| `includes/class-publitio-offloading-auth-service.php` | `PWPO_AuthService` | Credential validation and `get_option`/`update_option` helpers |

### Key Data Flow

**Upload path**: `add_attachment` action → `PWPO_Offload::pwpo_upload_file_to_publitio()` → `PublitioApiService` → Publitio SDK → stores Publitio file ID and URL in attachment post meta.

**Display path**: WordPress `the_content`, `image_downsize`, `wp_calculate_image_srcset`, and related filters → `PWPO_Offload` intercepts and rewrites URLs to Publitio CDN URLs, building dimension-specific URLs on the fly.

**Admin AJAX**: `wp_ajax_*` actions in `PWPO_Admin` handle settings saves, credential checks, and bulk operations (sync all media, delete from Publitio, restore local).

### Configuration Storage

All settings are stored as WordPress options (`get_option`/`update_option`). Publitio credentials (API key/secret), enabled media types, quality settings, domain/CNAME, and feature toggles are all options-based — no config files.

### Notable Implementation Details

- **Responsive images**: URL transformation builds Publitio-formatted dimension URLs (e.g. `/w_800,h_600/`) to replace WordPress-generated srcset entries.
- **Divi theme support**: Special regex patterns handle Divi's non-standard image markup.
- **Graceful fallback**: If a local file is missing, the plugin returns the Publitio URL rather than a broken link.
- **Frontend libraries**: Admin UI uses Slim Select 3.4.3 and Toastify.js, both loaded from CDN (not bundled).

## WordPress.org Publishing (_builds/)

`_builds/publitio-offloading/` is an **SVN working copy** of the WordPress Plugin Directory repository. It is not part of the plugin code itself.

- `trunk/` — copy of the current release; commit here to publish to wordpress.org
- `tags/<version>/` — versioned snapshots WordPress.org serves to users
- `assets/` — marketplace images (banners, icons, screenshots) separate from plugin code

### Release steps (each version bump)

1. **Sync source → trunk** (exclude non-distribution files):
   ```bash
   rsync -av \
     --exclude='.git/' \
     --exclude='_builds/' \
     --exclude='.claude/' \
     --exclude='CLAUDE.md' \
     --exclude='.DS_Store' \
     --exclude='.gitignore' \
     --exclude='publitio-offloading.zip' \
     /Users/ob1y2k/Projects/publitio_wp_offloading/ \
     /Users/ob1y2k/Projects/publitio_wp_offloading/_builds/publitio-offloading/trunk/
   ```
2. **Update `Stable tag`** in `_builds/publitio-offloading/trunk/README.txt` to match the new version. Also update it in the source `README.txt`.
3. **Handle SVN additions/deletions**: run `svn status` to spot `?` (needs `svn add`) or stale files (needs `svn delete`).
4. **Commit trunk**: `svn commit trunk/ -m "Updating trunk to vX.X.X" --username publitio`
5. **Create tag** (server-side copy — one command, no local files):
   ```bash
   svn copy \
     https://plugins.svn.wordpress.org/publitio-offloading/trunk \
     https://plugins.svn.wordpress.org/publitio-offloading/tags/X.X.X \
     -m "Tagging version X.X.X" --username publitio
   ```
6. **Verify**: `svn ls https://plugins.svn.wordpress.org/publitio-offloading/tags/`

## Dependencies

- **PHP**: WordPress 5.0.1–6.9
- **Composer**: `publitio/publitio` (dev-master), `guzzlehttp/psr7` ^1.9
- **WordPress**: jQuery (standard WP), no plugin dependencies
