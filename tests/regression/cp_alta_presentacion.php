<?php
/** Pure template contract check. Run: php tests/regression/cp_alta_presentacion.php.
 * Reads source only; never loads application code or configuration.
 */
$root = dirname(__DIR__, 2);
$view = file_get_contents($root . '/views/fc_complemento_pago/alta.php');
$inputs = file_get_contents($root . '/templates/inputs/fc_complemento_pago/alta.php');
$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
$fields = ['fc_csd_id', 'com_sucursal_id', 'serie', 'folio', 'exportacion', 'fecha',
    'cat_sat_tipo_de_comprobante_id', 'cat_sat_forma_pago_id', 'cat_sat_metodo_pago_id',
    'cat_sat_moneda_id', 'com_tipo_cambio_id', 'cat_sat_uso_cfdi_id', 'observaciones'];
$expected = [
    '<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_complemento_pago $controlador  controlador en ejecucion */ ?>',
    '<?php use config\views; ?>',
];
foreach ($fields as $field) $expected[] = '<?php echo $controlador->inputs->' . $field . '; ?>';
$expected[] = "<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>";
preg_match_all('/<\?php.*?\?>/s', $inputs, $blocks);
$assert($blocks[0] === $expected, 'Input PHP blocks or order changed');
$assert(substr_count($inputs, 'echo $controlador->inputs->') === 13, 'Expected 13 inputs');
$assert(substr_count($inputs, 'botons/submit/alta_bd.php') === 1, 'Expected one submit include');
$assert(substr_count($inputs, '<section class="fc-alta-section"') === 3, 'Expected three cards');
foreach (['issuer', 'document', 'payment'] as $section) {
    $assert(substr_count($inputs, 'id="cp-alta-' . $section . '-title"') === 1, 'Unique card heading required');
}
$form = <<<'HTML'
<form method="post" action="<?php echo $controlador->link_alta_bd; ?>" class="form-additional" enctype="multipart/form-data" id="<?php echo 'form_'.$controlador->seccion."_".$controlador->accion; ?>">
HTML;
$assert(substr_count($view, $form) === 1, 'Form method/action/enctype/dynamic ID changed');
$assert(substr_count($view, '<form ') === 1 && substr_count($view, '</form>') === 1, 'Single form required');
$assert(substr_count($view, '<?php include $controlador->include_inputs_alta; ?>') === 1, 'Single input include required');
$assert(!str_contains($view, '<button') && !str_contains($view, 'type="submit"'), 'Do not duplicate submit in view');
foreach (['css/fc_factura/alta.css?v=2', 'css/fc_factura/form-responsive.css?v=1'] as $href) {
    $assert(substr_count($view, '<link rel="stylesheet" href="' . $href . '">') === 1, 'Shared stylesheet required');
}
$assert(!str_contains($view, '<script') && !str_contains($inputs, '<script'), 'No new scripts');
$assert(str_contains($view, '<h1>Nuevo complemento de pago</h1>'), 'CP title required');
echo "PASS: 13 ordered inputs, exact PHP blocks, form contract, single submit, three cards, shared CSS; source-only\n";
