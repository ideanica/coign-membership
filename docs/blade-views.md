# Blade layouts and shared UI

Every page inherits `layouts/base.blade.php`, which owns the document, metadata, fonts, and common CSS. Use a page layout rather than copying HTML headers and footers:

- `layouts.public`: Home and public order tracking; shared Home/Tracking navbar and footer.
- `layouts.member`: Member profile with custom page content.
- `layouts.portal`: Admin pages and member service pages; standard title, alerts, actions, and admin sidebar when appropriate.
- `layouts.auth`: Login card and the large brand logo.

```blade
@extends('layouts.public')
@section('title', 'Page title')
@section('content')
    <section class="neo-card">Your page content</section>
@endsection
```

Use `@section('main-class', '')` for a full-width public page, and `@section('robots', 'noindex, nofollow')` for private or order-related pages. Add page-specific assets with `@push('styles')` and `@push('scripts')`; use `@push('body-end')` for dialogs or embedded JSON.

Shared components include `<x-navbar>`, `<x-footer>`, `<x-brand-link>`, `<x-brand-logo>`, `<x-field>`, `<x-icon>`, and `<x-pager>`. Public and member navigation both live in `components/navbar.blade.php`; admin menus live in `shared/admin-sidebar.blade.php`.

Home sections live in `home/sections/` and are composed with `@include`. Service content is supplied by `HomeController` from `config/home.php`. Registration is disabled. Only authorized admins create member accounts through `/admin/members/create`.
