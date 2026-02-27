<?php
use config\views;

$views_cfg = new views();

if (is_callable([$views_cfg, 'template_path'])) {
    include $views_cfg->template_path('principal.php');
} else {
    include $views_cfg->ruta_templates . 'principal.php';
}