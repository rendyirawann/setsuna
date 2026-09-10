{{-- Horizontal primary navigation (desktop only; mobile uses the drawer). --}}
<nav class="menu menu-rounded menu-column menu-lg-row menu-root-here-bg-desktop menu-active-bg menu-state-primary menu-title-gray-800 menu-arrow-gray-500 align-items-stretch fw-semibold fs-6 py-1"
    data-kt-menu="true" aria-label="Navigasi utama">

    <div class="menu-item me-lg-2 {{ request()->routeIs('dashboard') ? 'here show menu-here-bg' : '' }}">
        <a class="menu-link py-3" href="{{ route('dashboard') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-element-11 fs-3">
                    <span class="path1"></span><span class="path2"></span>
                    <span class="path3"></span><span class="path4"></span>
                </i>
            </span>
            <span class="menu-title">Dashboard</span>
        </a>
    </div>

    {{-- Modul inti SETSUNA: acara, tamu, media, paket, pesanan. --}}
    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
        class="menu-item menu-lg-down-accordion me-lg-2 {{ request()->is('admin/events*') || request()->is('admin/clients*') || request()->is('admin/plans*') || request()->is('admin/subscriptions*') ? 'here show menu-here-bg' : '' }}">
        <span class="menu-link py-3">
            <span class="menu-icon">
                <i class="ki-duotone ki-picture fs-3"><span class="path1"></span><span class="path2"></span></i>
            </span>
            <span class="menu-title">Acara</span>
            <span class="menu-arrow d-lg-none"></span>
        </span>
        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown py-4 w-225px">
            <div class="menu-item">
                <a class="menu-link {{ request()->is('admin/events*') ? 'active' : '' }}" href="{{ route('events.index') }}">
                    <span class="menu-icon"><i class="ki-duotone ki-calendar fs-4"><span class="path1"></span><span class="path2"></span></i></span>
                    <span class="menu-title">Daftar Acara</span>
                </a>
            </div>
            <div class="menu-item">
                <a class="menu-link {{ request()->is('admin/clients*') ? 'active' : '' }}" href="{{ route('clients.index') }}">
                    <span class="menu-icon"><i class="ki-duotone ki-profile-user fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i></span>
                    <span class="menu-title">Klien</span>
                </a>
            </div>
            <div class="menu-item">
                <a class="menu-link {{ request()->is('admin/subscriptions*') ? 'active' : '' }}" href="{{ route('subscriptions.index') }}">
                    <span class="menu-icon"><i class="ki-duotone ki-bill fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span></i></span>
                    <span class="menu-title">Pesanan</span>
                </a>
            </div>
            <div class="menu-item">
                <a class="menu-link {{ request()->is('admin/plans*') ? 'active' : '' }}" href="{{ route('plans.index') }}">
                    <span class="menu-icon"><i class="ki-duotone ki-package fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></span>
                    <span class="menu-title">Paket Langganan</span>
                </a>
            </div>
        </div>
    </div>

    @role('Superadmin|superadmin')
        <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
            class="menu-item menu-lg-down-accordion me-lg-2 {{ request()->is('admin/users*') || request()->is('admin/roles*') ? 'here show menu-here-bg' : '' }}">
            <span class="menu-link py-3">
                <span class="menu-icon">
                    <i class="ki-duotone ki-people fs-3">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        <span class="path4"></span><span class="path5"></span>
                    </i>
                </span>
                <span class="menu-title">User Management</span>
                <span class="menu-arrow d-lg-none"></span>
            </span>
            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown py-4 w-225px">
                <div class="menu-item">
                    <a class="menu-link {{ request()->is('admin/users*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                        <span class="menu-icon"><i class="ki-duotone ki-user fs-4"><span class="path1"></span><span class="path2"></span></i></span>
                        <span class="menu-title">Users</span>
                    </a>
                </div>
                <div class="menu-item">
                    <a class="menu-link {{ request()->is('admin/roles*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                        <span class="menu-icon"><i class="ki-duotone ki-shield-tick fs-4"><span class="path1"></span><span class="path2"></span></i></span>
                        <span class="menu-title">Roles &amp; Permissions</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="menu-item me-lg-2 {{ request()->routeIs('settings.*') ? 'here show menu-here-bg' : '' }}">
            <a class="menu-link py-3" href="{{ route('settings.index') }}">
                <span class="menu-icon">
                    <i class="ki-duotone ki-setting-2 fs-3"><span class="path1"></span><span class="path2"></span></i>
                </span>
                <span class="menu-title">Settings</span>
            </a>
        </div>
    @endrole

    {{-- Every role can open this; the controller scopes what they see. --}}
    <div class="menu-item me-lg-2 {{ request()->is('admin/log-activity*') ? 'here show menu-here-bg' : '' }}">
        <a class="menu-link py-3" href="{{ route('log-activity.index') }}">
            <span class="menu-icon">
                <i class="ki-duotone ki-notepad fs-3">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    <span class="path4"></span><span class="path5"></span>
                </i>
            </span>
            <span class="menu-title">Log Aktivitas</span>
        </a>
    </div>

</nav>
