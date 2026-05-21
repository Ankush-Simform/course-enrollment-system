<?php
class User {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

 public function signup($name, $email, $password, $role_id) {
    try {
        $this->db->beginTransaction();
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)");
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt->execute([$name, $email, $hashed, $role_id]);

        $user_id = $this->db->lastInsertId();
        $stmtP = $this->db->prepare("INSERT INTO profiles (user_id) VALUES (?)");
        $stmtP->execute([$user_id]);

        $this->db->commit();
        return true;
    } catch (Exception $e) {
        $this->db->rollBack();
        return false;
    }
}
public function login($email, $password) {
    try {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }

        return false;

    } catch (Exception $e) {
        return false;
    }
}
}