<div class="card">
  <h3>Logo principal del sistema</h3>

  <?php
  $activo = $controlador->logo_activo ?? [];
  $logos  = $controlador->logos ?? [];

  // Aliases reales del ORM (tabla_campo)
  $ruta_activa = (string)($activo['doc_documento_ruta_relativa'] ?? '');
  $logo_activo_url = $ruta_activa !== ''
      ? '/facturacion/' . ltrim($ruta_activa, '/')
      : null;
  ?>

  <div style="margin:12px 0; padding:12px; border:1px solid #e5e7eb; border-radius:10px;">
    <div style="font-weight:600; margin-bottom:8px;">Logo actual</div>

    <?php if ($logo_activo_url): ?>
      <img src="<?= htmlspecialchars($logo_activo_url, ENT_QUOTES, 'UTF-8') ?>"
           alt="Logo actual"
           style="max-height:70px;width:auto;display:block;">
      <div style="font-size:12px;color:#6b7280;margin-top:6px;">
        ID: <?= (int)($activo['org_logo_id'] ?? 0) ?>
        <?= (($activo['org_logo_es_principal'] ?? '') === 'activo') ? '• PRINCIPAL' : '' ?>
      </div>
    <?php else: ?>
      <div style="color:#6b7280;">No hay logo activo todavía.</div>
    <?php endif; ?>
  </div>

  <div style="margin-top:14px;">
    <div style="font-weight:600; margin-bottom:8px;">Selecciona un logo para usar como principal</div>

    <form method="POST"
      action="index.php?seccion=org_logo&accion=activar_logo_bd&session_id=<?= htmlspecialchars($_GET['session_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
      onsubmit="if(!confirm('¿Quieres usar este logo como logo principal del sistema?')) return false; setTimeout(function(){ window.location.href='index.php?seccion=org_logo&accion=lista&session_id=<?= htmlspecialchars($_GET['session_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>'; }, 600); return true;">

      <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:12px;">
        <?php foreach ($logos as $l): ?>
          <?php
          $ruta = (string)($l['doc_documento_ruta_relativa'] ?? '');
          $url = $ruta !== '' ? '/facturacion/' . ltrim($ruta, '/') : '';
          $isPrincipal = (($l['org_logo_es_principal'] ?? '') === 'activo');
          $org_logo_id = (int)($l['org_logo_id'] ?? 0);
          ?>
          <label style="border:1px solid <?= $isPrincipal ? '#22c55e' : '#e5e7eb' ?>; border-radius:10px; padding:10px; cursor:pointer;">
            <div style="display:flex; gap:10px; align-items:center;">
              <input type="radio" name="org_logo_id"
                     value="<?= $org_logo_id ?>"
                     <?= $isPrincipal ? 'checked' : '' ?>>

              <div style="font-size:12px; color:#6b7280;">
                ID: <?= $org_logo_id ?> <?= $isPrincipal ? '• PRINCIPAL' : '' ?>
              </div>
            </div>

            <div style="margin-top:10px;">
              <?php if ($url !== ''): ?>
                <img src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
                     alt="Logo"
                     style="max-height:55px;width:auto;display:block;">
              <?php else: ?>
                <div style="font-size:12px;color:#ef4444;">Sin ruta_relativa</div>
              <?php endif; ?>
            </div>
          </label>
        <?php endforeach; ?>
      </div>

      <div style="margin-top:14px; display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary">Usar como logo principal</button>
        <a class="btn" href="index.php?seccion=org_logo&accion=lista&session_id=<?= htmlspecialchars($_GET['session_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">Volver</a>
      </div>

    </form>
  </div>
</div>
