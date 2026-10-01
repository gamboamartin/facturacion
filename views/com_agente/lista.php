<?php
declare(strict_types=1);

use config\generales;
use config\views;
use gamboamartin\template_1\nav;

/** @var \gamboamartin\comercial\controllers\controlador_com_agente $controlador */

$usa_diseno_nuevo = false;
if(property_exists(generales::class, 'com_agente_v2')){
    $usa_diseno_nuevo = (bool)generales::$com_agente_v2;
}

if(!$usa_diseno_nuevo){
    include (new views())->template_path('listas/content.php');
    return;
}
?>

<link rel="stylesheet" href="css/agentes-ivitec/agentes-ivitec.css">

<div class="clientes-layout">

    <aside class="clientes-sidebar">
        <div class="clientes-sidebar-title">
            <span class="sidebar-title-icon"><i class="bi bi-people"></i></span>
            Clientes
        </div>
        <nav class="clientes-sidebar-nav">
            <?php
            $nav_html = (new nav())->lis_menu_principal(
                links: $links_menu, secciones: $controlador->secciones_permitidas);
            if(\gamboamartin\errores\errores::$error){
                $error = (new \gamboamartin\errores\errores())->error(mensaje: 'Error al generar nav', data: $nav_html);
                print_r($error);
                exit;
            }
            echo $nav_html;
            ?>
        </nav>
    </aside>

    <main class="agentes-page">

        <?php include (new views())->ruta_templates . "mensajes.php"; ?>

        <nav class="iv-breadcrumb">
            <a href="index.php?seccion=adm_session&accion=inicio&session_id=<?php echo $_GET['session_id']; ?>">
                <i class="bi bi-house"></i>
            </a>
            <span>&gt;</span>
            <a href="#">Clientes</a>
            <span>&gt;</span>
            <strong><?php echo $controlador->seccion_titulo; ?></strong>
        </nav>

        <div class="agentes-header">
            <div class="agentes-header-info">
                <span class="page-icon"><i class="bi bi-people"></i></span>
                <div>
                    <h1><?php echo $controlador->seccion_titulo; ?></h1>
                    <p>Administra asesores, usuarios y seguimiento comercial.</p>
                </div>
            </div>
            <div class="agentes-header-actions">
                <?php foreach ($controlador->acciones_visibles_permitidas as $accion_visible): ?>
                    <?php echo $accion_visible->boton; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="agent-kpis">
            <div class="kpi-card">
                <span class="kpi-icon purple"><i class="bi bi-people"></i></span>
                <div class="kpi-body">
                    <span class="kpi-label">Total de agentes</span>
                    <div class="kpi-value-row">
                        <span class="kpi-value"><?php echo (int)($controlador->info_total_agentes ?? 0); ?></span>
                    </div>
                </div>
            </div>

            <div class="kpi-card">
                <span class="kpi-icon green"><i class="bi bi-check-circle"></i></span>
                <div class="kpi-body">
                    <span class="kpi-label">Agentes activos</span>
                    <div class="kpi-value-row">
                        <span class="kpi-value"><?php echo (int)($controlador->info_agentes_activos ?? 0); ?></span>
                    </div>
                </div>
            </div>

            <div class="kpi-card">
                <span class="kpi-icon purple"><i class="bi bi-person"></i></span>
                <div class="kpi-body">
                    <span class="kpi-label">Asesores</span>
                    <div class="kpi-value-row">
                        <span class="kpi-value"><?php echo (int)($controlador->info_asesores ?? 0); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="filtros-avanzados agentes-filters">
            <div class="search-field">
                <span class="search-icon"><i class="bi bi-search"></i></span>
                <input type="text" id="buscar_agente" data-filtro_campo="com_agente.descripcion"
                       placeholder="Buscar por agente, usuario, correo o teléfono...">
            </div>
            <div class="filter-field">
                <label for="filtro_tipo">Tipo</label>
                <select id="filtro_tipo" data-filtro_campo="com_tipo_agente.id">
                    <option value="">Todos</option>
                    <?php foreach ($controlador->tipos_agente ?? [] as $tipo): ?>
                        <option value="<?php echo (int)$tipo['com_tipo_agente_id']; ?>">
                            <?php echo htmlspecialchars($tipo['com_tipo_agente_descripcion']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-field">
                <label for="filtro_estatus">Estatus</label>
                <select id="filtro_estatus" data-filtro_campo="com_agente.status">
                    <option value="">Todos</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
            <div class="filters-actions">
                <button id="filtrar" class="iv-btn iv-btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
                <button id="limpiar" class="iv-btn iv-btn-secondary"><i class="bi bi-x-circle"></i> Limpiar filtros</button>
            </div>
        </div>

        <div class="agents-table-card">
            <div class="agents-table-header">
                <h2><span class="title-bar"></span> Registro de agentes</h2>
            </div>
            <div class="table-scroll">
                <table class="datatable table table-striped agents-table"></table>
            </div>
        </div>

    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var actual = window.location.href;
    document.querySelectorAll('.clientes-sidebar-nav .nav-link').forEach(function (a) {
        if (a.href === actual) {
            a.classList.add('active');
            a.closest('li').classList.add('active');
        }
    });
});
</script>
<script src="js/com_agente/lista.js"></script>