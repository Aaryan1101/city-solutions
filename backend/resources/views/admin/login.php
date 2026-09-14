<div style="min-height:100vh;display:grid;place-items:center;padding:24px;">
    <form class="card" method="post" action="/admin/login" style="width:min(420px,100%);">
        <h1 style="margin-top:0;">City Mart Admin</h1>
        <p style="color:var(--muted);">Sign in to manage catalog, banners, and orders.</p>
        <?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <label>Email</label>
        <input name="email" type="email" required autocomplete="username">
        <label>Password</label>
        <input name="password" type="password" required autocomplete="current-password">
        <div style="height:14px;"></div>
        <button style="width:100%;">Login</button>
    </form>
</div>
