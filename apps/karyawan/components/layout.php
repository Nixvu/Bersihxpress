<?php
// Layout component untuk karyawan
function getKaryawanLayoutData() {
    return [
        'app_name' => 'BersihXpress - Karyawan',
        'css_files' => [
            '../../assets/css/style.css',
            '../../assets/css/webview.css'
        ],
        'js_files' => [
            '../../assets/js/webview.js',
            '../../assets/js/tailwind.js',
            '../../assets/js/icons.js',
            '../../assets/js/main.js'
        ]
    ];
}

function renderKaryawanHead($title = 'Dashboard Karyawan', $additional_css = []) {
    $layout = getKaryawanLayoutData();
    echo "<!DOCTYPE html>\n";
    echo "<html lang=\"id\">\n";
    echo "<head>\n";
    echo "    <meta charset=\"UTF-8\">\n";
    echo "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
    echo "    <title>{$title} - {$layout['app_name']}</title>\n";
    
    foreach ($layout['css_files'] as $css) {
        echo "    <link rel=\"stylesheet\" href=\"{$css}\">\n";
    }
    
    foreach ($additional_css as $css) {
        echo "    <link rel=\"stylesheet\" href=\"{$css}\">\n";
    }
    
    echo "</head>\n";
}

function renderKaryawanScripts($additional_js = []) {
    $layout = getKaryawanLayoutData();
    
    foreach ($layout['js_files'] as $js) {
        echo "    <script src=\"{$js}\"></script>\n";
    }
    
    foreach ($additional_js as $js) {
        echo "    <script src=\"{$js}\"></script>\n";
    }
}
?>