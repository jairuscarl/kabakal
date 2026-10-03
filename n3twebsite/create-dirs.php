<!DOCTYPE html>
<html>
<head>
    <title>Create Directories</title>
    <style>
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: white; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: #2d2d2d; padding: 30px; border-radius: 10px; }
        h1 { color: #A52A2A; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        pre { background: #1a1a1a; padding: 15px; border-radius: 5px; overflow-x: auto; }
        a { color: #A52A2A; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📁 Creating Required Directories</h1>
        <?php
        $dirs = [
            'public/uploads',
            'public/uploads/avatars',
            'public/uploads/reviews',
            'public/uploads/certificates',
            'logs'
        ];

        echo "<pre>";
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                // Try to create directory
                if (@mkdir($dir, 0755, true)) {
                    echo "<span class='success'>✓ Created: $dir</span>\n";
                } else {
                    echo "<span class='error'>✗ Failed to create: $dir</span>\n";
                    echo "  Please create manually:\n";
                    echo "  1. Go to: C:\\xampp\\htdocs\\N3tSkills\\\n";
                    echo "  2. Right-click → New → Folder\n";
                    echo "  3. Name it: " . str_replace('/', '\\\\', $dir) . "\n";
                }
            } else {
                echo "<span class='success'>✓ Already exists: $dir</span>\n";
            }
        }
        echo "</pre>";

        echo "<p><a href='debug.php'>→ Run Debug Check</a> | <a href='index.php'>→ Go to Homepage</a></p>";
        ?>
    </div>
</body>
</html>