<?php

require_once '../includes/auth.php';
require_once '../config/database.php';

verify_csrf();

header('Content-Type: application/json');

$db = (new Database())->connect();

$user = $_SESSION['user_id'];

$id = (int)$_POST['course_id'];

try{

    $db->beginTransaction();

    if($_POST['action']==='enroll'){

        $c = $db->prepare("
            SELECT *
            FROM courses
            WHERE id=?
            FOR UPDATE
        ");

        $c->execute([$id]);

        $course = $c->fetch();

        if(!$course)
            throw new Exception('Course not found');

        if($course['current_enrolled'] >= $course['max_seats'])
            throw new Exception('Course full');

        $check = $db->prepare("
            SELECT id
            FROM enrollments
            WHERE student_id=? AND course_id=?
        ");

        $check->execute([$user,$id]);

        if($check->fetch())
            throw new Exception('Already enrolled');

        $db->prepare("
            INSERT INTO enrollments
            (student_id,course_id,status)
            VALUES (?,?,'active')
        ")->execute([$user,$id]);

        $db->prepare("
            UPDATE courses
            SET current_enrolled=current_enrolled+1
            WHERE id=?
        ")->execute([$id]);

    }else{

        $del = $db->prepare("
            DELETE FROM enrollments
            WHERE student_id=? AND course_id=?
        ");

        $del->execute([$user,$id]);

        if(!$del->rowCount())
            throw new Exception('Enrollment not found');

        $db->prepare("
            UPDATE courses
            SET current_enrolled=current_enrolled-1
            WHERE id=? AND current_enrolled>0
        ")->execute([$id]);
    }

    $db->commit();

    echo json_encode([
        'status'=>'success'
    ]);

}catch(Exception $e){

    $db->rollBack();

    echo json_encode([
        'status'=>'error',
        'message'=>$e->getMessage()
    ]);
}