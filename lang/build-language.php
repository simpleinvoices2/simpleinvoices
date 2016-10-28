<?php

if (!is_dir(dirname(__DIR__) . '/language')) {
    mkdir(dirname(__DIR__) . '/language');
}

// Load the language paths
$paths = [];
if ($handle = opendir(__DIR__)) {
    /* This is the correct way to loop over the directory. */
    while (false !== ($entry = readdir($handle))) {
        if ((is_dir($entry)) && ($entry != "." && $entry != "..")) {
            if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'info.xml')) {
                $paths[$entry] = __DIR__ . DIRECTORY_SEPARATOR . $entry;
            }
        }
    }
    closedir($handle);
}

foreach($paths as $locale => $path) {
    // Load all messages
    include $path . DIRECTORY_SEPARATOR . 'lang.php';
    $allMessages = $LANG;
    
    $messages = [];
    
    // Load line by line
    $rowNum = 1;
    $file = file($path . DIRECTORY_SEPARATOR . 'lang.php');
    foreach ($file as $row) {
        $row = trim($row);

        if (preg_match('/^\$LANG\[\'(.*)\'\]\s*=\s*"(.*)";\/\/([10])\Z/', $row, $match)) {
            // Is it a valid key
            if (array_key_exists($match[1], $allMessages)) {
                $messages[$match[1]] = [
                    'key'        => $match[1],
                    'msg'        => $match[2],
                    'translated' => (bool) $match[3]
                ];
            } else {
                die("Failed for key " . $match[1]);
            }
        } elseif (preg_match('/^\$LANG/', $row)) {
            echo "  file: " . $path . DIRECTORY_SEPARATOR . "lang.php\n";
            echo "  line: " . $rowNum . "\n";
            echo "string: " . $row . "\n";
            die("\nError parsing file!\n");
        }
        
        $rowNum++;
    }
    
    if (count($messages) !== count($allMessages)) {
        echo "    file: " . $path . DIRECTORY_SEPARATOR . "lang.php\n";
        echo "Expected: " . count($allMessages) . " messages\n";
        echo "     Got: " . count($messages) . " messages\n";
        die("\nError parsing file!\n");
    } else {
        // =============================================================
        // --------------- CONVERT TO SIMPLE ARRAY START ---------------
        // Calculate largest key for padding
        $out  = "<?php\n";
        $out .= "return [\n";
        foreach ($allMessages as $key => $value) {
            if ($messages[$key]['translated']) {
                $out .= "    '" . $key . "' => '" . addslashes($value) . "',\n";
            }
        }
        $out .= "];";
        
        // Write the file
        file_put_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . $locale . '.php', $out);
        // ---------------- CONVERT TO SIMPLE ARRAY END ----------------
        // =============================================================
    }
}

echo "\nSuccess!\n";