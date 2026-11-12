<?php
// "Continue with Google": send the customer to Google's sign-in page.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/GoogleAuthController.php';

if (is_logged_in()) redirect('index.php');
if (!GoogleAuthController::isEnabled()) {
    flash('error', 'Sign in with Google is not set up yet. Please use your email and password.');
    redirect('views/login.php');
}
redirect((new GoogleAuthController())->start());
