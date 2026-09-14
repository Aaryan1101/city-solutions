<div style="min-height:100vh;display:grid;place-items:center;padding:24px;">
    <form class="card" method="post" action="/service-provider/login" style="width:min(420px,100%);">
        <h1 style="margin-top:0;">Service Provider Login</h1>
        <p style="color:var(--muted);">Approved service providers can manage assigned service bookings here.</p>
        <?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <label>Phone</label>
        <input name="phone" required>
        <label>Password</label>
        <input name="password" type="password" required>
        <div style="height:14px;"></div>
        <button style="width:100%;">Login</button>
        <div style="height:10px;"></div>
        <a class="btn secondary" style="width:100%;text-align:center;" href="/admin/login">Admin Login</a>
    </form>
</div>
