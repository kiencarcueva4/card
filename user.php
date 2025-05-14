<?php
session_start();
require_once 'functions.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$search = $_GET['search'] ?? '';
$cards = getAllCards($search);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CardHubManager - User Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
        <a href="logout.php" class="logout-btn">Logout</a>
    </header>
    
    <div class="search-container">
        <form method="GET" action="user.php">
            <input type="text" name="search" placeholder="Search cards..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
        </form>
    </div>
    
    <div class="cards-container">
        <?php if (empty($cards)): ?>
            <p class="no-cards">No cards found.</p>
        <?php else: ?>
            <?php foreach ($cards as $card): ?>
                <div class="card">
                    <div class="card-image">
                        <img src="<?php echo htmlspecialchars($card['image_path']); ?>" alt="<?php echo htmlspecialchars($card['title']); ?>">
                    </div>
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($card['title']); ?></h3>
                        <p><?php echo htmlspecialchars($card['description']); ?></p>
                        <?php if (!empty($card['location_name'])): ?>
                            <div class="card-location">
                                <span class="location-icon">📍</span>
                                <?php echo htmlspecialchars($card['location_name']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>