<?php
session_start();
require_once 'db.php';

// Guest Handling
if (!isset($_SESSION['user_id'])) {
    if (!isset($_COOKIE['guest_token'])) {
        $guestId = 'Guest_' . substr(uniqid(), -4);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, is_guest) VALUES (?, 'guest_pass', TRUE)");
        $stmt->execute([$guestId]);
        $newId = $pdo->lastInsertId();
        
        $_SESSION['user_id'] = $newId;
        $_SESSION['username'] = $guestId;
        $_SESSION['is_guest'] = true;
        setcookie('guest_token', $guestId, time() + (86400 * 30), "/"); // 30 days
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$_COOKIE['guest_token']]);
        $guest = $stmt->fetch();
        if ($guest) {
            $_SESSION['user_id'] = $guest['id'];
            $_SESSION['username'] = $guest['username'];
            $_SESSION['is_guest'] = true;
            $_SESSION['is_admin'] = false; // Ensure they are not admin
        }
    }
}

$isLoggedIn = isset($_SESSION['user_id']);
$isGuest = isset($_SESSION['is_guest']) && $_SESSION['is_guest'];
$username = $_SESSION['username'] ?? '';
$isAdmin = $isLoggedIn && isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

echo "<script>console.log('PHP Session User: " . ($username ? $username : 'None') . " (Guest: " . ($isGuest ? 'Yes' : 'No') . ")');</script>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TypeMaster | Premium Typing Tutor</title>
    <meta name="description" content="Master your typing speed with TypeMaster. Real-time analysis, virtual keyboard guidance, and advanced performance tracking.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="dark-theme">
    <div id="app">
        <nav class="glass-nav">
            <div class="logo">Type<span>Master</span></div>
            <div class="nav-links">
                <?php if ($isAdmin): ?>
                    <button id="admin-btn" class="nav-btn">Admin Panel</button>
                <?php endif; ?>
                <button id="leaderboard-btn" class="nav-btn">Leaderboard</button>
                <div id="auth-section">
                    <?php if ($isLoggedIn && !$isGuest): ?>
                        <span class="user-greet">Hello, <b><?php echo htmlspecialchars($username); ?></b></span>
                        <button id="logout-btn" class="nav-btn">Logout</button>
                    <?php else: ?>
                        <?php if ($isGuest): ?>
                            <span class="user-greet">Hello, <b><?php echo htmlspecialchars($username); ?></b> (Guest)</span>
                        <?php endif; ?>
                        <button id="login-trigger" class="nav-btn primary">Login / Register</button>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <main class="container">
            <header class="hero-section">
                <h1>Elevate Your <span>Typing</span></h1>
                <p>Advanced tutor with real-time finger analysis and adaptive learning.</p>
            </header>

            <section class="difficulty-selector">
                <button class="diff-btn active" data-level="very-easy">Very Easy</button>
                <button class="diff-btn" data-level="easy">Easy</button>
                <button class="diff-btn" data-level="medium">Medium</button>
                <button class="diff-btn" data-level="hard">Hard</button>
                <button class="diff-btn" data-level="very-hard">Very Hard</button>
            </section>

            <section class="time-selector">
                <span class="label">Duration:</span>
                <button class="time-btn active" data-time="60">1m</button>
                <button class="time-btn" data-time="120">2m</button>
                <button class="time-btn" data-time="180">3m</button>
                <button class="time-btn" data-time="300">5m</button>
            </section>

            <div class="typing-container glass-card">
                <div class="stats-bar">
                    <div class="stat-item">
                        <span class="label">WPM</span>
                        <span id="wpm-val" class="value">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Accuracy</span>
                        <span id="acc-val" class="value">0%</span>
                    </div>
                    <div class="stat-item">
                        <span class="label">Time</span>
                        <span id="timer-val" class="value">60s</span>
                    </div>
                </div>

                <div id="words-display" class="words-wrapper">
                    <!-- Words will be injected here -->
                </div>

                <div class="input-area">
                    <input type="text" id="typing-input" autocomplete="off" autofocus placeholder="Type the words here...">
                    <button id="restart-btn" title="Restart Test">
                        <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M17.65 6.35A7.958 7.958 0 0 0 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
                    </button>
                </div>
            </div>

            <!-- Virtual Keyboard -->
            <section id="keyboard-wrapper" class="keyboard-section">
                <div class="keyboard-container">
                    <div class="keyboard"></div>
                    <div class="hands-overlay">
                        <!-- Left Hand -->
                        <svg class="hand-svg realistic" id="left-hand-svg" viewBox="0 0 200 250">
                            <g class="finger-group" data-finger="l-pinky">
                                <path class="finger-path" d="M40,120 Q35,70 45,70 T55,120" />
                                <rect class="nail" x="43" y="75" width="4" height="6" rx="2" />
                            </g>
                            <g class="finger-group" data-finger="l-ring">
                                <path class="finger-path" d="M65,110 Q60,50 70,50 T80,110" />
                                <rect class="nail" x="68" y="55" width="4" height="6" rx="2" />
                            </g>
                            <g class="finger-group" data-finger="l-middle">
                                <path class="finger-path" d="M90,105 Q85,40 95,40 T105,105" />
                                <rect class="nail" x="93" y="45" width="4" height="6" rx="2" />
                            </g>
                            <g class="finger-group" data-finger="l-index">
                                <path class="finger-path" d="M115,110 Q110,55 120,55 T130,110" />
                                <rect class="nail" x="118" y="60" width="4" height="6" rx="2" />
                            </g>
                            <g class="finger-group" data-finger="l-thumb">
                                <path class="finger-path" d="M140,160 Q160,140 180,160" />
                            </g>
                            <path class="palm" d="M40,120c0,47.8,19,100.9,77.9,100.9C159.3,220.9,180,207.8,180,160" />
                        </svg>

                        <!-- Right Hand -->
                        <svg class="hand-svg realistic" id="right-hand-svg" viewBox="0 0 200 250">
                            <g class="finger-group" data-finger="r-thumb">
                                <path class="finger-path" d="M60,160 Q40,140 20,160" />
                            </g>
                            <g class="finger-group" data-finger="r-index">
                                <path class="finger-path" d="M85,110 Q90,55 80,55 T70,110" />
                                <rect class="nail" x="78" y="60" width="4" height="6" rx="2" />
                            </g>
                            <g class="finger-group" data-finger="r-middle">
                                <path class="finger-path" d="M110,105 Q115,40 105,40 T95,105" />
                                <rect class="nail" x="103" y="45" width="4" height="6" rx="2" />
                            </g>
                            <g class="finger-group" data-finger="r-ring">
                                <path class="finger-path" d="M135,110 Q140,50 130,50 T120,110" />
                                <rect class="nail" x="128" y="55" width="4" height="6" rx="2" />
                            </g>
                            <g class="finger-group" data-finger="r-pinky">
                                <path class="finger-path" d="M160,120 Q165,70 155,70 T145,120" />
                                <rect class="nail" x="153" y="75" width="4" height="6" rx="2" />
                            </g>
                            <path class="palm" d="M160,120c0,47.8-19,100.9-77.9,100.9C40.7,220.9,20,207.8,20,160" />
                        </svg>
                    </div>
                </div>
            </section>

            <!-- Analysis Section -->
            <section id="analysis-panel" class="analysis-grid hidden">
                <div class="glass-card analysis-card">
                    <h3>Performance Analysis</h3>
                    <div id="key-heatmap" class="heatmap-container"></div>
                    <div id="advice-box" class="advice-content">
                        <p>Start typing to see your personalized advice!</p>
                    </div>
                </div>
            </section>
        </main>

        <!-- Modals -->
        <div id="auth-modal" class="modal hidden">
            <div class="modal-content glass-card">
                <h2 id="modal-title">Welcome Back</h2>
                <form id="auth-form">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" id="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="password" required>
                    </div>
                    <button type="submit" class="submit-btn">Continue</button>
                </form>
                <p class="toggle-auth">Don't have an account? <a href="#" id="auth-toggle">Sign Up</a></p>
                <button class="close-modal">&times;</button>
            </div>
        </div>

        <div id="leaderboard-modal" class="modal hidden">
            <div class="modal-content glass-card wide">
                <h2>Top Typists</h2>
                <div class="leaderboard-list"></div>
                <button class="close-modal">&times;</button>
            </div>
        </div>

        <div id="admin-modal" class="modal hidden">
            <div class="modal-content glass-card wide">
                <h2>Admin Dashboard - All Learners</h2>
                <div class="admin-table-container">
                    <table id="admin-table">
                        <thead>
                            <tr>
                                <th>Learner</th>
                                <th>Sessions</th>
                                <th>Avg WPM</th>
                                <th>Best WPM</th>
                                <th>Avg Acc</th>
                            </tr>
                        </thead>
                        <tbody id="admin-stats-body"></tbody>
                    </table>
                </div>
                <button class="close-modal">&times;</button>
            </div>
        </div>

        <div id="user-detail-modal" class="modal hidden">
            <div class="modal-content glass-card wide">
                <h2 id="detail-user-name">User Report</h2>
                <div class="analysis-card">
                    <h3>Performance Heatmap (Average Time per Key)</h3>
                    <div id="heatmap-keyboard-container" class="heatmap-keyboard-wrapper"></div>
                    <div id="user-advice-box" class="advice-content"></div>
                </div>
                <button class="close-modal">&times;</button>
            </div>
        </div>

        <div class="bg-glow"></div>
    </div>

    <!-- Pass PHP data to JS -->
    <script>
        window.TM_USER = <?php echo $isLoggedIn ? json_encode(['username' => $username, 'is_admin' => $isAdmin]) : 'null'; ?>;
    </script>
    <script src="script.js"></script>
</body>
</html>
