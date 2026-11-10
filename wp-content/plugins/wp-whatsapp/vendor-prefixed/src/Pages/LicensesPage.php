<?php

namespace WhatsappScoped\YayCommerce\AdminShell\Pages;

/**
 * Renders the Licenses admin page.
 * Ported from YayMail Pro LicensesMenu.php.
 * @internal
 */
class LicensesPage
{
    public static function render() : void
    {
        ?>
        <script>
            document.querySelector("#wpbody-content").innerHTML = "";
        </script>
        <?php 
        include __DIR__ . '/../../views/licenses-page.php';
    }
    public static function load_data() : void
    {
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_scripts']);
    }
    public static function enqueue_scripts() : void
    {
        $assets_url = \plugin_dir_url(__FILE__) . '../../assets/';
        wp_enqueue_style('yaycommerce-licenses', $assets_url . 'css/licenses.css', [], '1.0');
    }
}
