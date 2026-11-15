<?php
declare(strict_types=1);
/** Standalone regression: php -n -d extension=tokenizer tests/regression/cp_zero_totals.php
 * Reads the actual checkout method using PHP tokens; runs only that method in
 * memory with an error-reporting double. No application bootstrap, PDO or network.
 * This tests the payload generator, not full ORM persistence or fiscal stamping.
 */
function check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$path = dirname(__DIR__, 2) . '/orm/fc_complemento_pago.php';
$source = file_get_contents($path);
check(is_string($source), 'Cannot read checkout model');
check(function_exists('token_get_all'), 'PHP tokenizer required; run with -d extension=tokenizer');
$tokens = token_get_all($source, TOKEN_PARSE);
$methods = [];
for ($i = 0, $count = count($tokens); $i < $count; $i++) {
    if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
    $j = $i + 1;
    while ($j < $count && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) $j++;
    if (!is_array($tokens[$j] ?? null) || $tokens[$j][0] !== T_STRING || $tokens[$j][1] !== 'fc_partida_cp_ins') continue;
    $text = '';
    $depth = 0;
    $opened = false;
    for ($k = $i; $k < $count; $k++) {
        $token = $tokens[$k];
        $text .= is_array($token) ? $token[1] : $token;
        // Braces inside comments and quoted strings are tokens, not delimiters.
        if ($token === '{') { $depth++; $opened = true; }
        elseif ($token === '}') { $depth--; }
        if ($opened && $depth === 0) break;
    }
    check($opened && $depth === 0, 'Incomplete method body');
    $methods[] = $text;
}
check(count($methods) === 1, 'Expected exactly one payload method');
final class PayloadErrorDouble {
    public array $calls = [];
    public function error(string $mensaje, mixed $data): array {
        $this->calls[] = ['mensaje' => $mensaje, 'data' => $data];
        return ['error' => true, 'mensaje' => $mensaje, 'data' => $data];
    }
}
// Only the exact function token range is evaluated, never the model or its parents.
$probe = eval('return new class { public PayloadErrorDouble $error; public ' . $methods[0] . ' };');
$probe->error = new PayloadErrorDouble();
$checks = 0;
foreach ([[17, 29, 41], [1, 2, 3], [PHP_INT_MAX - 2, PHP_INT_MAX - 1, PHP_INT_MAX]] as [$unit, $product, $cp]) {
    $actual = $probe->fc_partida_cp_ins($unit, $product, $cp);
    $expected = [
        'com_producto_id' => $product,
        'cantidad' => 1,
        'descripcion' => 'Pago',
        'valor_unitario' => 0,
        'descuento' => 0,
        'total' => 0,
        'total_retenciones' => 0,
        'cat_sat_unidad_id' => $unit,
        'fc_complemento_pago_id' => $cp,
        'cat_sat_conf_imps_id' => 999,
    ];
    check($actual === $expected, 'Unexpected payload fields, values, types or input bindings');
    check($probe->error->calls === [], 'Valid IDs unexpectedly rejected');
    $checks++;
}
$names = ['cat_sat_unidad_id', 'com_producto_id', 'fc_complemento_pago_id'];
foreach ($names as $position => $name) {
    foreach ([0, -1, PHP_INT_MIN] as $invalid) {
        $ids = [17, 29, 41];
        $ids[$position] = $invalid;
        $probe->error->calls = [];
        $result = $probe->fc_partida_cp_ins(...$ids);
        check(count($probe->error->calls) === 1, 'Invalid ID must report exactly one error');
        check($result === ['error' => true, 'mensaje' => 'Error ' . $name . ' es menor a 0', 'data' => $invalid], 'ID rejection changed');
        check(!array_key_exists('com_producto_id', $result) && !array_key_exists('total', $result), 'Invalid ID produced a payment payload');
        $checks++;
    }
}
echo "PASS: {$checks} cases; checkout payload generator only; no DB or bootstrap.\n";
