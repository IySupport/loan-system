<?php
// Usage (CLI, on the target server): php public/tools/make_hash.php YourPassword123
if ($argc < 2) {
    echo "Usage: php make_hash.php <password>\n";
    exit(1);
}
echo password_hash($argv[1], PASSWORD_BCRYPT) . "\n";
