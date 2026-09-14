<form class="card" method="post" action="/real-estate-agent/login" style="width:min(420px,100%);">
    <h2>Real Estate Agent Login</h2>
    <?php if (!empty($error)): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <label>Phone</label>
    <input name="phone" required autofocus>
    <label>Password</label>
    <input name="password" type="password" required>
    <button style="margin-top:16px">Login</button>
</form>
