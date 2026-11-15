<?php
/** Pure source-based enum regression. Run: php tests/regression/menu_theme.php
 * No application bootstrap, configuration file, database or network access.
 */
$source = file_get_contents(__DIR__ . '/../../templates/nav/_menu_superior.php');
$start = strpos($source, "\$iv_tema = 'original';");
$end = strpos($source, '// End theme enum.');
if ($start === false || $end === false || $end <= $start) {
    throw new RuntimeException('Theme source boundary missing');
}
$block = substr($source, $start, $end - $start);
$cases = [
    ['', 'original'],
    ["public static string \$menu_superior_tema='amarillo-pastel';", 'amarillo-pastel'],
    ["public static string \$menu_superior_tema='original';", 'original'],
    ['public static $menu_superior_tema=null;', 'original'],
    ['public static $menu_superior_tema=false;', 'original'],
    ['public static $menu_superior_tema=[];', 'original'],
    ['public static string $menu_superior_tema;', 'original'],
    ["private static string \$menu_superior_tema='amarillo-pastel';", 'original'],
    ["protected static string \$menu_superior_tema='amarillo-pastel';", 'original'],
    ["public string \$menu_superior_tema='amarillo-pastel';", 'original'],
    ["public static string \$menu_superior_tema='\" onmouseover=alert(1)';", 'original'],
    ["public static string \$menu_superior_tema='red;}body{display:none}';", 'original'],
];
set_error_handler(static function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
foreach ($cases as $index => [$declaration, $expected]) {
    $class = 'MenuThemeRegression' . $index;
    eval('class ' . $class . ' {' . $declaration . '}');
    $code = str_replace('\\config\\generales', '\\' . $class, $block);
    eval($code);
    if ($iv_tema !== $expected) {
        throw new RuntimeException('Theme regression case ' . $index);
    }
}
restore_error_handler();
$css = file_get_contents(__DIR__ . '/../../css/overrides.css');
$marker = '/* Optional environment theme.';
$themeStart = strpos($css, $marker);
if ($themeStart === false) throw new RuntimeException('Scoped theme CSS missing');
$themeCss = substr($css, $themeStart);
preg_match_all('/([^{}]+)\{/', $themeCss, $selectors);
foreach ($selectors[1] as $selector) {
    $commentEnd = strrpos($selector, '*/');
    if ($commentEnd !== false) $selector = substr($selector, $commentEnd + 2);
    foreach (explode(',', $selector) as $item) {
        if (!str_starts_with(trim($item), '.iv-nav[data-iv-theme="amarillo-pastel"]')) {
            throw new RuntimeException('Unscoped theme CSS');
        }
    }
}
echo "PASS: 12 enum/default/type/visibility/injection cases; exact CSS scope\n";
