<?php
/** @var string $app_site_title */
/** @var string $app_logo_url */
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-theme-toggle aria-label="Switch to dark mode">
                            <i class="bi bi-moon-stars-fill"></i>
                        </button>
                    </div>
                    <?php if (!empty($app_logo_url)): ?>
                        <div class="text-center mb-4">
                            <img src="<?= escape($app_logo_url) ?>" alt="<?= escape($app_site_title ?? 'Servicebook') ?>" style="max-height: 84px; max-width: 100%;">
                        </div>
                    <?php else: ?>
                        <h1 class="h4 mb-4 text-center"><?= escape(($app_site_title ?? 'Servicebook') . ' Login') ?></h1>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert"><?= escape($error) ?></div>
                    <?php endif; ?>
                    <form method="post" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input id="username" name="username" type="text" class="form-control" value="<?= escape($username) ?>" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" name="password" type="password" class="form-control" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Sign In</button>
                        </div>
                    </form>
                </div>
            </div>
            <p class="text-muted text-center mt-3"><small>Servicebook - <a target="_blank" href="https://github.com/Ketris/Servicebook">Github</a></small></p>
        </div>
    </div>
</div>
