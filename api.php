<?php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        $data = json_decode(file_get_contents('php://input'), true);
        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$data['username'], $hashed]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
        }
        break;

    case 'login':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        $user = $stmt->fetch();
        if ($user && password_verify($data['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['is_guest'] = (bool)$user['is_guest'];
            echo json_encode(['success' => true, 'user' => [
                'username' => $user['username'],
                'is_admin' => (bool)$user['is_admin'],
                'is_guest' => (bool)$user['is_guest']
            ]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
        break;

    case 'logout':
        session_destroy();
        setcookie('guest_token', '', time() - 3600, "/");
        echo json_encode(['success' => true]);
        break;

    case 'save_result':
        if (!isset($_SESSION['user_id'])) die(json_encode(['success' => false]));
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO typing_results (user_id, wpm, accuracy, difficulty, key_analysis) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $data['wpm'],
            $data['accuracy'],
            $data['difficulty'],
            json_encode($data['keyAnalysis'])
        ]);
        echo json_encode(['success' => true]);
        break;

    case 'get_leaderboard':
        $stmt = $pdo->query("SELECT u.username, r.wpm, r.accuracy, r.difficulty 
                             FROM typing_results r 
                             JOIN users u ON r.user_id = u.id 
                             WHERE r.id IN (
                                 SELECT MAX(t2.id) 
                                 FROM typing_results t2
                                 WHERE (t2.user_id, t2.wpm) IN (
                                     SELECT user_id, MAX(wpm) 
                                     FROM typing_results 
                                     GROUP BY user_id
                                 )
                                 GROUP BY t2.user_id
                             )
                             ORDER BY r.wpm DESC 
                             LIMIT 10");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'get_admin_stats':
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));
        }
        // Get overall progress for all users
        $stmt = $pdo->query("SELECT u.username, 
                                    COUNT(r.id) as sessions, 
                                    MAX(r.wpm) as max_wpm, 
                                    AVG(r.wpm) as avg_wpm,
                                    AVG(r.accuracy) as avg_acc
                             FROM users u
                             LEFT JOIN typing_results r ON u.id = r.user_id
                             GROUP BY u.id
                             ORDER BY sessions DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'get_user_analysis':
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            die(json_encode(['success' => false, 'message' => 'Unauthorized']));
        }
        $username = $_GET['username'] ?? '';
        $stmt = $pdo->prepare("SELECT r.key_analysis 
                             FROM typing_results r 
                             JOIN users u ON r.user_id = u.id 
                             WHERE u.username = ?");
        $stmt->execute([$username]);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $aggregate = [];
        foreach ($results as $json) {
            $data = json_decode($json, true);
            if (!$data) continue;
            foreach ($data as $key => $times) {
                if (!isset($aggregate[$key])) $aggregate[$key] = [];
                $aggregate[$key] = array_merge($aggregate[$key], $times);
            }
        }
        
        $final = [];
        foreach ($aggregate as $key => $times) {
            $final[$key] = array_sum($times) / count($times);
        }
        
        echo json_encode(['success' => true, 'analysis' => $final, 'username' => $username]);
        break;

    case 'update_profile':
        if (!isset($_SESSION['user_id'])) die(json_encode(['success' => false, 'message' => 'Not logged in']));
        $data = json_decode(file_get_contents('php://input'), true);
        $newUsername = trim($data['username'] ?? '');
        $newPassword = $data['password'] ?? '';
        
        if (empty($newUsername)) {
            die(json_encode(['success' => false, 'message' => 'Username cannot be empty']));
        }

        try {
            // Check if username already exists
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check->execute([$newUsername, $_SESSION['user_id']]);
            if ($check->fetch()) {
                die(json_encode(['success' => false, 'message' => 'Username already taken']));
            }

            if (!empty($newPassword)) {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, is_guest = FALSE WHERE id = ?");
                $stmt->execute([$newUsername, $hashed, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, is_guest = FALSE WHERE id = ?");
                $stmt->execute([$newUsername, $_SESSION['user_id']]);
            }
            
            $_SESSION['username'] = $newUsername;
            $_SESSION['is_guest'] = false;
            setcookie('guest_token', $newUsername, time() + (86400 * 30), "/");
            
            echo json_encode(['success' => true, 'username' => $newUsername]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating profile']);
        }
        break;

    default:
        echo json_encode(['message' => 'Invalid action']);
}
?>
