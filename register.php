```php
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

redirectIfLoggedIn();

$error = '';
$first_name = '';
$last_name = '';
$email = '';
$contactnumber = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contactnumber = trim($_POST['contactnumber'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (
        $first_name === '' ||
        $last_name === '' ||
        $email === '' ||
        $contactnumber === '' ||
        $password === '' ||
        $confirm_password === ''
    ) {
        $error = 'Please complete all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $check = $pdo->prepare(
                'SELECT user_id FROM users WHERE email = :email LIMIT 1'
            );

            $check->execute(['email' => $email]);

            if ($check->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                if ($hashed_password === false) {
                    throw new RuntimeException(
                        'Password hashing failed.'
                    );
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO users
                        (first_name, last_name, email, password,
                         contactnumber, dateregistered)
                     VALUES
                        (:first_name, :last_name, :email, :password,
                         :contactnumber, :dateregistered)'
                );

                $stmt->execute([
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'password' => $hashed_password,
                    'contactnumber' => $contactnumber,
                    'dateregistered' => date('Y-m-d')
                ]);

                $user_id = (int) $pdo->lastInsertId();

                // Log in the newly registered account.
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user_id;
                $_SESSION['full_name'] = trim(
                    $first_name . ' ' . $last_name
                );
                $_SESSION['email'] = $email;

                header('Location: index.php');
                exit();
            }
        } catch (PDOException $e) {
            error_log('ETrack registration error: ' . $e->getMessage());
            $error = 'Unable to create your account right now. Please try again.';
        } catch (RuntimeException $e) {
            error_log('ETrack password error: ' . $e->getMessage());
            $error = 'Unable to create your account right now. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ETrack - Register</title>
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
            padding: 12px;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            padding: 20px 28px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow-lg);
        }

        .auth-brand {
            text-align: center;
            margin-bottom: 14px;
        }

        .auth-brand h1 {
            margin: 0 0 5px;
            font-size: 28px;
            color: var(--primary-dark);
        }

        .auth-brand p {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .auth-card .field {
            margin-bottom: 9px;
        }

        .auth-card .field label {
            display: block;
            margin-bottom: 4px;
            font-size: 13px;
        }

        .auth-card .field input {
            width: 100%;
            min-height: 38px;
            padding: 9px 12px;
            font-size: 14px;
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
            padding: 10px 12px;
            margin-top: 3px;
        }

        .auth-link {
            text-align: center;
            margin: 13px 0 0;
            font-size: 14px;
        }

        .auth-link a {
            color: var(--primary-dark);
            font-weight: 600;
        }

        .auth-error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 9px 11px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 13px;
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 20px 18px;
            }
        }

        @media (max-height: 750px) {
            .auth-page {
                align-items: flex-start;
                padding-top: 10px;
                padding-bottom: 10px;
            }

            .auth-card {
                padding-top: 14px;
                padding-bottom: 14px;
            }

            .auth-brand {
                margin-bottom: 10px;
            }

            .auth-brand h1 {
                font-size: 25px;
            }

            .auth-card .field {
                margin-bottom: 7px;
            }

            .auth-card .field input {
                min-height: 35px;
                padding-top: 7px;
                padding-bottom: 7px;
            }

            .auth-submit {
                padding-top: 9px;
                padding-bottom: 9px;
            }

            .auth-link {
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
<div class="auth-page">
    <main class="auth-card">
        <div class="auth-brand">
            <h1>💰 ETrack</h1>
            <p>Create an account to manage your expenses.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="auth-error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="field">
                <label for="first_name">First Name</label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    value="<?= htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Enter your first name"
                    autocomplete="given-name"
                    required
                >
            </div>

            <div class="field">
                <label for="last_name">Last Name</label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    value="<?= htmlspecialchars($last_name, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Enter your last name"
                    autocomplete="family-name"
                    required
                >
            </div>

            <div class="field">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Enter your email"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="field">
                <label for="contactnumber">Contact Number</label>
                <input
                    type="tel"
                    id="contactnumber"
                    name="contactnumber"
                    value="<?= htmlspecialchars($contactnumber, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Enter your contact number"
                    autocomplete="tel"
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
                        placeholder="At least 8 characters"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        data-toggle-password="password"
                        aria-label="Show password"
                        title="Show password"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="field">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Re-enter your password"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        data-toggle-password="confirm_password"
                        aria-label="Show password"
                        title="Show password"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary auth-submit">
                Create Account
            </button>
        </form>

        <p class="auth-link">
            Already have an account?
            <a href="login.php">Log in here</a>
        </p>
    </main>
</div>

<script>
document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
    button.addEventListener('click', function () {
        const inputId = button.getAttribute('data-toggle-password');
        const passwordInput = document.getElementById(inputId);

        if (!passwordInput) {
            return;
        }

        const shouldShow = passwordInput.type === 'password';
        passwordInput.type = shouldShow ? 'text' : 'password';

        button.setAttribute(
            'aria-label',
            shouldShow ? 'Hide password' : 'Show password'
        );

        button.setAttribute(
            'title',
            shouldShow ? 'Hide password' : 'Show password'
        );
    });
});
</script>
</body>
</html>
```