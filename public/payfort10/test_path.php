<?php
// payfort10/test_path.php
echo "Current file: " . __FILE__ . "<br>";
echo "Current dir: " . __DIR__ . "<br>";
echo "Parent 1: " . dirname(__DIR__) . "<br>";
echo "Parent 2: " . dirname(dirname(__DIR__)) . "<br>";
echo "Parent 3: " . dirname(dirname(dirname(__DIR__))) . "<br>";

// جرب تشوف فين الـ config
$paths = [
    __DIR__ . '/../../config/config.php',
    __DIR__ . '/../../../config/config.php',
    __DIR__ . '/../../../../config/config.php',
];

foreach ($paths as $path) {
    echo "<br>Checking: $path<br>";
    echo "Exists: " . (file_exists($path) ? "YES ✅" : "NO ❌") . "<br>";
}
