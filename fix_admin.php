<?php
$dir = __DIR__;
$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$count = 0;
foreach($objects as $name => $object){
    if (strpos($name, '/.') !== false || strpos($name, '\\.') !== false) continue;
    if ($object->getExtension() == 'php') {
        $content = file_get_contents($name);
        
        // If the file uses BASE_URL before config is included
        // Actually, let's just make sure require_once __DIR__ . '/../config/supabase.php'; happens at the very top (after session_start) 
        // if there's a header('Location: ... BASE_URL ...') before require_once
        
        // Or simply: check if `header('Location:` with BASE_URL occurs BEFORE `require_once` of SupabaseClient or config.
        $posHeader = strpos($content, "header('Location: ' . BASE_URL");
        if ($posHeader !== false) {
            $posConfig = strpos($content, "config/supabase.php");
            $posClient = strpos($content, "SupabaseClient.php");
            
            $posRequire = false;
            if ($posConfig !== false) $posRequire = $posConfig;
            if ($posClient !== false) {
                if ($posRequire === false || $posClient < $posRequire) {
                    $posRequire = $posClient;
                }
            }
            
            if ($posRequire !== false && $posHeader < $posRequire) {
                // The redirect happens BEFORE the config is loaded!
                // Let's move the require_once UP.
                // Or easier: insert require_once at the top.
                
                $depth = substr_count(str_replace($dir, '', $name), '\\') + substr_count(str_replace($dir, '', $name), '/') - 1;
                if ($depth < 0) $depth = 0;
                $relStr = str_repeat('../', $depth);
                $requireStmt = "\nrequire_once __DIR__ . '/" . $relStr . "config/supabase.php';\n";
                
                $content = preg_replace('/<\?php/', '<?php' . $requireStmt, $content, 1);
                file_put_contents($name, $content);
                $count++;
                echo "Fixed $name\n";
            }
        }
    }
}
echo "Total updated: $count files.\n";
