<header style="padding:28px 32px 0;">
    <div class="admin-surface" style="border-radius:34px; padding:22px 28px; display:flex; align-items:center; justify-content:space-between; gap:20px;">
        <div style="display:flex; align-items:center; gap:18px;">
            <div style="width:72px; height:72px; border-radius:26px; border:1px solid rgba(191,219,254,.16); background:rgba(255,255,255,.06); display:grid; place-items:center;">
                <img src="{{ asset('favicon-ctg.png') }}" alt="CTG" style="width:40px; height:40px; object-fit:contain;">
            </div>
            <div>
                <div style="font-size:12px; letter-spacing:.34em; text-transform:uppercase; color:rgba(220, 231, 243,.72);">Admin Workspace</div>
                <div style="font-size:46px; line-height:1; font-weight:800; color:#fff;">{{ request()->routeIs('admin.students.*') ? 'Students' : 'Users' }}</div>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:14px; padding:16px 20px; border-radius:28px; border:1px solid rgba(191,219,254,.14); background:rgba(255,255,255,.06);">
            <img src="{{ Auth::user()->profile_photo ? asset('storage/' . Auth::user()->profile_photo) : asset('storage/profile_photos/default-3d.svg') }}"
                alt="Admin Photo" style="width:58px; height:58px; border-radius:999px; object-fit:cover; background:#fff;">
            <div>
                <div style="font-size:12px; letter-spacing:.28em; text-transform:uppercase; color:rgba(220, 231, 243,.72);">Administrator</div>
                <div style="font-size:18px; font-weight:700; color:#fff;">{{ Auth::user()->name }}</div>
            </div>
        </div>
    </div>
</header>
