# Invitation and greeting catalog

Adapted from `softmitend/invite-me`, branch `v2`, commit `5990cfbd5fc4604aacd514a87781a5c06738268e`. It preserves multi-category templates, gallery covers, external image storage, preview URLs, specifications, and scheduled percentage/fixed discounts. The old modal forms are replaced with dedicated Blade pages and grouped fields.

Public pages: `/catalog` and `/catalog/{slug}`. Home's invitation and greeting service cards link to the catalog; the public navbar stays Home/Tracking.

Admin menus: Katalog undangan, Diskon katalog, and Kategori katalog. Create/edit forms keep existing photos unless explicitly removed. Archive hides a template without deleting its underlying files. Each template supports up to 10 photos.

## External photo storage

Copy the old project's `CATALOG_S3_*` values into this project's local `.env` using `.env.catalog.example` as the key list. Do not commit credentials. The upload disk is `catalog_cloud`, with the S3 adapter used for Cloudflare R2. Laravel's [filesystem documentation](https://laravel.com/docs/13.x/filesystem) describes this adapter.

Upload flow: browser → one authenticated request per image (JPG/PNG/WEBP, at most 4 MB) → R2 → verification → encrypted temporary upload ticket → atomic catalog save. The database stores image URLs/paths and metadata, never image bytes. Managed images are served through `/catalog-images/{id}`, preserving the proxy approach from invite-me v2 without requiring bucket CORS or a public bucket URL.

Without R2 credentials, uploading stays disabled; existing HTTPS image links remain usable. External links can be entered directly in the gallery. A temporary upload ticket belongs to its uploading admin, expires after one hour, and can be claimed only once. Removing images removes catalog references, not unrelated bucket files.

## Import actual existing templates

The repository's main seed data uses placeholder demo domains and is intentionally not published as real merchandise. To import the actual old database, set `INVITE_DATABASE_URL` to a read-only PostgreSQL connection and run:

```sh
php artisan catalog:import-invite
```

Alternatively supply a UTF-8 JSON export with a `catalogs` array containing template fields, `categories` (name/slug), `images` (external_url or disk/path), `specifications` (label/value), and optional `discount` (name/type/value/schedule/status):

```sh
php artisan catalog:import-invite --file=path/to/catalog-export.json
```

Repeated imports skip existing slugs. Use `--replace` explicitly to replace their content. The import is transactional and does not copy users, passwords, reviews, or orders. HTTPS image links and R2 file references are preserved; missing local images cannot be recovered by importing database records. Configure the same R2 bucket before displaying imported managed images.

The importer also preserves stored rating averages, review counts, and catalog timestamps. A top-level `categories` array imports category names, visibility, and display order, including categories that do not yet contain templates. Individual reviews and reviewer identities are not copied.
