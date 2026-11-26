<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Overview') &middot; book-store</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
    <div class="admin-shell">
        <div class="sidebar-scrim" data-sidebar-close></div>
        <aside class="admin-sidebar" id="admin-sidebar">
            <a href="{{ route('admin.dashboard') }}" class="brand-lockup">
                <span class="brand-mark"><x-icon name="book-open" class="h-6 w-6"/></span>
                <span><strong>book-store</strong><small>BOOKS ADMIN</small></span>
            </a>

            <div class="sidebar-label">WORKSPACE</div>
            <nav class="sidebar-nav" aria-label="Main navigation">
                @can('dashboard.view')<a href="{{ route('admin.dashboard') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.dashboard')])><x-icon name="dashboard"/><span>Overview</span></a>@endcan
                @can('categories.view')<a href="{{ route('admin.categories.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.categories.*')])><x-icon name="categories"/><span>Categories</span></a>@endcan
                @can('subcategories.view')<a href="{{ route('admin.subcategories.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.subcategories.*')])><x-icon name="layers"/><span>Subcategories</span></a>@endcan
                @can('brands.view')<a href="{{ route('admin.brands.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.brands.*')])><x-icon name="publishers"/><span>Publishers</span></a>@endcan
                @can('books.view')<a href="{{ route('admin.products.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.products.*')])><x-icon name="books"/><span>Books</span></a>@endcan
                @can('inventory.view')<a href="{{ route('admin.inventory') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.inventory')])><x-icon name="inventory"/><span>Inventory</span></a>@endcan
                @can('media.view')<a href="{{ route('admin.media.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.media.*')])><x-icon name="media"/><span>Media library</span></a>@endcan
            </nav>

            @if (auth()->user()->can('banners.view') || auth()->user()->can('menu-master.view') || auth()->user()->can('contact-page.view') || auth()->user()->can('about-page.view') || auth()->user()->can('footer.view'))
                <div class="sidebar-label sidebar-label--lower">WEBSITE</div>
                <nav class="sidebar-nav" aria-label="Website management">
                    @can('banners.view')<a href="{{ route('admin.banners.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.banners.*')])><x-icon name="banner"/><span>Banner Management</span></a>@endcan
                    @can('menu-master.view')<a href="{{ route('admin.menus.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.menus.*')])><x-icon name="menu"/><span>Menu Master</span></a>@endcan
                    @can('contact-page.view')<a href="{{ route('admin.contact-page-settings.edit') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.contact-page-settings.*')])><x-icon name="contact"/><span>Contact Us Page Master</span></a>@endcan
                    @can('about-page.view')<a href="{{ route('admin.about-page-settings.edit') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.about-page-settings.*')])><x-icon name="about"/><span>About Us Page Master</span></a>@endcan
                    @can('footer.view')<a href="{{ route('admin.footer.edit') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.footer.*')])><x-icon name="layers"/><span>Footer Management</span></a>@endcan
                </nav>
            @endif

            @if (auth()->user()->can('roles.view') || auth()->user()->can('permissions.view') || auth()->user()->can('users.view'))
                <div class="sidebar-label sidebar-label--lower">ACCESS CONTROL</div>
                <nav class="sidebar-nav" aria-label="Access control">
                    @can('roles.view')<a href="{{ route('admin.roles.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.roles.*')])><x-icon name="shield"/><span>Roles</span></a>@endcan
                    @can('permissions.view')<a href="{{ route('admin.permissions.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.permissions.*')])><x-icon name="key"/><span>Permissions</span></a>@endcan
                    @can('users.view')<a href="{{ route('admin.users.index') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.users.*')])><x-icon name="users"/><span>Admin users</span></a>@endcan
                </nav>
            @endif

            @can('profile.view')
                <div class="sidebar-label sidebar-label--lower">PREFERENCES</div>
                <nav class="sidebar-nav">
                    <a href="{{ route('admin.settings.edit') }}" @class(['nav-link', 'is-active' => request()->routeIs('admin.settings.*')])><x-icon name="settings"/><span>My profile</span></a>
                </nav>
            @endcan

            <div class="sidebar-bottom">
                <!-- <div class="sidebar-note">
                    <span class="sidebar-note-icon"><x-icon name="trend" class="h-4 w-4"/></span>
                    <p><strong>Your library, in focus.</strong><br>Everything you need to keep your shelves in order.</p>
                </div>   -->
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="nav-link nav-logout" type="submit"><x-icon name="logout"/><span>Sign out</span></button>
                </form>
            </div>
        </aside>

        <div class="admin-main">
            <header class="topbar">
                <div class="topbar-left">
                    <button class="icon-button mobile-menu-button" type="button" aria-label="Open navigation" data-sidebar-open><x-icon name="layers"/></button>
                    <div class="breadcrumb"><span>Workspace</span><x-icon name="arrow" class="h-3 w-3 rotate-[-45deg]"/><strong>@yield('title', 'Overview')</strong></div>
                </div>
                <div class="topbar-right">
                    <span class="today-label">{{ now()->format('l, F j') }}</span>
                    <span class="topbar-divider"></span>
                    <details class="account-menu" data-account-menu>
                        <summary class="topbar-profile" aria-label="Open account menu">
                            <x-user-avatar :user="auth()->user()" class="avatar"/>
                            <span class="topbar-user"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->primaryRoleDisplayName() }}</small></span>
                            <span class="account-menu-caret" aria-hidden="true"></span>
                        </summary>
                        <div class="account-menu-panel">
                            @can('profile.view')<a href="{{ route('admin.settings.edit') }}" class="account-menu-item"><x-icon name="user" class="h-4 w-4"/> My profile</a><div class="account-menu-divider"></div>@endcan
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button class="account-menu-item account-menu-item--logout" type="submit"><x-icon name="logout" class="h-4 w-4"/> Sign out</button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            <main class="page-content">
                @if (session('success'))
                    <div class="toast toast-success" role="status" data-toast><span class="toast-icon"><x-icon name="check"/></span><span>{{ session('success') }}</span><button type="button" aria-label="Dismiss notification" data-toast-close><x-icon name="close" class="h-4 w-4"/></button></div>
                @endif
                @if (session('error'))
                    <div class="toast toast-error" role="alert" data-toast><span class="toast-icon"><x-icon name="close"/></span><span>{{ session('error') }}</span><button type="button" aria-label="Dismiss notification" data-toast-close><x-icon name="close" class="h-4 w-4"/></button></div>
                @endif
                @yield('content')
            </main>
            <footer class="page-footer"><span>&copy; {{ now()->year }} book-store</span><span>Made for the love of reading.</span></footer>
        </div>
    </div>

    <dialog class="confirm-dialog" id="confirm-dialog">
        <form method="dialog" class="confirm-dialog-card">
            <span class="dialog-warning"><x-icon name="trash" class="h-5 w-5"/></span>
            <h2>Delete this item?</h2>
            <p data-confirm-message>This action cannot be undone.</p>
            <div class="dialog-actions">
                <button class="button button-muted" value="cancel">Keep it</button>
                <button class="button button-danger" type="button" data-confirm-submit>Yes, delete</button>
            </div>
        </form>
    </dialog>
</body>
</html>
