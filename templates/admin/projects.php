<?php
if (!defined('ABSPATH')) exit;

/** @var \WPDM\Core\Infrastructure\WordPress\Admin\Tables\UnitsListTable $table */
/** @var string[] $errors */
?>
<div class="wrap wpdm-wrap">
    <h1 class="wp-heading-inline">Proyectos</h1>
    <hr class="wp-header-end">

    <p>Unidades obtenidas desde el ERP SINCO para cada proyecto configurado.</p>

    <?php if (!empty($errors)) : ?>
        <div class="notice notice-error">
            <p><strong>Se encontraron errores al consultar SINCO:</strong></p>
            <ul style="list-style: disc; margin-left: 20px;">
                <?php foreach ($errors as $msg) : ?>
                    <li><?php echo esc_html($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="get">
        <input type="hidden" name="page" value="wpdm-projects" />
        <?php
        $table->search_box('Buscar unidades', 'wpdm-unit');
        $table->display();
        ?>
    </form>
</div>
