<?php
session_start();
require_once 'functions.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$message = '';
$cards = getAllCards();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_card']) || isset($_POST['update_card'])) {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $location_name = $_POST['location_name'] ?? null;
        $image = $_FILES['image'] ?? null;
        $id = $_POST['card_id'] ?? 0;
        
        if (empty($title) || empty($description)) {
            $message = 'Title and description are required';
        } else {
            if (isset($_POST['add_card']) && $image && $image['error'] == 0) {
                $upload_result = uploadImage($image);
                if ($upload_result['success']) {
                    if (addCard($title, $description, $upload_result['path'], $location_name)) {
                        $message = 'Card added successfully!';
                        $cards = getAllCards();
                    } else {
                        $message = 'Error adding card to database';
                    }
                } else {
                    $message = $upload_result['message'];
                }
            } elseif (isset($_POST['update_card'])) {
                if ($image && $image['error'] == 0) {
                    $upload_result = uploadImage($image);
                    if ($upload_result['success']) {
                        if (updateCard($id, $title, $description, $upload_result['path'], $location_name)) {
                            $message = 'Card updated successfully!';
                            $cards = getAllCards();
                        } else {
                            $message = 'Error updating card';
                        }
                    } else {
                        $message = $upload_result['message'];
                    }
                } else {
                    if (updateCard($id, $title, $description, null, $location_name)) {
                        $message = 'Card updated successfully!';
                        $cards = getAllCards();
                    } else {
                        $message = 'Error updating card';
                    }
                }
            } else {
                $message = 'Image is required when adding a new card';
            }
        }
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (deleteCard($id)) {
        $message = 'Card deleted successfully!';
        $cards = getAllCards();
    } else {
        $message = 'Error deleting card';
    }
}

// Get card for editing
$edit_card = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_card = getCardById($id);
    if (!$edit_card) {
        $message = 'Card not found';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CardHubManager - Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Welcome, Admin <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
        <a href="logout.php" class="logout-btn">Logout</a>
    </header>
    
    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <div class="admin-container">
        <div class="card-form">
            <h2><?php echo $edit_card ? 'Edit Card' : 'Add New Card'; ?></h2>
            <form method="POST" action="admin.php" enctype="multipart/form-data">
                <?php if ($edit_card): ?>
                    <input type="hidden" name="card_id" value="<?php echo $edit_card['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo $edit_card ? htmlspecialchars($edit_card['title']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" required><?php 
                        echo $edit_card ? htmlspecialchars($edit_card['description']) : ''; 
                    ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="location_name">Location:</label>
                    <input type="text" id="location_name" name="location_name"
                           value="<?php echo $edit_card ? htmlspecialchars($edit_card['location_name'] ?? '') : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="image">Image:</label>
                    <input type="file" id="image" name="image" <?php echo $edit_card ? '' : 'required'; ?>>
                    <?php if ($edit_card): ?>
                        <small>Leave empty to keep current image</small>
                        <div class="current-image">
                            <img src="<?php echo htmlspecialchars($edit_card['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($edit_card['title']); ?>" width="100">
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" name="<?php echo $edit_card ? 'update_card' : 'add_card'; ?>" class="btn">
                    <?php echo $edit_card ? 'Update Card' : 'Add Card'; ?>
                </button>
                
                <?php if ($edit_card): ?>
                    <a href="admin.php" class="btn cancel-btn">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="cards-list">
            <h2>All Cards</h2>
            <?php if (empty($cards)): ?>
                <p class="no-cards">No cards found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cards as $card): ?>
                            <tr>
                                <td><img src="<?php echo htmlspecialchars($card['image_path']); ?>" alt="<?php echo htmlspecialchars($card['title']); ?>" width="50"></td>
                                <td><?php echo htmlspecialchars($card['title']); ?></td>
                                <td><?php echo htmlspecialchars(substr($card['description'], 0, 50)); ?>...</td>
                                <td><?php echo htmlspecialchars($card['location_name'] ?? 'N/A'); ?></td>
                                <td class="actions">
                                    <a href="admin.php?edit=<?php echo $card['id']; ?>" class="btn edit-btn">Edit</a>
                                    <a href="admin.php?delete=<?php echo $card['id']; ?>" class="btn delete-btn" 
                                       onclick="return confirm('Are you sure you want to delete this card?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>