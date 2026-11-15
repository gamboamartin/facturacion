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
$colorCases = [
    ["public static string \$menu_superior_color='#FFFFFF';", 'personalizado', '#FFFFFF', '#000000'],
    ["public static string \$menu_superior_color='#000000';", 'personalizado', '#000000', '#FFFFFF'],
    ["public static string \$menu_superior_color='#fff6d8';", 'personalizado', '#FFF6D8', '#000000'],
    ["public static string \$menu_superior_color='#7028ed';", 'personalizado', '#7028ED', '#FFFFFF'],
];
$invalidValues = [null, false, 123, [], '', '#fff', '#12345678', ' #123456', "#123456\n", '#GG0000', '#123456; color:red', '" onmouseover=alert(1)'];
foreach ($invalidValues as $value) {
    $colorCases[] = ['public static $menu_superior_color=' . var_export($value, true) . ';', 'original', null, null];
}
foreach (['private static string', 'protected static string', 'public string'] as $type) {
    $colorCases[] = [$type . " \$menu_superior_color='#FFFFFF';", 'original', null, null];
}
$colorCases[] = ['public static string $menu_superior_color;', 'original', null, null];
foreach ($colorCases as $index => [$declaration, $expected, $background, $ink]) {
    $class = 'MenuColorRegression' . $index;
    // Legacy pastel must NOT survive a present invalid color property.
    eval('class ' . $class . " {public static string \$menu_superior_tema='amarillo-pastel';" . $declaration . '}');
    eval(str_replace('\\config\\generales', '\\' . $class, $block));
    if ($iv_tema !== $expected) throw new RuntimeException('Color case ' . $index);
    $expectedStyle = $expected === 'personalizado' ? '--iv-env-bg:' . $background . ';--iv-env-ink:' . $ink . ';' : '';
    if ($iv_tema_style !== $expectedStyle) throw new RuntimeException('Unsafe color output ' . $index);
    if ($expected === 'personalizado' && max($iv_black_contrast, $iv_white_contrast) < 4.5) {
        throw new RuntimeException('Insufficient normal text contrast');
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
        if (!str_starts_with(trim($item), '.iv-nav[data-iv-theme="amarillo-pastel"]') &&
            !str_starts_with(trim($item), '.iv-nav[data-iv-theme="personalizado"]')) {
            throw new RuntimeException('Unscoped theme CSS');
        }
    }
}
if (!str_contains($themeCss, '.iv-nav[data-iv-theme="personalizado"] .iv-nav__link:focus-visible,') ||
    !str_contains($themeCss, '.iv-nav[data-iv-theme="personalizado"] .iv-nav__toggle:focus-visible { outline-color: var(--iv-env-ink); }')) {
    throw new RuntimeException('Custom theme focus must follow contrast ink');
}
if (substr_count($themeCss, 'rgba(127,127,127,.25)') !== 2) {
    throw new RuntimeException('Custom theme borders must remain subtle');
}
echo "PASS: 12 legacy cases + " . count($colorCases) . " color/contrast/precedence cases; exact CSS scope\n";
