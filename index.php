<?php
require_once __DIR__ . '/src/Installation.php';

Installation::redirectToInstallerIfNeeded();
header('Location: public/');
exit;
