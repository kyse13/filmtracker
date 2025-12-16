<?php
if (!defined('FILMTRACKER')) {
    die('Прямой доступ запрещен');
}

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0);
            session_name(SESSION_NAME);
            session_start();
            
            if (!isset($_SESSION['created'])) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            } elseif (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    public function register($username, $email, $password, $confirm_password) {
        $errors = [];
        if (empty($username) || strlen($username) < 3) {
            $errors[] = 'Имя пользователя должно содержать минимум 3 символа';
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Некорректный email адрес';
        }
        
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Пароль должен содержать минимум ' . PASSWORD_MIN_LENGTH . ' символов';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Пароли не совпадают';
        }
        
        // Проверка существования пользователя
        $existing_user = $this->db->fetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        
        if ($existing_user) {
            $errors[] = 'Пользователь с таким именем или email уже существует';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        try {
            $this->db->beginTransaction();
            
            $user_id = $this->db->insert(
                "INSERT INTO users (username, email, password_hash, email_verified) VALUES (?, ?, ?, 0)",
                [$username, $email, $password_hash]
            );
            
            require_once INCLUDES_PATH . '/email.php';
            $emailSystem = new EmailSystem();
            $emailSystem->sendVerificationEmail($user_id, $email);
            
            $this->db->insert(
                "INSERT INTO user_email_preferences (user_id) VALUES (?)",
                [$user_id]
            );
            
            $this->db->commit();
            
            return ['success' => true, 'user_id' => $user_id, 'message' => 'Регистрация успешна! Проверьте email для подтверждения.'];
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Ошибка при регистрации. Попробуйте позже.']];
        }
    }
    
    public function login($email, $password, $remember = false) {
        $errors = [];
        
        if ($this->isLockedOut($email)) {
            $errors[] = 'Слишком много неудачных попыток. Попробуйте через 15 минут.';
            return ['success' => false, 'errors' => $errors];
        }
        
        $user = $this->db->fetchOne(
            "SELECT id, username, email, password_hash, role, email_verified, is_banned FROM users WHERE email = ?",
            [$email]
        );
        
        if (!$user) {
            $this->recordLoginAttempt($email, false);
            $errors[] = 'Неверный email или пароль';
            return ['success' => false, 'errors' => $errors];
        }
        
        if ($user['is_banned']) {
            $errors[] = 'Ваш аккаунт заблокирован';
            return ['success' => false, 'errors' => $errors];
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            $this->recordLoginAttempt($email, false);
            $errors[] = 'Неверный email или пароль';
            return ['success' => false, 'errors' => $errors];
        }
        
        $this->recordLoginAttempt($email, true);
        
        $this->db->query(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$user['id']]
        );
        
        $full_user = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$user['id']]);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email_verified'] = $user['email_verified'];
        $_SESSION['avatar'] = $full_user['avatar'] ?? null;
        $_SESSION['logged_in'] = true;
        
        session_regenerate_id(true);
        
        return [
            'success' => true,
            'user' => $user,
            'message' => $user['email_verified'] ? 'Добро пожаловать!' : 'Проверьте email для подтверждения аккаунта.'
        ];
    }
    
    public function logout() {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'email_verified' => $_SESSION['email_verified'] ?? 0,
            'avatar' => $_SESSION['avatar'] ?? null
        ];
    }
    
    public function isAdmin() {
        return $this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            require_once INCLUDES_PATH . '/functions.php';
            redirect(BASE_URL . '/login');
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            require_once INCLUDES_PATH . '/functions.php';
            redirect(BASE_URL . '/403');
        }
    }
    
    private function isLockedOut($email) {
        $recent_attempts = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM login_attempts 
             WHERE email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND) AND success = 0",
            [$email, LOGIN_LOCKOUT_TIME]
        );
        
        return $recent_attempts['count'] >= MAX_LOGIN_ATTEMPTS;
    }
    
    private function recordLoginAttempt($email, $success) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->db->insert(
            "INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, ?)",
            [$email, $ip_address, $success ? 1 : 0]
        );
    }
    
    public function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    public function verifyCSRFToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
}

$auth = new Auth();

