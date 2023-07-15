<?php
/*
Plugin Name: Nodle Pix Desconto & Parcelamento no Cartão
Description: Aplica desconto para pagamentos à vista no Pix e/ou boleto e exibe parcelamento no cartão. Solução pensada no mercado brasileiro.
Version: 2.2.1
Contributors: Rafael K B e Douglas G S
Author: Nodle - Desenvolvendo Soluções Reais
Author URI: https://nodle.com.br
*/
// Adiciona a versão mínima do PHP
if (version_compare(PHP_VERSION, '7.4', '<')) {
    // Exibe uma mensagem de erro caso a versão mínima não seja atendida
    die('Este plugin requer pelo menos o PHP 7.4 para funcionar corretamente.');
}
// Adiciona a versão mínima do WordPress
global $wp_version;
if (version_compare($wp_version, '6.0', '<')) {
    // Exibe uma mensagem de erro caso a versão mínima não seja atendida
    die('Este plugin requer pelo menos o WordPress 6.0 para funcionar corretamente.');
}
// Mostrar parcelamento no cartão
add_shortcode( 'price_parcelas', 'price_parcelas_shortcode' );
function price_parcelas_shortcode() {
    if ( is_product() ) {
        $product_id = get_the_ID();
        $product = wc_get_product( $product_id );
        $installment_enabled = get_option( 'installment_enabled', 'no' ); // verifica se o parcelamento está habilitado

        $sale_price = $product->get_sale_price();
        if ( $sale_price && $installment_enabled === 'yes' ) {
            $installment_max_times = get_option( 'installment_max_times', 12 ); // obtém o número máximo de parcelas das opções
            $installment_text_before = get_option( 'installment_text_before', 'em até' ); // obtém o texto antes do parcelamento das opções
            $installment_text_after_times = get_option( 'installment_text_after_times', 'x de' ); // obtém o texto após o número de parcelas das opções
            $installment_text_after_price = get_option( 'installment_text_after_price', 'Sem juros' ); // obtém o texto após o valor da parcela das opções
            $installment_min_amount_per_installment = get_option( 'installment_min_amount_per_installment', 0 ); // obtém o valor mínimo por parcela

            $installment_times = floor( $sale_price / $installment_min_amount_per_installment );
            $installment_price = $sale_price / $installment_times;
            $installment_price_html = wc_price( $installment_price );

            if ( $installment_times <= $installment_max_times ) {
                $installment_info = "$installment_text_before $installment_times $installment_text_after_times $installment_price_html $installment_text_after_price";
                return "<div class='installment-info-wrapper'>
                            <span class='installment-text'>$installment_info</span>
                        </div>";
            }
        }
    }

    return '';
}

// Mostrar valor no PIX
add_shortcode( 'price_pix', 'price_pix_shortcode' );
function price_pix_shortcode() {
    if ( is_product() ) {
        $product_id = get_the_ID();
        $product = wc_get_product( $product_id );
        $pix_discount_enabled = get_option( 'pix_discount_enabled', 'no' ); // verifica se o desconto no Pix está habilitado
        if ( $pix_discount_enabled === 'yes' ) {
            $discount_percentage = get_option( 'pix_discount_percentage', 10 ); // obtém o percentual de desconto das opções
            $prefix = get_option( 'pix_discount_prefix', 'ou' ); // obtém o texto antes do preço com desconto das opções
            $text_after_price = get_option( 'pix_discount_text_after', ' à vista no Pix com '.$discount_percentage.'% de desconto! 🔥' ); // obtém o texto após o preço com desconto das opções

            $sale_price = $product->get_sale_price();
            if ( $sale_price ) {
                $discounted_price = $sale_price - ( $sale_price * ( $discount_percentage / 100 ) );
                $discounted_price_html = wc_price( $discounted_price );
                return "<div class='price-with-discount-wrapper'>
                            <span class='prefix'>$prefix</span>
                            <span class='discounted-price'>$discounted_price_html</span>
                            <span class='text-after-price'>$text_after_price</span>
                        </div>";
            }
        }
    }

    return '';
}

// Mostrar preço do produto
add_shortcode( 'price_produto', 'price_produto_shortcode' );
function price_produto_shortcode() {
    if ( is_product() ) {
        $product_id = get_the_ID();
        $product = wc_get_product( $product_id );
        $price_html = $product->get_price_html();
        return "<div class='original-price-wrapper'>
                    <span class='original-price'>$price_html</span>
                </div>";
    }

    return '';
}


// Adicionar menu de configurações
add_action( 'admin_menu', 'pix_installment_add_admin_menu' );
add_action( 'admin_init', 'pix_installment_settings_init' );

function pix_installment_add_admin_menu() {
    add_submenu_page(
        'woocommerce',
        'Descontos e Parcelas',
        'Descontos e Parcelas',
        'manage_options',
        'pix-installment',
        'pix_installment_options_page'
    );
}

function pix_installment_settings_init() {
    // Configurações do desconto Pix
    add_settings_section( 'pix_discount_settings_section', 'Configurações do Desconto Pix/boleto', 'pix_discount_settings_section_callback', 'pix_installment_settings' );
    add_settings_field( 'pix_discount_enabled', 'Habilitar Desconto Pix/boleto', 'pix_discount_enabled_render', 'pix_installment_settings', 'pix_discount_settings_section' );
    add_settings_field( 'pix_discount_percentage', 'Percentual de Desconto', 'pix_discount_percentage_render', 'pix_installment_settings', 'pix_discount_settings_section' );
    add_settings_field( 'pix_discount_prefix', 'Texto antes do preço com desconto', 'pix_discount_prefix_render', 'pix_installment_settings', 'pix_discount_settings_section' );
    add_settings_field( 'pix_discount_text_after', 'Texto após o preço com desconto', 'pix_discount_text_after_render', 'pix_installment_settings', 'pix_discount_settings_section' );

    register_setting( 'pix_installment_settings', 'pix_discount_enabled' );
    register_setting( 'pix_installment_settings', 'pix_discount_percentage' );
    register_setting( 'pix_installment_settings', 'pix_discount_prefix' );
    register_setting( 'pix_installment_settings', 'pix_discount_text_after' );

    // Configurações do parcelamento
    add_settings_section( 'installment_settings_section', 'Configurações do Parcelamento', 'installment_settings_section_callback', 'pix_installment_settings' );
    add_settings_field( 'installment_enabled', 'Habilitar Parcelamento', 'installment_enabled_render', 'pix_installment_settings', 'installment_settings_section' );
    add_settings_field( 'installment_max_times', 'Número Máximo de Parcelas', 'installment_max_times_render', 'pix_installment_settings', 'installment_settings_section' );
    add_settings_field( 'installment_text_before', 'Texto antes do parcelamento', 'installment_text_before_render', 'pix_installment_settings', 'installment_settings_section' );
    add_settings_field( 'installment_text_after_times', 'Texto após o número de parcelas', 'installment_text_after_times_render', 'pix_installment_settings', 'installment_settings_section' );
    add_settings_field( 'installment_text_after_price', 'Texto após o valor da parcela', 'installment_text_after_price_render', 'pix_installment_settings', 'installment_settings_section' );
    add_settings_field( 'installment_min_amount_per_installment', 'Valor mínimo por parcela', 'installment_min_amount_per_installment_render', 'pix_installment_settings', 'installment_settings_section' );

    register_setting( 'pix_installment_settings', 'installment_enabled' );
    register_setting( 'pix_installment_settings', 'installment_max_times' );
    register_setting( 'pix_installment_settings', 'installment_text_before' );
    register_setting( 'pix_installment_settings', 'installment_text_after_times' );
    register_setting( 'pix_installment_settings', 'installment_text_after_price' );
    register_setting( 'pix_installment_settings', 'installment_min_amount_per_installment' );
}

function pix_discount_settings_section_callback() {
    echo 'Personalize as configurações do Desconto Pix/boleto:';
}

function pix_discount_enabled_render() {
    $value = get_option( 'pix_discount_enabled', 'no' );
    echo '<input type="checkbox" name="pix_discount_enabled" value="yes" ' . checked( $value, 'yes', false ) . ' />';
}

function pix_discount_percentage_render() {
    $value = get_option( 'pix_discount_percentage', 10 );
    echo '<input type="number" min="0" max="100" step="1" name="pix_discount_percentage" value="' . $value . '" />';
}

function pix_discount_prefix_render() {
    $value = get_option( 'pix_discount_prefix', 'ou' );
    echo '<input type="text" name="pix_discount_prefix" value="' . $value . '" />';
}

function pix_discount_text_after_render() {
    $value = get_option( 'pix_discount_text_after', ' à vista no Pix com '.$discount_percentage.'% de desconto! 🔥' );
    echo '<input type="text" name="pix_discount_text_after" value="' . $value . '" />';
}

function installment_settings_section_callback() {
    echo 'Personalize as configurações do Parcelamento:';
}

function installment_enabled_render() {
    $value = get_option( 'installment_enabled', 'no' );
    echo '<input type="checkbox" name="installment_enabled" value="yes" ' . checked( $value, 'yes', false ) . ' />';
}

function installment_max_times_render() {
    $value = get_option( 'installment_max_times', 12 );
    echo '<input type="number" min="1" step="1" name="installment_max_times" value="' . $value . '" />';
}

function installment_text_before_render() {
    $value = get_option( 'installment_text_before', 'em até' );
    echo '<input type="text" name="installment_text_before" value="' . $value . '" />';
}

function installment_text_after_times_render() {
    $value = get_option( 'installment_text_after_times', 'x de' );
    echo '<input type="text" name="installment_text_after_times" value="' . $value . '" />';
}

function installment_text_after_price_render() {
    $value = get_option( 'installment_text_after_price', 'Sem juros' );
    echo '<input type="text" name="installment_text_after_price" value="' . $value . '" />';
}

function installment_min_amount_per_installment_render() {
    $value = get_option( 'installment_min_amount_per_installment', 0 );
    echo '<input type="number" min="0" step="1" name="installment_min_amount_per_installment" value="' . $value . '" />';
}

function pix_installment_options_page() {
    ?>
    <div>
        <h2>Configurações do Plugin</h2>
        <p>Plugin criado para adaptação do WooCommerce para a dinâmica e metodologia Brasileira de consumo.</p>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'pix_installment_settings' );
            do_settings_sections( 'pix_installment_settings' );
            submit_button();
            ?>
        </form>
        <br>
        <a href="https://nodle.com.br" target="_blank" class="button button-primary">Conheça a Nodle</a>
    </div>
    <?php
}
