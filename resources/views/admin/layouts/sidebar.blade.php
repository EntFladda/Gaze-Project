<aside id="adminSidebar" class="admin-sidebar admin-scrollbar scrolled">
    <div class="compact-center compact-stack" style="padding:20px 16px 16px; border-bottom:1px solid rgba(191,219,254,.1); display:flex; align-items:center; justify-content:space-between; gap:12px;">
        <div class="compact-center" style="display:flex; align-items:center; gap:16px;">
            <img src="{{ asset('favicon-ctg.png') }}" alt="CTG" class="sidebar-brand-admin" style="width:44px; height:44px; object-fit:contain;">
            <div class="hide-when-collapsed">
                <div style="font-size:18px; letter-spacing:.28em; text-transform:uppercase; color:rgba(220, 231, 243,.72);">Admin</div>
                <div style="font-size:34px; font-weight:800; line-height:1; color:#fff;">CTG</div>
            </div>
        </div>
        <button id="adminSidebarToggle" class="sidebar-toggle-admin" onclick="toggleAdminSidebar()" style="border:0; background:transparent; color:#fff; font-size:32px; cursor:pointer; line-height:1;">&#9776;</button>
    </div>

    <nav class="compact-stack" style="padding:16px 14px 20px; display:flex; flex-direction:column; gap:12px;">
        @php
            $adminLinks = [
                ['route' => 'admin.users.index', 'label' => 'Pengguna', 'badge' => 'PG'],
                ['route' => 'admin.students.index', 'label' => 'Mahasiswa', 'badge' => 'MH'],
            ];
        @endphp

        @foreach ($adminLinks as $link)
            @php
                $isActive = request()->routeIs(str_replace('.index', '.*', $link['route'])) || request()->routeIs($link['route']);
            @endphp
            <a href="{{ route($link['route']) }}" class="nav-link-admin"
                style="display:flex; align-items:center; gap:16px; padding:18px 18px; border-radius:24px; text-decoration:none; color:#fff; background:{{ $isActive ? 'linear-gradient(90deg,#1D5FD6,#2B7FD8)' : 'rgba(255,255,255,.06)' }}; border:1px solid {{ $isActive ? 'rgba(37,99,235,.28)' : 'rgba(191,219,254,.08)' }}; box-shadow:{{ $isActive ? '0 18px 36px rgba(37,99,235,.22)' : 'none' }};">
                <span class="nav-badge-admin" style="width:50px; height:50px; border-radius:18px; display:grid; place-items:center; font-weight:800; letter-spacing:.12em; background:rgba(255,255,255,.12);">{{ $link['badge'] }}</span>
                <span class="hide-when-collapsed" style="font-size:18px; font-weight:700;">{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="compact-stack" style="margin-top:auto; padding:20px;">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="compact-center logout-admin" style="width:100%; padding:16px 18px; border:0; border-radius:22px; background:linear-gradient(90deg,#1D5FD6,#2BA7D8); color:#fff; font-size:18px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px;">
                <span>⏻</span>
                <span class="hide-when-collapsed">Keluar</span>
            </button>
        </form>
    </div>
</aside>
