<?php
if (!defined('ABSPATH')) exit;

/** @var \WPDM\Core\Infrastructure\WordPress\Admin\Tables\UnitsListTable $table */
/** @var string[] $errors */
/** @var array{type:string,message:string}|false $notice */
?>
<div class="wrap wpdm-wrap">
    <h1 class="wp-heading-inline">Proyectos</h1>
    <hr class="wp-header-end">

    <p>Unidades obtenidas desde el ERP SINCO para cada proyecto configurado.</p>

    <?php if (!empty($notice) && is_array($notice)) :
        $cls = match ($notice['type'] ?? '') {
            'success' => 'notice-success',
            'warning' => 'notice-warning',
            default   => 'notice-error',
        };
    ?>
        <div class="notice <?php echo esc_attr($cls); ?> is-dismissible">
            <p><?php echo esc_html($notice['message']); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)) : ?>
        <div class="notice notice-error">
            <p><strong>Errores al consultar SINCO:</strong></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <?php foreach ($errors as $msg) : ?>
                    <li><?php echo esc_html($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="get" id="wpdm-units-form">
        <input type="hidden" name="page" value="wpdm-projects" />
        <?php
        $table->search_box('Buscar unidades', 'wpdm-unit');
        $table->display();
        ?>
    </form>
</div>

<script>
(function () {
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a.wpdm-sync-unit');
        if (!a) return;
        var name = a.dataset.name || ('ID ' + a.dataset.unit);
        if (!window.confirm('¿Sincronizar la unidad "' + name + '" con los datos actuales de SINCO?')) {
            e.preventDefault();
        }
    });

    var form = document.getElementById('wpdm-units-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            var topAction = form.querySelector('select[name="action"]');
            var botAction = form.querySelector('select[name="action2"]');
            var action = (topAction && topAction.value !== '-1' ? topAction.value : '')
                      || (botAction && botAction.value !== '-1' ? botAction.value : '');
            if (action === 'sync') {
                var count = form.querySelectorAll('input[name="unit[]"]:checked').length;
                if (count === 0) return;
                if (!window.confirm('¿Sincronizar ' + count + ' unidad(es) seleccionada(s)?')) {
                    e.preventDefault();
                }
            }
        });
    }
})();
</script>
