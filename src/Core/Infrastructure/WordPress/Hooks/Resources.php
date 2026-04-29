<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Hooks;
/**
 * Permite registrar los hooks relacionados con la carga de recursos (CSS, JS) en el panel de administración de WordPress para el plugin WP Data Merge.
 * 
 * @name Resources
 * @package WPDM\Core\Infrastructure\WordPress\Hooks
 * @since 1.0.0
 */
class Resources
{

    public function register(): void
    {
         add_action('admin_enqueue_scripts', [$this, 'enqueueAdminStyles']);
    }

     /**
      * Encola los estilos CSS personalizados para el panel de administración de WordPress, asegurándose de que solo se carguen en las páginas relacionadas con el plugin WP Data Merge.
      * @param string $hook
      * @return void
      */
     public function enqueueAdminStyles(string $hook): void
    {
        // Cargar en páginas del plugin WP Data Merge
        $isWpdmPage = strpos($hook, 'wpdm') !== false;
        
        // Cargar en páginas de edición del post type 'proyecto'
        $isProjectEdit = false;
        if (in_array($hook, ['post.php', 'post-new.php'], true)) {
            global $post_type;
            $isProjectEdit = $post_type === 'proyecto';
        }
        
        if (!$isWpdmPage && !$isProjectEdit) {
            return;
        }

        wp_enqueue_style(
            'wpdm-admin',
            WPDM_URL . 'assets/css/admin.css',
            [],
            WPDM_VERSION
        );
    }
}
