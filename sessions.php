<?php
require_once 'auth.php';
require_once 'config/database.php';

requireLogin();

$userId = $_SESSION['user_id'] ?? null;
$fullName = $_SESSION['full_name'] ?? 'User';
$sessionStarted = isset($_SESSION['user_id']);
$sessionId = session_id();

$maskedSessionId = $sessionId
    ? substr($sessionId, 0, 4) . str_repeat('*', max(0, strlen($sessionId) - 4))
    : 'Unavailable';

$sessionStatus = $sessionStarted ? 'Active' : 'Inactive';
$loginTime = $_SESSION['login_time'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions | ETrack</title>

    <link rel="stylesheet" href="assets/style.css">

    <style>
        /* Remove space above the navbar */
        html,
        body {
            margin: 0;
            padding: 0;
        }

        .page {
            margin-top: 0;
            padding-top: 0;
        }

        /* Navbar - keep consistent with Home and Transactions */
        .main-navbar {
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 14px 24px;
            margin-left: calc(-1 * (100vw - 100%) / 2);
            margin-top: 0;
            margin-bottom: 0px;
            background: #ffffff;
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 0 12px 12px 0;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
        }

        .navbar-brand {
            color: var(--primary-dark, #4338ca);
            font-size: 23px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .navbar-links {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 8px;
        }

        .navbar-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            border-radius: 8px;
            color: var(--text, #1f2937);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .navbar-links a:hover {
            background: var(--primary-light, #eef2ff);
            color: var(--primary-dark, #4338ca);
        }

        .navbar-links a.active {
            background: var(--primary-dark, #4338ca);
            color: #ffffff;
        }

        .navbar-links a.logout-link {
            background: #fee2e2;
            color: #b91c1c;
        }

        .navbar-links a.logout-link:hover {
            background: #fecaca;
        }

        @media (max-width: 760px) {
            .main-navbar {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
                padding: 14px;
            }

            .navbar-links {
                width: 100%;
                justify-content: flex-start;
                gap: 6px;
            }

            .navbar-links a {
                padding: 9px 11px;
                font-size: 13px;
            }
        }

        @media (max-width: 420px) {
            .navbar-links {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .navbar-links a {
                width: 100%;
            }
        }

        /* Sessions page content */
        .sessions-page {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px 48px;
            box-sizing: border-box;
            color: var(--text, #1f2937);
        }

        .sessions-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .sessions-heading h1 {
            margin: 0 0 8px;
            font-size: clamp(26px, 4vw, 34px);
            font-weight: 800;
        }

        .sessions-heading p {
            margin: 0;
            color: var(--muted, #6b7280);
            line-height: 1.6;
        }

        .session-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 13px;
            font-weight: 700;
        }

        .session-status-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
        }

        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .session-card {
            background: #ffffff;
            border: 1px solid var(--border, #e5e7eb);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
            min-width: 0;
        }

        .session-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .session-icon {
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-radius: 12px;
            background: var(--primary-light, #eef2ff);
            font-size: 22px;
        }

        .session-card h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 750;
        }

        .session-card-subtitle {
            margin: 5px 0 0;
            color: var(--muted, #6b7280);
            font-size: 13px;
            line-height: 1.5;
        }

        .session-details {
            display: grid;
            gap: 0;
        }

        .session-detail {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .session-detail:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .session-detail-label {
            color: var(--muted, #6b7280);
            font-size: 14px;
        }

        .session-detail-value {
            max-width: 60%;
            text-align: right;
            overflow-wrap: anywhere;
            font-size: 14px;
            font-weight: 650;
        }

        .session-active-text {
            color: #15803d;
        }

        .session-security {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 22px;
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 16px;
            margin-bottom: 24px;
        }

        .session-security-icon {
            font-size: 24px;
            flex-shrink: 0;
        }

        .session-security h2 {
            margin: 0 0 8px;
            color: #1e40af;
            font-size: 16px;
        }

        .session-security p {
            margin: 0;
            color: #1e3a8a;
            font-size: 14px;
            line-height: 1.7;
        }

        .session-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .session-logout-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 12px 20px;
            border: none;
            border-radius: 9px;
            background: #dc2626;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .session-logout-button:hover {
            background: #b91c1c;
        }

        .session-note {
            margin-top: 16px;
            color: var(--muted, #6b7280);
            font-size: 12px;
            line-height: 1.6;
            text-align: right;
        }

        @media (max-width: 760px) {
            .sessions-page {
                padding: 0 14px 32px;
            }

            .sessions-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .session-card {
                padding: 20px;
            }
        }

        @media (max-width: 420px) {
            .session-detail {
                flex-direction: column;
                gap: 7px;
            }

            .session-detail-value {
                max-width: 100%;
                text-align: left;
            }

            .session-actions {
                justify-content: stretch;
            }

            .session-logout-button {
                width: 100%;
            }

            .session-note {
                text-align: left;
            }
        }
    </style>
</head>
<body>

<div class="page">
    <nav class="main-navbar" aria-label="Main navigation">
        <a href="index.php" class="navbar-brand">💰 ETrack</a>

        <div class="navbar-links">
            <a href="index.php">Home</a>
            <a href="transactions.php">Transactions</a>
            <a href="sessions.php" class="active" aria-current="page">Sessions</a>
            <a
                href="logout.php"
                class="logout-link"
                onclick="return confirm('Are you sure you want to log out?');"
            >Logout</a>
        </div>
    </nav>
</div>

<main class="sessions-page">
    <header class="sessions-heading">
        <div>
            <h1>Sessions</h1>
            <p>View your current login session and account security information.</p>
        </div>

        <div class="session-status">
            <span class="session-status-dot"></span>
            <?php echo htmlspecialchars($sessionStatus, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </header>

    <section class="sessions-grid">
        <article class="session-card">
            <div class="session-card-header">
                <div class="session-icon">💻</div>
                <div>
                    <h2>Current Session</h2>
                    <p class="session-card-subtitle">
                        Information about your current login.
                    </p>
                </div>
            </div>

            <div class="session-details">
                <div class="session-detail">
                    <span class="session-detail-label">Session Status</span>
                    <span class="session-detail-value session-active-text">
                        <?php echo htmlspecialchars($sessionStatus, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>

                <div class="session-detail">
                    <span class="session-detail-label">Session ID</span>
                    <span class="session-detail-value">
                        <?php echo htmlspecialchars($maskedSessionId, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>

                <div class="session-detail">
                    <span class="session-detail-label">Session Storage</span>
                    <span class="session-detail-value">PHP Session</span>
                </div>
            </div>
        </article>

        <article class="session-card">
            <div class="session-card-header">
                <div class="session-icon">👤</div>
                <div>
                    <h2>Account Information</h2>
                    <p class="session-card-subtitle">
                        The account currently signed in.
                    </p>
                </div>
            </div>

            <div class="session-details">
                <div class="session-detail">
                    <span class="session-detail-label">Full Name</span>
                    <span class="session-detail-value">
                        <?php echo htmlspecialchars((string) $fullName, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>

                <div class="session-detail">
                    <span class="session-detail-label">User ID</span>
                    <span class="session-detail-value">
                        <?php echo htmlspecialchars((string) $userId, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>

                <div class="session-detail">
                    <span class="session-detail-label">Login Time</span>
                    <span class="session-detail-value">
                        <?php
                        echo $loginTime
                            ? htmlspecialchars((string) $loginTime, ENT_QUOTES, 'UTF-8')
                            : 'Not recorded';
                        ?>
                    </span>
                </div>
            </div>
        </article>
    </section>

    <section class="session-security">
        <div class="session-security-icon">🛡️</div>
        <div>
            <h2>Keep Your Account Secure</h2>
            <p>
                Always log out when using a shared computer. Your current
                session information is shown here, but this page does not
                track previous logins or other devices. Never share your
                account credentials or session information with others.
            </p>
        </div>
    </section>

    <section class="session-card">
        <div class="session-card-header">
            <div class="session-icon">🚪</div>
            <div>
                <h2>End Current Session</h2>
                <p class="session-card-subtitle">
                    Log out of your ETrack account on this browser.
                </p>
            </div>
        </div>

        <div class="session-actions">
            <a
                href="logout.php"
                class="session-logout-button"
                onclick="return confirm('Are you sure you want to end your current session and log out?');"
            >
                Log Out
            </a>
        </div>

        <p class="session-note">
            Logging out will end your current login session.
        </p>
    </section>
</main>

</body>
</html>