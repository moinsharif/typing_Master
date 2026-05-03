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

            <!-- Virtual Keyboard Section -->
            <section id="keyboard-wrapper" class="keyboard-section">
                <div class="keyboard-container">
                    <div class="keyboard"></div>
                    <div class="hands-overlay side-layout">
                        <!-- Left Hand -->
                        <svg class="hand-svg-side realistic" id="left-hand-svg" viewBox="0 0 300 300">
                            <g class="hand-group" id="left-hand">
                                <g class="finger-group" data-finger="l-pinky">
                                    <path class="finger-path" d="M33.1,132.4c0-53.1-5.3-79.8-16-79.8s-13.2,26.7-8,79.8"/>
                                    <path class="nail" d="M20.4,63.7v3.2c0,1.8-1.4,3.2-3.2,3.2l0,0c-1.8,0-3.2-1.4-3.2-3.2v-3.2c0-1.8,1.4-3.2,3.2-3.2l0,0 C18.9,60.6,20.4,62,20.4,63.7z"/>
                                </g>
                                <g class="finger-group" data-finger="l-ring">
                                    <path class="finger-path" d="M73,116.4c0-63.8-5.3-95.8-16-95.8s-13.2,31.9-8,95.8"/>
                                    <path class="nail" d="M60.3,31.8V35c0,1.8-1.4,3.2-3.2,3.2l0,0c-1.8,0-3.2-1.4-3.2-3.2v-3.2c0-1.8,1.4-3.2,3.2-3.2l0,0 C58.8,28.6,60.3,30.1,60.3,31.8z"/>
                                </g>
                                <g class="finger-group" data-finger="l-middle">
                                    <path class="finger-path" d="M112.9,108.4c0-69.1-5.3-103.7-16-103.7s-13.2,34.6-8,103.7"/>
                                    <path class="nail" d="M100.2,15.9v3.2c0,1.8-1.4,3.2-3.2,3.2l0,0c-1.8,0-3.2-1.4-3.2-3.2v-3.2c0-1.8,1.4-3.2,3.2-3.2l0,0 C98.7,12.7,100.2,14.1,100.2,15.9z"/>
                                </g>
                                <g class="finger-group" data-finger="l-index">
                                    <path class="finger-path" d="M152.8,116.4c0-58.6-5.3-87.8-16-87.8s-13.2,29.2-8,87.8"/>
                                    <path class="nail" d="M140.1,39.8V43c0,1.8-1.4,3.2-3.2,3.2l0,0c-1.8,0-3.2-1.4-3.2-3.2v-3.2c0-1.8,1.4-3.2,3.2-3.2l0,0 C138.6,36.6,140.1,38.1,140.1,39.8z"/>
                                </g>
                                <g class="finger-group" data-finger="l-thumb">
                                    <path class="finger-path" d="M164,175.1c71.5-82.5,53.5-115.9-9.9-32.2"/>
                                    <path class="nail" d="M203.4,101.4l1.3,0.8c1.4,0.8,1.9,2.6,1,4l-2.6,3.8c-0.8,1.4-2.9,1.9-4.3,1.1l-1.3-0.8c-1.4-0.8-1.9-2.6-1-4l2.6-3.8 C200.1,101.1,202.1,100.6,203.4,101.4z"/>
                                </g>
                                <path class="palm" d="M152.8,116.4c-2.4,48.7,18.7,63.5,22.3,91c6.2,47.1-6.5,80.6-41.7,86 c-92.9,14.4-124.3-84.7-124.3-161"/>
                            </g>
                        </svg>

                        <!-- Right Hand -->
                        <svg class="hand-svg-side realistic" id="right-hand-svg" viewBox="300 0 300 300">
                            <g class="hand-group" id="right-hand">
                                <g class="finger-group" data-finger="r-thumb">
                                    <path class="finger-path" d="M452.1,142.9c-63.4-83.6-81.4-50.3-9.9,32.2"/>
                                    <path class="nail" d="M407.1,102.5l2.6,3.8c1,1.4,0.5,3.2-1,4l-1.3,0.8c-1.4,0.8-3.5,0.3-4.3-1.1l-2.6-3.8c-1-1.4-0.5-3.2,1-4l1.3-0.8 C404.1,100.6,406.1,101.1,407.1,102.5z"/>
                                </g>
                                <g class="finger-group" data-finger="r-index">
                                    <path class="finger-path" d="M477.3,116.4c5.3-58.6,2.7-87.8-8-87.8c-10.7,0-16,29.2-16,87.8"/>
                                    <path class="nail" d="M469.3,36.6L469.3,36.6c1.8,0,3.2,1.4,3.2,3.2V43c0,1.8-1.4,3.2-3.2,3.2l0,0c-1.8,0-3.2-1.4-3.2-3.2v-3.2 C466.1,38.1,467.6,36.6,469.3,36.6z"/>
                                </g>
                                <g class="finger-group" data-finger="r-middle">
                                    <path class="finger-path" d="M517.2,108.4c5.3-69.1,2.7-103.7-8-103.7s-16,34.6-16,103.7"/>
                                    <path class="nail" d="M509.2,12.7L509.2,12.7c1.8,0,3.2,1.4,3.2,3.2v3.2c0,1.8-1.4,3.2-3.2,3.2l0,0c-1.8,0-3.2-1.4-3.2-3.2v-3.2 C506,14.1,507.5,12.7,509.2,12.7z"/>
                                </g>
                                <g class="finger-group" data-finger="r-ring">
                                    <path class="finger-path" d="M557.1,116.4c5.3-63.8,2.7-95.8-8-95.8c-10.7,0-16,31.9-16,95.8"/>
                                    <path class="nail" d="M549.1,28.6L549.1,28.6c1.8,0,3.2,1.4,3.2,3.2V35c0,1.8-1.4,3.2-3.2,3.2l0,0c-1.8,0-3.2-1.4-3.2-3.2v-3.2 C545.9,30.1,547.4,28.6,549.1,28.6z"/>
                                </g>
                                <g class="finger-group" data-finger="r-pinky">
                                    <path class="finger-path" d="M597,132.4c5.3-53.1,2.7-79.8-8-79.8s-16,26.7-16,79.8"/>
                                    <path class="nail" d="M589,60.6L589,60.6c1.8,0,3.2,1.4,3.2,3.2v3.2c0,1.8-1.4,3.2-3.2,3.2l0,0c-1.8,0-3.2-1.4-3.2-3.2v-3.2 C585.8,62,587.3,60.6,589,60.6z"/>
                                </g>
                                <path class="palm" d="M597,132.4c0,76.3-31.4,175.4-124.3,161c-35.1-5.4-47.9-38.9-41.7-86 c3.7-27.5,24.7-42.3,22.3-91"/>
                            </g>
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
