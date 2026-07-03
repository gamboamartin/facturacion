<?php

function resuelve_catalogo(PDO $link, string $tabla, string $codigo, string $campo_codigo = 'codigo'): int
{
    $stmt = $link->prepare("SELECT id FROM {$tabla} WHERE {$campo_codigo} = :codigo LIMIT 1");
    $stmt->execute([':codigo' => $codigo]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : 0;
}