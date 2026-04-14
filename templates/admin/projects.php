<?php
if (!defined('ABSPATH')) exit;

/** @var array<int, array{project: array{post_id:int,title:string,id_macroproject:string,id_proyectos:int[]}, id_proyecto:int, units: array<int, array<string,mixed>>}> $groups */
/** @var string[] $errors */

$columns = [
    'id'             => 'ID',
    'nombre'         => 'Nombre',
    'tipoUnidad'     => 'Tipo unidad',
    'tipoInmueble'   => 'Tipo inmueble',
    'estado'         => 'Estado',
    'valor'          => 'Valor',
    'areaPrivada'    => 'Área privada',
    'areaConstruida' => 'Área construida',
    'numeroPiso'     => 'Piso',
    'fechaEntrega'   => 'Entrega',
];
?>
<div class="wrap wpdm-wrap">
    <h1>Proyectos</h1>
    <p>Listado de unidades obtenidas desde el ERP SINCO para cada proyecto configurado.</p>

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

    <?php if (empty($groups)) : ?>
        <div class="wpdm-section">
            <p>No hay proyectos configurados. Crea entradas en el tipo de contenido <strong>Proyecto</strong> y completa los campos ACF <code>id_macroproject</code> e <code>id_proyecto</code>.</p>
            <p>Verifica también la <a href="<?php echo esc_url(admin_url('admin.php?page=wpdm-settings')); ?>">conexión API</a>.</p>
        </div>
    <?php else : ?>
        <?php foreach ($groups as $group) :
            $project = $group['project'];
            $units   = $group['units'];
        ?>
            <div class="wpdm-section" style="margin-bottom: 30px;">
                <h2 style="margin-top: 0;">
                    <?php echo esc_html($project['title']); ?>
                    <span style="font-weight: 400; color: #646970;">
                        — Macroproyecto <?php echo esc_html($project['id_macroproject']); ?>
                        / Proyecto <?php echo (int) $group['id_proyecto']; ?>
                        (<?php echo count($units); ?> unidades)
                    </span>
                </h2>

                <?php if (empty($units)) : ?>
                    <p style="color: #646970;">Sin unidades para mostrar.</p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <?php foreach ($columns as $label) : ?>
                                    <th><?php echo esc_html($label); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($units as $unit) : ?>
                                <tr>
                                    <?php foreach ($columns as $key => $label) :
                                        $value = $unit[$key] ?? '';
                                        if ($key === 'valor' && is_numeric($value)) {
                                            $value = '$' . number_format((float) $value, 0, ',', '.');
                                        } elseif ($key === 'fechaEntrega' && !empty($value)) {
                                            $ts = strtotime((string) $value);
                                            $value = $ts ? date_i18n('Y-m-d', $ts) : $value;
                                        } elseif (is_bool($value)) {
                                            $value = $value ? 'Sí' : 'No';
                                        }
                                    ?>
                                        <td><?php echo esc_html((string) $value); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
