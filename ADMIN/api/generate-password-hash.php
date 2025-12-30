<?php
/**
 * Generate Password Hash
 * This script generates a bcrypt hash for the password "Admin#123"
 */

$password = 'Admin#123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: $password\n";
echo "Hash: $hash\n";
echo "\n";
echo "SQL Update Statement:\n";
echo "UPDATE admin_user SET password = '$hash', updated_at = NOW() WHERE email = 'joecel519@gmail.com';\n";
?>

