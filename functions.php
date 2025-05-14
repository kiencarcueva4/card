<?php
require_once 'config.php';

function sanitizeInput($data) {
    global $conn;
    return htmlspecialchars(strip_tags($conn->real_escape_string($data)));
}

function loginUser($email, $password) {
    global $conn;
    
    $email = sanitizeInput($email);
    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        }
    }
    return false;
}

function getAllCards($search = '') {
    global $conn;
    
    $search = sanitizeInput($search);
    $sql = "SELECT id, title, description, location_name, image_path, created_at FROM cards";
    
    if (!empty($search)) {
        $sql .= " WHERE title LIKE '%$search%' OR description LIKE '%$search%' OR location_name LIKE '%$search%'";
    }
    
    $sql .= " ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    if (!$result) {
        error_log("Database error: " . $conn->error);
        return [];
    }
    
    $cards = [];
    while ($row = $result->fetch_assoc()) {
        $cards[] = $row;
    }
    
    return $cards;
}

function addCard($title, $description, $image_path, $location_name = null) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO cards (title, description, image_path, location_name) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ssss", $title, $description, $image_path, $location_name);
    return $stmt->execute();
}

function updateCard($id, $title, $description, $image_path = null, $location_name = null) {
    global $conn;
    
    if ($image_path) {
        $stmt = $conn->prepare("UPDATE cards SET title=?, description=?, image_path=?, location_name=? WHERE id=?");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        $stmt->bind_param("ssssi", $title, $description, $image_path, $location_name, $id);
    } else {
        $stmt = $conn->prepare("UPDATE cards SET title=?, description=?, location_name=? WHERE id=?");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        $stmt->bind_param("sssi", $title, $description, $location_name, $id);
    }
    
    return $stmt->execute();
}

function deleteCard($id) {
    global $conn;
    
    $id = (int)$id;
    $stmt = $conn->prepare("DELETE FROM cards WHERE id=?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function getCardById($id) {
    global $conn;
    
    $id = (int)$id;
    $stmt = $conn->prepare("SELECT * FROM cards WHERE id=?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function uploadImage($file) {
    $target_dir = "images/";
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            return ['success' => false, 'message' => 'Failed to create image directory'];
        }
    }
    
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'message' => 'File is not an image'];
    }
    
    if ($file["size"] > 2000000) {
        return ['success' => false, 'message' => 'File is too large (max 2MB)'];
    }
    
    if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'path' => $target_file];
    } else {
        return ['success' => false, 'message' => 'Error uploading file'];
    }
}

function registerUser($name, $email, $password) {
    global $conn;
    
    $name = sanitizeInput($name);
    $email = sanitizeInput($email);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("sss", $name, $email, $hashed_password);
    return $stmt->execute();
}

function emailExists($email) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}
?>