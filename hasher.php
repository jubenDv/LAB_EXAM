<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST["password"];
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Password Hasher</title>
</head>
<body>
    <h2>PHP Password Hasher</h2>
    <form method="post">
        <label for="password">Enter Password:</label>
        <input type="text" id="password" name="password" required>
        <button type="submit">Generate Hash</button>
    </form>

    <?php if (!empty($hashedPassword)): ?>
        <h3>Hashed Password:</h3>
        <textarea readonly><?php echo htmlspecialchars($hashedPassword); ?></textarea>
    <?php endif; ?>
</body>
</html>
