<?php
/** Read-only preservation check; no application bootstrap, DB or configuration. */
$expected = json_decode(<<<'JSON'
{"fc_factura": ["ffa98506e8aeb93e6fa3c7559b7742cda6b82325365be082e669f87de38d63bf", "08fcb7ef864f14d0ca7bd8da3a9b6f4e5c02de6ab10845f6885211e06af7b789"], "fc_nota_credito": ["039cb5bd9c1ef5cb74983bbcf95531600a9ddb5650b7fbd3cea798e023efba82", "c8460a036b7ebc5933c4668b170cfe398f1928e5fe1c2c3d9c9d3e69a51b3bc4"]}
JSON, true);
foreach ($expected as $entity => [$phpHash, $tagHash]) {
    $source = file_get_contents(__DIR__ . '/../../views/' . $entity . '/modifica.php');
    preg_match_all('/<\?php.*?\?>/s', $source, $blocks);
    if (hash('sha256', implode('', $blocks[0])) !== $phpHash) throw new RuntimeException('PHP/order changed: ' . $entity);
    $masked = preg_replace_callback('/<\?php.*?\?>/s', static fn($m) => 'PHP_' . hash('sha256', $m[0]), $source);
    preg_match_all('/<(?:form|input|button|script)\b[^>]*>/s', $masked, $tags);
    if (hash('sha256', implode('', $tags[0])) !== $tagHash) throw new RuntimeException('Form/control/script contract changed: ' . $entity);
    if (!str_contains($source, 'fc-modifica fc-invoice-form fc-edit')) throw new RuntimeException('Wrapper missing');
    if (substr_count($source, 'css/fc_factura/modifica.css?v=2') !== 1) throw new RuntimeException('Shared edit CSS missing');
    // Check the JS-critical .partidas ancestor around the entry totals.
    $scopeSource = preg_replace_callback('/<\?php.*?\?>/s', static function($m) {
        return str_contains($m[0], '$controlador->inputs->partidas->subtotal') ||
            str_contains($m[0], '$controlador->inputs->partidas->total;') ? '<input data-test-partida-total>' : '';
    }, $source);
    preg_match_all('/<div\b[^>]*>|<\/div>|<input data-test-partida-total>/', $scopeSource, $scopeTags);
    $ancestors = []; $scopedTotals = 0;
    foreach ($scopeTags[0] as $tag) {
        if ($tag === '</div>') { array_pop($ancestors); }
        elseif ($tag === '<input data-test-partida-total>') {
            if (!in_array(true, $ancestors, true)) throw new RuntimeException('Entry total lost .partidas ancestor');
            $scopedTotals++;
        } else {
            preg_match('/class="([^"]*)"/', $tag, $classes);
            $ancestors[] = isset($classes[1]) && in_array('partidas', explode(' ', $classes[1]), true);
        }
    }
    if ($scopedTotals !== 2) throw new RuntimeException('Expected both scoped entry totals');
    preg_match_all('/<div\b[^>]*>|<\/div>/', $masked, $divs);
    $depth = 0;
    foreach ($divs[0] as $tag) {
        $depth += $tag === '</div>' ? -1 : 1;
        if ($depth < 0) throw new RuntimeException('Unexpected closing div: ' . $entity);
    }
    if ($depth !== 0) throw new RuntimeException('Unclosed div: ' . $entity);
}
echo "PASS: exact PHP/order and form/input/button/script contracts, wrappers, CSS, balanced divs; both views\n";
