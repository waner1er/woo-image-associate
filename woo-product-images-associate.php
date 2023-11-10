<?php
/*
Plugin Name: Import Products with Images
Description: Un plugin pour importer des produits avec des images associées selon le SKU d'un produit.
Version: 1.0
Author: Erwan RIVET
*/

// Action pour associer les images SKU à la galerie des produits WooCommerce
// add_action('admin_init', 'associate_sku_images_with_product_gallery');

function associate_sku_images_with_product_gallery()
{
    // Obtenir tous les produits WooCommerce
    $products = get_posts(
        array(
            'post_type' => 'product',
            'numberposts' => -1,
        )
    );

    // Parcourir tous les produits
    foreach ($products as $product) {
        // Obtenir le SKU du produit
        $sku = get_post_meta($product->ID, '_sku', true);

        // Charger les images de la bibliothèque de médias
        $args = array(
            'post_type' => 'attachment',
            'numberposts' => -1,
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
        );

        $images = get_posts($args);

        // Tableau pour stocker les images correspondantes
        $matching_images = array();

        foreach ($images as $image) {
            $image_title = $image->post_title;

            // Utiliser une regex pour vérifier si le titre correspond au motif du SKU
            if (preg_match('/^' . preg_quote($sku) . '_/', $image_title)) {
                // L'image correspond au SKU du produit
                $image_id = $image->ID;
                $matching_images[] = $image_id;
            }
        }

        $gallery_ids = get_post_meta($product->ID, '_product_image_gallery', true);
        if (!is_array($gallery_ids)) {
            $gallery_ids = array();
        }

        // Ajouter toutes les images correspondantes à la galerie du produit
        $gallery_ids = array_merge($gallery_ids, $matching_images);

        // Supprimer les doublons
        $gallery_ids = array_unique($gallery_ids);

        // Mettre à jour la galerie d'images du produit
        update_post_meta($product->ID, '_product_image_gallery', implode(',', $gallery_ids));
    }
}

// Ajout d'un sous-menu dans le menu Extensions 
add_action('admin_menu', 'add_plugin_submenu');

function add_plugin_submenu()
{
    add_submenu_page('woocommerce', 'Importer les images SKU', 'Importer les images SKU', 'manage_options', 'import-sku-images', 'import_images_page');
}

function import_images_page()
{
    // Affichage de la page d'importation des images ici
    $nonce = wp_create_nonce('import_images_nonce');
    ?>
    <div class="wrap">
        <h2>Importer les images SKU</h2>
        <form method="post" action="<?php echo admin_url('admin-post.php') ?>">
            <input type="hidden" name="action" value="import_images">
            <input type="hidden" name="import_images_nonce" value="<?php echo $nonce ?>">
            <input type="submit" class="button button-primary" name="import_images_button" value="Lancer l'importation">
        </form>
    </div>
    <?php
}

add_action('admin_post_import_images', 'process_import_images');

function process_import_images()
{
    // Vérification du nonce
    if (isset($_POST['import_images_nonce']) && wp_verify_nonce($_POST['import_images_nonce'], 'import_images_nonce')) {
        if (isset($_POST['import_images_button'])) {
            associate_sku_images_with_product_gallery();
        }
        wp_redirect(admin_url('admin.php?page=import-sku-images'));
        exit;
    }
}
