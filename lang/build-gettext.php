<?php
/**
 * This script converts the $LANG array to gettext po files and creates 
 * an empty pot (PO Template) file for additional translations.
 */

define('__DEFAULT_LOCALE__', 'en_GB');

if ((!file_exists(__DIR__. '/' . __DEFAULT_LOCALE__ . '/lang.php')) || (!file_exists(__DIR__. '/' . __DEFAULT_LOCALE__ . '/info.xml'))) {
    die("\nError: The default language could not be found.\n");
}

if (!file_exists(dirname(__DIR__) . '/language')) {
    mkdir(dirname(__DIR__) . '/language');
}

if ((!is_dir(dirname(__DIR__) . '/language')) || (!is_writable(dirname(__DIR__) . '/language'))) {
    die("\nError: Output path does not exist or is not writable.\n");
}

// Create a POT file
$pot = <<<EOF
msgid ""
msgstr ""
"Project-Id-Version: Simple Invoices v2.0.0\\n"
"POT-Creation-Date: 2016-10-21 22:30+0100\\n"
"PO-Revision-Date: \\n"
"Last-Translator: Your Name <you@example.com>\\n"
"Language-Team: Your Team <translations@example.com>\\n"
"Report-Msgid-Bugs-To: Translator Name <translations@example.com>\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=n != 1;\\n"
"X-Textdomain-Support: yes\\n"
"X-Generator: Poedit 1.6.4\\n"
"X-Poedit-SourceCharset: UTF-8\\n"
"X-Poedit-KeywordsList: __;_e;esc_html_e;esc_html_x:1,2c;esc_html__;esc_attr_e;esc_attr_x:1,2c;esc_attr__;_ex:1,2c;_nx:4c,1,2;_nx_noop:4c,1,2;_x:1,2c;_n:1,2;_n_noop:1,2;__ngettext:1,2;__ngettext_noop:1,2;_c,_nc:4c,1,2;\\n"
"X-Poedit-Basepath: ../\\n"
"X-Poedit-SearchPath-0: .\\n"
"X-Poedit-Language: English\\n"
"X-Poedit-Country: UNITED STATES\\n"
"X-Poedit-Bookmarks: \\n"
\n
EOF;

include(__DIR__ . '/' . __DEFAULT_LOCALE__ . '/lang.php');
// Copy the default messages for later use
$DEFAULTS = $LANG;

$POT_MESSAGES = array();
foreach ($LANG as $key => $value) {
    // Better style but will brake things so for now we do it old style
    //$message = $value;
    $message = $key;
    if (!mb_check_encoding($message, 'UTF-8')) {
        $message = mb_convert_encoding($message, 'UTF-8');
    }
    $message = addslashes($message);
 
    // For old style
    $val = $value;
    if (!mb_check_encoding($val, 'UTF-8')) {
        $val = mb_convert_encoding($val, 'UTF-8');
    }
    $val = addslashes($val);
    
    // Avoid duplicates
    if (!in_array($message, $POT_MESSAGES)) {
        // For easier translation on old fashion 
        $pot .= "#  $val\n";
        $pot .= "msgid \"$message\"\n";
        $pot .= "msgstr \"\"\n\n";
        
        $POT_MESSAGES[] = $message;
    }
}

file_put_contents(dirname(__DIR__) . '/language/SimpleInvoices.pot', $pot);

$paths = array();

// Load the language paths
if ($handle = opendir(__DIR__)) {
    /* This is the correct way to loop over the directory. */
    while (false !== ($entry = readdir($handle))) {
        if ((is_dir($entry)) && ($entry != "." && $entry != "..")) {
            if (file_exists(__DIR__ . '/' . $entry . '/info.xml')) {
                $paths[$entry] = __DIR__ . '/' . $entry;
            }
        }
    }

    closedir($handle);
}

// Read all languages
$LANGUAGE_LIST = array();

foreach ($paths as $locale => $path) {
    // check locale matches
    $values = simplexml_load_file($path . '/info.xml');

    if (strcmp($locale, $values->shortname) !== 0) {
        echo "Mismatch: " . $locale . " <> " . $values->shortname . "\n";
        continue;
    }

    $creationDate = null;
    if (isset($values->creationDate)) {
        $date = \DateTime::createFromFormat('Y-m-d', $values->creationDate);
        $creationDate = $date->format('Y-m-d 00:00:00+0000');
    }

    //echo $values->name . "\n\n";
    //echo $values->englishname . "\n\n";
    //echo $values->shortname . "\n\n";
    //echo $values->version . "\n\n";
    
    $lang = array(
        'name'         => (string) $values->name,
        'english_name' => (string) $values->englishname,
        'short_name'   => (string) $values->shortname,
    );
    $LANGUAGE_LIST[] = $lang;
 
    $author = null;
    if (isset($values->author) && isset($values->authorEmail)) {
        $author = sprintf('%s <%s>', $values->author, $values->authorEmail);
    } elseif (isset($values->author)) {
        $author = $values->author;
    } elseif (isset($values->authorEmail)) {
        $author = sprintf('<%s>', $values->authorEmail);
    }
    
    // Generate PO file
    $po = <<<EOF
msgid ""
msgstr ""
"Project-Id-Version: Simple Invoices v2.0.0\\n"
"POT-Creation-Date: $creationDate\\n"
"PO-Revision-Date: $creationDate\\n"
"Language-Team: \\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Last-Translator: $author\\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\\n"
"Language: $locale\\n"
EOF;
    
    include $path . '/lang.php';
    
    $foundMessages = array();
    foreach ($LANG as $key => $value) {
        $message = $key;
        if (!mb_check_encoding($message, 'UTF-8')) {
            $message = mb_convert_encoding($message, 'UTF-8');
        }
        $message = addslashes($message);
    
        $val = $value;
        if (!mb_check_encoding($val, 'UTF-8')) {
            $val = mb_convert_encoding($val, 'UTF-8');
        }
        $val = addslashes($val);
    
        // Avoid duplicates
        if (!in_array($message, $foundMessages)) {
            // For easier translation on old fashion
            if (isset($DEFAULTS[$key])) {
                $po .= "#  $DEFAULTS[$key]\n";
            }
            $po .= "msgid \"$message\"\n";
            
            if ((strcmp($locale, __DEFAULT_LOCALE__) !== 0) && (strcmp($DEFAULTS[$key], $value) === 0)) {
                $po .= "msgstr \"\"\n\n";
            } else {
                $po .= "msgstr \"$message\"\n\n";
            }
    
            $foundMessages[] = $message;
        }
    }
    
    file_put_contents(dirname(__DIR__) . '/language/' . $locale . '.po', $po);
}

// Write the language list json file
$json = json_encode($LANGUAGE_LIST, JSON_UNESCAPED_UNICODE  | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT );
file_put_contents(dirname(__DIR__) . '/language/languages.json', $json);