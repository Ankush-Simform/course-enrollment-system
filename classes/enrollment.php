<?php
class Enrollment {
    private $db;

    public function __construct($db_connection) {
        $this->db = $db_connection;
    }

    // 1. Enroll a student
    public function enroll($student_id, $course_id) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("INSERT INTO enrollments (student_id, course_id, status) VALUES (?, ?, 'active')");
            $stmt->execute([$student_id, $course_id]);

            $update = $this->db->prepare("UPDATE courses SET current_enrolled = current_enrolled + 1 WHERE id = ?");
            $update->execute([$course_id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function cancel($enrollment_id) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT course_id, status FROM enrollments WHERE id = ?");
            $stmt->execute([$enrollment_id]);
            $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($enrollment && $enrollment['status'] === 'active') {
                $up1 = $this->db->prepare("UPDATE enrollments SET status = 'cancelled' WHERE id = ?");
                $up1->execute([$enrollment_id]);

                $up2 = $this->db->prepare("UPDATE courses SET current_enrolled = current_enrolled - 1 WHERE id = ?");
                $up2->execute([$enrollment['course_id']]);

                $this->db->commit();
                return true;
            }
            return false;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

 public function getAll() {
    $sql = "SELECT e.*, u.name as student_name, c.course_name, t.name as teacher_name 
            FROM enrollments e
            LEFT JOIN users u ON e.student_id = u.id
            LEFT JOIN courses c ON e.course_id = c.id
            LEFT JOIN users t ON c.instructor_id = t.id
            ORDER BY e.enrolled_date DESC";
    return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

    public function getByStudent($student_id) {
        $stmt = $this->db->prepare("SELECT e.*, c.course_name FROM enrollments e 
                                    JOIN courses c ON e.course_id = c.id 
                                    WHERE e.student_id = ?");
        $stmt->execute([$student_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}