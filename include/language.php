<?php
use SimpleInvoices\SystemDefault\SystemDefaultManager;
/*
 * Read language informations
 * 1. reads default-language file
 * 2. reads requested language file
 * 3. make some editing (Upper-Case etc.)
 * 
 * Not in each translated file need to be each all translations, only in the default-lang-file (english)
 */


//http_negotiate_language($langs, $result);
//print_r($result);
unset($LANG);

function getLanguageArray($lang = null) 
{
	global $serviceManager;
    
	if (empty($lang)) {
	    $systemDefaults = $serviceManager->get(SystemDefaultManager::class);
	    $lang           = $systemDefaults->get('language', 'en_GB');
	}
	
	return $serviceManager->get(\Zend\I18n\Translator\TranslatorInterface::class)->getAllMessages('default', $lang)->getArrayCopy();
}

function getLanguageList() {
	$xmlFile = "info.xml";
	$langPath = "lang/";
	$folders = null;
	
	if($handle = opendir($langPath)) {
		
		//TODO: catch ., .. and other bad folders
		for($i=0;$file = readdir($handle);$i++) {
			$folders[$i] = $file;
		}
		closedir($handle);
	}
	
	$languages = null;
	$i = 0;
	
	foreach($folders as $folder) {
		$file = $langPath.$folder."/".$xmlFile;
		if(file_exists($file)) {
			//echo $file."<br />";
			$values = simplexml_load_file($file);
			$languages[$i] = $values;
			$i++;
			//print_r($values);
			//echo $values->name;
		}
	}
	
	return $languages;
}

function getLocaleList()
{
    $xmlFile = "info.xml";
    $langPath = "lang/";
    $folders = null;
    
    if($handle = opendir($langPath)) {
        //TODO: catch ., .. and other bad folders
        for($i=0;$file = readdir($handle);$i++) {
            $folders[$i] = $file;
        }
        closedir($handle);
    }
    
    $locales = [];
    
    foreach($folders as $folder) {
        $file = $langPath.$folder."/".$xmlFile;
        if(file_exists($file)) {
            $values = simplexml_load_file($file);
            $locales[(string) $values->shortname] = (string) $values->shortname;
        }
    }
    
    return $locales;
}