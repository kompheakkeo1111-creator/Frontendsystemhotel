<?php
/**
 * Login View - User Authentication Form
 *
 * Standalone login page for user authentication. Displays a centered login card
 * with username/password fields and error message display. Does not use the
 * main layout (header/footer) as it is accessed before authentication.
 *
 * Expected variables from controller:
 *   $error - string - Authentication error message (optional, displayed on failed login)
 *
 * Security measures:
 *   - htmlspecialchars() on $error to prevent XSS from reflected error messages
 *   - POST method for form submission (prevents credentials in URL/query string)
 *   - HTML5 required attribute on username and password fields
 *   - type="password" masks password input from shoulder surfing
 *   - No CSRF token visible (may be added server-side or via middleware)
 *   - Session-based authentication handled by controller after form submission
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Management System - Login</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Background Image */
        body {
            background: url('assets/img/hotel-login-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        /* Dark Overlay */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
        }

        /* Login Card */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 1;
        }

        .login-card h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        /* Hotel logo above the login heading */
        .login-logo {
            display: block;
            width: 190px;
            max-width: 70%;
            height: auto;
            margin: -8px auto 14px;
        }

        .form-control {
            border-radius: 25px;
            padding: 12px 20px;
        }

        .btn-login {
            border-radius: 25px;
            padding: 12px;
            background: linear-gradient(135deg, #c9a227 0%, #d4af37 100%);
            border: none;
            color: white;
            width: 100%;
            font-weight: bold;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            background: linear-gradient(135deg, #b8912a 0%, #c9a227 100%);
        }

        /* Google Sign-In divider */
        .login-divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #888;
            font-size: 14px;
            margin: 22px 0 15px;
        }
        .login-divider::before,
        .login-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        .login-divider span {
            padding: 0 10px;
        }

        /* Center the Google button inside the card */
        #gSignIn {
            display: flex;
            justify-content: center;
        }

        /* Google-styled fallback button (used while GOOGLE_CLIENT_ID is empty) */
        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            max-width: 320px;
            height: 42px;
            padding: 0 16px;
            background: #ffffff;
            border: 1px solid #dadce0;
            border-radius: 6px;
            color: #3c4043;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: box-shadow .2s ease, background-color .2s ease;
        }

        .google-btn:hover {
            background: #f8faff;
            box-shadow: 0 1px 2px rgba(60, 64, 67, .3);
        }

        .google-btn:active {
            background: #f1f3f4;
        }

        /* Notice shown when the Google button is used without a Client ID */
        #googleNotice {
            display: none;
            margin-top: 12px;
            font-size: 13px;
        }

        /* Google sign-in error message */
        #googleError {
            display: none;
            margin-top: 12px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <!-- Hotel logo (emblem + wordmark) -->
        <img class="login-logo" src="assets/img/logo-full.png" alt="Demo Hotel">

        <h2>Hotel Management</h2>

        <!-- Error Message: Displayed when authentication fails -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <!-- Security: htmlspecialchars() prevents XSS from reflected error message -->
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Login Form: POST method prevents credentials from appearing in URL -->
        <form method="POST">
            <!-- Username field: text input with required validation -->
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>

            <!-- Password field: masked input with required validation -->
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <!-- Submit button: triggers POST to login controller -->
            <button type="submit" class="btn btn-login">Login</button>
        </form>

        <?php
        // Google Sign-In: the button is always rendered. It only becomes a
        // working sign-in once GOOGLE_CLIENT_ID is set in config_local.php.
        $googleClientId = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';
        $googleEnabled = ($googleClientId !== '' && strpos($googleClientId, 'YOUR_') !== 0);
        ?>

        <div class="login-divider"><span>or</span></div>

        <?php if ($googleEnabled): ?>
            <!-- Google Identity Services configuration -->
            <div id="g_id_onload"
                 data-client_id="<?php echo htmlspecialchars($googleClientId, ENT_QUOTES); ?>"
                 data-callback="handleGoogleSignIn"
                 data-auto_prompt="false">
            </div>

            <!-- Google Sign-In button (rendered by accounts.google.com/gsi/client) -->
            <div id="gSignIn" class="g_id_signin"
                 data-type="standard"
                 data-size="large"
                 data-theme="outline"
                 data-text="sign_in_with"
                 data-shape="rectangular"
                 data-logo_alignment="left">
            </div>
        <?php else: ?>
            <!-- Fallback: keeps "Sign in with Google" visible before a Client ID exists -->
            <div id="gSignIn">
                <button type="button" class="google-btn" id="googleFallbackBtn">
                    <svg viewBox="0 0 48 48" width="18" height="18" aria-hidden="true" focusable="false">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                    <span>Sign in with Google</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Error message returned by the server for Google sign-in -->
        <div id="googleError" class="alert alert-danger"></div>

        <!-- Shown when the Google button is clicked before a Client ID is configured -->
        <div id="googleNotice" class="alert alert-warning"></div>
    </div>

    <?php if (!$googleEnabled): ?>
    <script>
        // No Client ID yet: explain what is needed instead of failing silently.
        document.getElementById('googleFallbackBtn').addEventListener('click', function () {
            var notice = document.getElementById('googleNotice');
            notice.textContent = 'Google sign-in is not configured yet. Set GOOGLE_CLIENT_ID in backend/config_local.php.';
            notice.style.display = 'block';
        });
    </script>
    <?php endif; ?>

    <?php if ($googleEnabled): ?>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        /**
         * Called by Google Identity Services after the user picks an account.
         * Sends the ID token to the backend, which verifies it server-side
         * and creates the session on success.
         */
        function handleGoogleSignIn(response) {
            var errorBox = document.getElementById('googleError');
            errorBox.style.display = 'none';

            fetch('index.php?r=login/google', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body: 'credential=' + encodeURIComponent(response.credential)
            })
            .then(function (res) {
                return res.json().then(function (data) {
                    if (data.success && data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        errorBox.textContent = data.error || 'Google sign-in failed.';
                        errorBox.style.display = 'block';
                        // Reset so the button can be used again
                        if (window.google && google.accounts.id) {
                            google.accounts.id.prompt();
                        }
                    }
                });
            })
            .catch(function () {
                errorBox.textContent = 'Network error. Please try again.';
                errorBox.style.display = 'block';
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
