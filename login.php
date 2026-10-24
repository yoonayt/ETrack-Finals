<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

redirectIfLoggedIn();

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT user_id, first_name, last_name, email, password
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );

            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = 'Invalid email or password.';
            } else {
                $storedPassword = (string) $user['password'];

                // Detect an incomplete bcrypt hash rather than treating
                // it as a valid password.
                $looksLikeBcrypt = preg_match(
                    '/^\$2[aby]\$\d{2}\$/',
                    $storedPassword
                ) === 1;

                if (
                    $looksLikeBcrypt &&
                    strlen($storedPassword) !== 60
                ) {
                    $error = 'This account has an incomplete saved password. Please create a new account or reset this account password.';
                } elseif (password_verify($password, $storedPassword)) {
                    // Upgrade the hash if PHP recommends a newer format.
                    if (password_needs_rehash(
                        $storedPassword,
                        PASSWORD_DEFAULT
                    )) {
                        $newHash = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $update = $pdo->prepare(
                            'UPDATE users
                             SET password = :password
                             WHERE user_id = :user_id'
                        );

                        $update->execute([
                            'password' => $newHash,
                            'user_id' => $user['user_id']
                        ]);
                    }

                    session_regenerate_id(true);

                    $_SESSION['user_id'] = (int) $user['user_id'];
                    $_SESSION['full_name'] = trim(
                        ($user['first_name'] ?? '') . ' ' .
                        ($user['last_name'] ?? '')
                    );
                    $_SESSION['email'] = $user['email'];

                    header('Location: index.php');
                    exit();
                } else {
                    // Do not accept an incomplete hash or plain-text
                    // password as a substitute for proper verification.
                    $error = 'Invalid email or password.';
                }
            }
        } catch (PDOException $e) {
            error_log('ETrack login error: ' . $e->getMessage());
            $error = 'A database error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ETrack - Login</title>
    <link rel="stylesheet" href="assets/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
        }

        .auth-page {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            padding: 28px 32px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow-lg);
        }

        .auth-brand {
            text-align: center;
            margin-bottom: 20px;
        }

        .auth-brand h1 {
            margin: 0 0 6px;
            font-size: 30px;
            color: var(--primary-dark);
        }

        .auth-brand p {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .auth-card .field {
            margin-bottom: 12px;
        }

        .auth-card .field input {
            width: 100%;
            padding: 10px 12px;
            min-height: 42px;
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .auth-card .password-wrapper input {
            padding-right: 44px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
        }

        .toggle-password:hover {
            color: var(--primary-dark);
        }

        .toggle-password svg {
            display: block;
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .auth-submit {
            width: 100%;
            justify-content: center;
            padding: 11px 12px;
        }

        .auth-link {
            text-align: center;
            margin: 18px 0 0;
            font-size: 14px;
        }

        .auth-link a {
            color: var(--primary-dark);
            font-weight: 600;
        }

        .auth-error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 14px;
            font-size: 14px;
        }

        .auth-success {
            background: #dcfce7;
            color: #166534;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 14px;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .auth-page {
                padding: 12px;
            }

            .auth-card {
                padding: 24px 20px;
            }
        }

        @media (max-height: 600px) {
            .auth-page {
                align-items: flex-start;
                padding-top: 12px;
                padding-bottom: 12px;
            }

            .auth-card {
                padding-top: 18px;
                padding-bottom: 18px;
            }

            .auth-brand {
                margin-bottom: 12px;
            }

            .auth-card .field {
                margin-bottom: 9px;
            }

            .auth-link {
                margin-top: 12px;
            }
        }
    </style>
</head>
<body>
<div class="auth-page">
    <main class="auth-card">
        <div class="auth-brand">
            <h1>💰 ETrack</h1>
            <p>Log in to manage your expenses.</p>
        </div>

        <?php if (isset($_GET['registered'])): ?>
            <div class="auth-success">
                Account created successfully. You can now log in.
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="auth-error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="field">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    autocomplete="email"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="password">Password</label>

                <div class="password-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        id="togglePassword"
                        aria-label="Show password"
                        title="Show password"
                        aria-controls="password"
                    >
                        <svg id="eyeIcon" viewBox="0 0 24 24"
                             aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary auth-submit">
                Log In
            </button>
        </form>

        <p class="auth-link">
            Don't have an account?
            <a href="register.php">Register here</a>
        </p>
    </main>
</div>

<script>
const passwordInput = document.getElementById('password');
const togglePassword = document.getElementById('togglePassword');
const eyeIcon = document.getElementById('eyeIcon');

togglePassword.addEventListener('click', function () {
    const shouldShow = passwordInput.type === 'password';

    passwordInput.type = shouldShow ? 'text' : 'password';

    togglePassword.setAttribute(
        'aria-label',
        shouldShow ? 'Hide password' : 'Show password'
    );

    togglePassword.setAttribute(
        'title',
        shouldShow ? 'Hide password' : 'Show password'
    );

    eyeIcon.innerHTML = shouldShow
        ? `
            <path d="M3 3l18 18"/>
            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
            <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a16.5 16.5 0 0 1-4 4.8"/>
            <path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1 0 2-.2 2.8-.5"/>
        `
        : `
            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
            <circle cx="12" cy="12" r="3"/>
        `;
});
</script>
</body>
</html>