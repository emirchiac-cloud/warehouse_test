<?php
// controllers/AuthController.php
// Controller managing user authentication, login validation, session state and logout

require_once __DIR__ . '/../data/UserData.php';

class AuthController {

    /**
     * Authenticates credentials, initiates session, and returns user array or throws Exception.
     */
    public static function login(string $identifier, string $password): array {
        $identifier = trim($identifier);
        $password   = trim($password);

        if (empty($identifier) || empty($password)) {
            throw new InvalidArgumentException("Email and password are required.");
        }

        $user = UserData::findByEmail($identifier);
        if (!$user || !UserData::verifyPassword($password, $user)) {
            throw new RuntimeException("Invalid email or password.");
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['id']        = (int)$user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['role']      = $user['role'];

        return [
            'id'        => (int)$user['id'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role'      => $user['role']
        ];
    }

    /**
     * Clears user session and destroys cookie.
     */
    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Retrieves current logged-in user session array or null.
     */
    public static function getCurrentUser(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_id'])) {
            return [
                'id'        => (int)$_SESSION['user_id'],
                'full_name' => $_SESSION['full_name'] ?? '',
                'email'     => $_SESSION['email'] ?? '',
                'role'      => $_SESSION['role'] ?? 'staff'
            ];
        }
        return null;
    }

    /**
     * Enforces page authentication; redirects to login if unauthenticated.
     */
    public static function requireAuth(?string $requiredRole = null): array {
        $user = self::getCurrentUser();
        if (!$user) {
            header("Location: login.php");
            exit;
        }
        if ($requiredRole !== null && $user['role'] !== $requiredRole) {
            http_response_code(403);
            echo "Access Denied: You do not have permission to view this resource.";
            exit;
        }
        return $user;
    }
}
