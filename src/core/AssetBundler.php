<?php
// Simple Asset Bundler
// In a real production environment, use Webpack/Vite/Gulp.
// This script combines CSS/JS files on the fly (or could be used to generate static files).

function bundle_css($files) {
    $content = '';
    foreach ($files as $file) {
        $path = PUBLIC_PATH . $file;
        if (file_exists($path)) {
            $content .= file_get_contents($path) . "\n";
        }
    }
    // Simple minification (remove comments and whitespace)
    $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
    $content = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $content);
    return $content;
}

function bundle_js($files) {
    $content = '';
    foreach ($files as $file) {
        $path = PUBLIC_PATH . $file;
        if (file_exists($path)) {
            $content .= file_get_contents($path) . ";\n";
        }
    }
    // JS Minification is complex and risky with regex. 
    // We will just concatenate for now to reduce HTTP requests.
    return $content;
}
