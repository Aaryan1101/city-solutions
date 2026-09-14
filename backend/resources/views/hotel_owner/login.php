<div style="max-width:420px;margin:80px auto;" class="card">
    <h1>Hotel Owner Login</h1>
    <?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" action="/hotel-owner/login">
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
