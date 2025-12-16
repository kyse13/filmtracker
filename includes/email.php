<?php
/**
 * FilmTracker - Система отправки email через Mail.ru SMTP
 * Верификация, восстановление пароля, уведомления
 */

if (!defined('FILMTRACKER')) {
    die('Прямой доступ запрещен');
}

class EmailSystem {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Отправка email через Mail.ru SMTP
     */
    public function sendEmail($to, $subject, $body, $is_html = true) {
        // Добавление в очередь
        $queue_id = $this->db->insert(
            "INSERT INTO email_queue (to_email, subject, body, status) VALUES (?, ?, ?, 'pending')",
            [$to, $subject, $body]
        );
        
        // Попытка немедленной отправки
        $result = $this->sendViaSMTP($to, $subject, $body, $is_html);
        
        if ($result['success']) {
            $this->db->query(
                "UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?",
                [$queue_id]
            );
            
            // Логирование
            $this->db->insert(
                "INSERT INTO email_logs (to_email, subject, type, status) VALUES (?, ?, 'general', 'sent')",
                [$to, $subject]
            );
        } else {
            $this->db->query(
                "UPDATE email_queue SET status = 'failed', error_message = ?, attempts = attempts + 1 WHERE id = ?",
                [$result['error'], $queue_id]
            );
        }
        
        return $result;
    }
    
    /**
     * Отправка через SMTP (Mail.ru)
     */
    private function sendViaSMTP($to, $subject, $body, $is_html = true) {
        // Используем PHPMailer если доступен, иначе прямую SMTP отправку
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return $this->sendWithPHPMailer($to, $subject, $body, $is_html);
        } else {
            return $this->sendWithDirectSMTP($to, $subject, $body, $is_html);
        }
    }
    
    /**
     * Прямая отправка через SMTP сокеты (без PHPMailer)
     */
    private function sendWithDirectSMTP($to, $subject, $body, $is_html) {
        try {
            // Функция для чтения ответа сервера (обрабатывает многострочные ответы)
            $readResponse = function($socket) {
                $response = '';
                while ($line = fgets($socket, 515)) {
                    $response .= $line;
                    if (substr($line, 3, 1) == ' ') {
                        break;
                    }
                }
                return $response;
            };
            
            // Создание SSL контекста
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            
            // Подключение к SMTP серверу
            $socket = @stream_socket_client(
                'ssl://' . SMTP_HOST . ':' . SMTP_PORT,
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            if (!$socket) {
                return ['success' => false, 'error' => "Не удалось подключиться к SMTP серверу: $errstr ($errno)"];
            }
            
            // Установка таймаута
            stream_set_timeout($socket, 30);
            
            // Чтение приветствия сервера
            $response = $readResponse($socket);
            if (substr($response, 0, 3) != '220') {
                fclose($socket);
                return ['success' => false, 'error' => 'Сервер не ответил: ' . trim($response)];
            }
            
            // EHLO
            fputs($socket, "EHLO " . SMTP_HOST . "\r\n");
            $response = $readResponse($socket);
            if (substr($response, 0, 3) != '250') {
                fclose($socket);
                return ['success' => false, 'error' => 'Ошибка EHLO: ' . trim($response)];
            }
            
            // AUTH LOGIN
            fputs($socket, "AUTH LOGIN\r\n");
            $response = $readResponse($socket);
            if (substr($response, 0, 3) != '334') {
                fclose($socket);
                return ['success' => false, 'error' => 'Ошибка AUTH LOGIN: ' . trim($response)];
            }
            
            // Отправка логина
            fputs($socket, base64_encode(SMTP_USER) . "\r\n");
            $response = $readResponse($socket);
            if (substr($response, 0, 3) != '334') {
                fclose($socket);
                return ['success' => false, 'error' => 'Ошибка логина: ' . trim($response)];
            }
            
            // Отправка пароля
            fputs($socket, base64_encode(SMTP_PASS) . "\r\n");
            $response = $readResponse($socket);
            if (substr($response, 0, 3) != '235') {
                fclose($socket);
                return ['success' => false, 'error' => 'Ошибка аутентификации. Проверьте логин и пароль: ' . trim($response)];
            }
            
            // MAIL FROM
            fputs($socket, "MAIL FROM: <" . EMAIL_FROM . ">\r\n");
            $response = $readResponse($socket);
            if (substr($response, 0, 3) != '250') {
                fclose($socket);
                return ['success' => false, 'error' => 'Ошибка MAIL FROM: ' . trim($response)];
            }
            
            // RCPT TO
            fputs($socket, "RCPT TO: <" . $to . ">\r\n");
            $response = $readResponse($socket);
            if (substr($response, 0, 3) != '250') {
                fclose($socket);
                return ['success' => false, 'error' => 'Ошибка RCPT TO: ' . trim($response)];
            }
            
            // DATA
            fputs($socket, "DATA\r\n");
            $response = $readResponse($socket);
            if (substr($response, 0, 3) != '354') {
                fclose($socket);
                return ['success' => false, 'error' => 'Ошибка DATA: ' . trim($response)];
            }
            
            // Заголовки письма
            $headers = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\r\n";
            $headers .= "To: <" . $to . ">\r\n";
            $headers .= "Reply-To: " . EMAIL_REPLY_TO . "\r\n";
            $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            
            if ($is_html) {
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            } else {
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            }
            
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            
            // Тело письма (заменяем точки в начале строк на ..)
            $body_encoded = str_replace("\n.", "\n..", $body);
            
            // Тело письма
            $message = $headers . "\r\n" . $body_encoded . "\r\n.\r\n";
            
            // Отправка письма
            fputs($socket, $message);
            $response = $readResponse($socket);
            
            if (substr($response, 0, 3) != '250') {
                fclose($socket);
                return ['success' => false, 'error' => 'Ошибка отправки письма: ' . trim($response)];
            }
            
            // QUIT
            fputs($socket, "QUIT\r\n");
            $readResponse($socket);
            fclose($socket);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Ошибка SMTP: ' . $e->getMessage()];
        }
    }
    
    /**
     * Отправка через PHPMailer (рекомендуется)
     */
    private function sendWithPHPMailer($to, $subject, $body, $is_html) {
        try {
            require_once INCLUDES_PATH . '/classes/PHPMailer.php';
            require_once INCLUDES_PATH . '/classes/SMTP.php';
            require_once INCLUDES_PATH . '/classes/Exception.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Настройки SMTP
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            
            // Отправитель и получатель
            $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
            $mail->addAddress($to);
            $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
            
            // Содержимое
            $mail->isHTML($is_html);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            if (!$is_html) {
                $mail->AltBody = strip_tags($body);
            }
            
            $mail->send();
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return ['success' => false, 'error' => $mail->ErrorInfo];
        }
    }
    
    /**
     * Отправка email верификации
     */
    public function sendVerificationEmail($user_id, $email) {
        // Генерация токена
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + VERIFICATION_TOKEN_EXPIRY);
        
        // Сохранение токена
        $this->db->insert(
            "INSERT INTO email_verification_tokens (user_id, token, expires_at) VALUES (?, ?, ?)",
            [$user_id, $token, $expires_at]
        );
        
        // Создание ссылки
        $verification_link = BASE_URL . '/verify-email?token=' . $token;
        
        // Загрузка шаблона
        $template = $this->loadEmailTemplate('verification', [
            'verification_link' => $verification_link,
            'expires_hours' => 24
        ]);
        
        // Отправка
        return $this->sendEmail($email, 'Подтверждение email - FilmTracker', $template);
    }
    
    /**
     * Отправка email восстановления пароля
     */
    public function sendPasswordResetEmail($user_id, $email) {
        // Генерация токена
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + PASSWORD_RESET_TOKEN_EXPIRY);
        
        // Удаление старых токенов
        $this->db->query(
            "DELETE FROM password_reset_tokens WHERE user_id = ? OR expires_at < NOW()",
            [$user_id]
        );
        
        // Сохранение токена
        $this->db->insert(
            "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)",
            [$user_id, $token, $expires_at]
        );
        
        // Создание ссылки
        $reset_link = BASE_URL . '/reset-password?token=' . $token;
        
        // Загрузка шаблона
        $template = $this->loadEmailTemplate('password_reset', [
            'reset_link' => $reset_link,
            'expires_hours' => 1
        ]);
        
        // Отправка
        return $this->sendEmail($email, 'Восстановление пароля - FilmTracker', $template);
    }
    
    /**
     * Отправка приветственного email
     */
    public function sendWelcomeEmail($user_id, $email, $username) {
        $template = $this->loadEmailTemplate('welcome', [
            'username' => $username,
            'login_link' => BASE_URL . '/login'
        ]);
        
        return $this->sendEmail($email, 'Добро пожаловать в FilmTracker!', $template);
    }
    
    /**
     * Загрузка шаблона email
     */
    private function loadEmailTemplate($template_name, $variables = []) {
        $template_path = TEMPLATES_PATH . '/emails/' . $template_name . '.php';
        
        if (!file_exists($template_path)) {
            return $this->getDefaultTemplate($template_name, $variables);
        }
        
        ob_start();
        extract($variables);
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Шаблон по умолчанию (если файл не найден)
     */
    private function getDefaultTemplate($template_name, $variables) {
        $content = '';
        
        switch ($template_name) {
            case 'verification':
                $content = '<h2>Подтвердите ваш email</h2><p>Перейдите по ссылке: ' . ($variables['verification_link'] ?? '') . '</p>';
                break;
            case 'password_reset':
                $content = '<h2>Восстановление пароля</h2><p>Перейдите по ссылке: ' . ($variables['reset_link'] ?? '') . '</p>';
                break;
            case 'welcome':
                $content = '<h2>Добро пожаловать, ' . ($variables['username'] ?? '') . '!</h2>';
                break;
        }
        
        return $this->wrapInHTMLTemplate($content);
    }
    
    /**
     * Обертка HTML шаблона
     */
    private function wrapInHTMLTemplate($content) {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FilmTracker</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0;">FilmTracker</h1>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        ' . $content . '
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
        <p style="color: #999; font-size: 12px; text-align: center;">
            Это автоматическое сообщение от FilmTracker. Пожалуйста, не отвечайте на это письмо.
        </p>
    </div>
</body>
</html>';
    }
    
    /**
     * Верификация email токена
     */
    public function verifyEmailToken($token) {
        $token_data = $this->db->fetchOne(
            "SELECT user_id, expires_at FROM email_verification_tokens WHERE token = ? AND expires_at > NOW()",
            [$token]
        );
        
        if (!$token_data) {
            return ['success' => false, 'error' => 'Недействительный или истекший токен'];
        }
        
        // Обновление статуса пользователя
        $this->db->query(
            "UPDATE users SET email_verified = 1 WHERE id = ?",
            [$token_data['user_id']]
        );
        
        // Удаление токена
        $this->db->query(
            "DELETE FROM email_verification_tokens WHERE token = ?",
            [$token]
        );
        
        // Отправка приветственного email
        $user = $this->db->fetchOne("SELECT email, username FROM users WHERE id = ?", [$token_data['user_id']]);
        if ($user) {
            $this->sendWelcomeEmail($token_data['user_id'], $user['email'], $user['username']);
        }
        
        return ['success' => true, 'user_id' => $token_data['user_id']];
    }
    
    /**
     * Проверка токена сброса пароля
     */
    public function verifyPasswordResetToken($token) {
        $token_data = $this->db->fetchOne(
            "SELECT user_id, expires_at, used FROM password_reset_tokens WHERE token = ? AND expires_at > NOW() AND used = 0",
            [$token]
        );
        
        if (!$token_data) {
            return ['success' => false, 'error' => 'Недействительный или истекший токен'];
        }
        
        return ['success' => true, 'user_id' => $token_data['user_id']];
    }
    
    /**
     * Сброс пароля по токену
     */
    public function resetPassword($token, $new_password) {
        $token_data = $this->verifyPasswordResetToken($token);
        
        if (!$token_data['success']) {
            return $token_data;
        }
        
        // Хеширование нового пароля
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Обновление пароля
        $this->db->query(
            "UPDATE users SET password_hash = ? WHERE id = ?",
            [$password_hash, $token_data['user_id']]
        );
        
        // Помечаем токен как использованный
        $this->db->query(
            "UPDATE password_reset_tokens SET used = 1 WHERE token = ?",
            [$token]
        );
        
        return ['success' => true, 'message' => 'Пароль успешно изменен'];
    }
}

