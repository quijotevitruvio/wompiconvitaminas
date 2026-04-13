<?php
if (!defined('ABSPATH'))
    exit;

class WC_Gateway_Wompi_Vitaminas_Pro extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'wompi_vitaminas_pro';
        $this->has_fields = false;
        $this->method_title = '💳 Wompi con Vitaminas PRO';
        $this->method_description = __('Pasarela de pago Wompi premium con identidad global e inteligencia de datos (v5.4.0).', 'wompi-con-vitaminas');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', '💳 Wompi (Pasarela Segura)');
        $this->description = $this->get_option('description', 'Paga de forma segura con Tarjetas, PSE o Nequi.');
        $this->enabled = $this->get_option('enabled');

        // Hook estándar de guardado
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * Comprueba si la pasarela está disponible para su uso.
     * Nivel Enterprise: Desactiva el pago si faltan llaves críticas.
     */
    public function is_available()
    {
        if (!parent::is_available()) {
            return false;
        }

        // Blindaje de Moneda (Tier 1) - Wompi opera principalmente en COP
        if (get_woocommerce_currency() !== 'COP') {
            return false;
        }

        return !empty($this->get_option('live_public_key')) || !empty($this->get_option('test_public_key'));
    }

    public function process_admin_options()
    {
        $saved = parent::process_admin_options();

        // Anti-Cache: Purga LiteSpeed si está presente tras guardar
        if ($saved && class_exists('LiteSpeed_Cache_API')) {
            do_action('litespeed_purge_all');
        }

        return $saved;
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => 'Activar/Desactivar',
                'type' => 'checkbox',
                'label' => __('Activar Wompi con Vitaminas PRO', 'wompi-con-vitaminas'),
                'default' => 'yes'
            ],
            'ui_section' => [
                'title' => '🔥 Ajustes Visuales (Checkout Blocks)',
                'type' => 'title',
                'description' => 'Personaliza el look & feel en el nuevo Checkout de WooCommerce.'
            ],
            'title' => [
                'title' => 'Título en Checkout',
                'type' => 'text',
                'default' => '💳 Wompi (Gana Puntos Colombia)'
            ],
            'description' => [
                'title' => 'Descripción',
                'type' => 'textarea',
                'default' => 'Paga de forma segura con Tarjetas, PSE o Nequi.'
            ],
            'image_url' => [
                'title' => 'URL de Logo Principal',
                'type' => 'text',
                'description' => 'Logo que aparece al lado del título.'
            ],
            'visa_logo' => [
                'title' => 'Logo Visa',
                'type' => 'text',
                'description' => 'Pequeño (ej: 40x12px)'
            ],
            'mastercard_logo' => [
                'title' => 'Logo Mastercard',
                'type' => 'text',
                'description' => 'Pequeño (ej: 40x12px)'
            ],
            'pse_logo' => [
                'title' => 'Logo PSE',
                'type' => 'text',
                'description' => 'Pequeño (ej: 40x12px)'
            ],
            'nequi_logo' => [
                'title' => 'Logo Nequi',
                'type' => 'text',
                'description' => 'Pequeño (ej: 40x12px)'
            ],
            'content_banner_url' => [
                'title' => 'Banner de Contenido',
                'type' => 'text',
                'description' => 'Imagen central opcional (estilo QR BACS).'
            ],
            'badge_text' => [
                'title' => 'Texto del Badge',
                'type' => 'text',
                'default' => '⚡ Recomendado'
            ],
            'badge_color' => [
                'title' => 'Color del Badge',
                'type' => 'color',
                'default' => '#22c55e'
            ],
            'api_section' => [
                'title' => '🌐 Configuración API Wompi',
                'type' => 'title',
                'description' => 'Wompi funciona mediante el Checkout Widget. <br><br><strong>Webhook Moderno:</strong> Debes configurar la siguiente URL en tu panel de Wompi para asegurar la compatibilidad con bloques: <br><code>' . site_url('/wp-json/wc-wompi/v1/webhook') . '</code>'
            ],
            'mode' => [
                'title' => 'Modo de Operación',
                'type' => 'select',
                'options' => [
                    'sandbox' => 'Pruebas (Sandbox)',
                    'production' => 'Producción (Live)'
                ],
                'default' => 'sandbox'
            ],
            'test_public_key' => [
                'title' => 'Test Public Key',
                'type' => 'text',
                'sanitize_callback' => 'trim'
            ],
            'test_private_key' => [
                'title' => 'Test Private Key',
                'type' => 'password',
                'sanitize_callback' => 'trim'
            ],
            'test_integrity_key' => [
                'title' => 'Test Integrity Secret',
                'type' => 'password',
                'sanitize_callback' => 'trim'
            ],
            'test_event_key' => [
                'title' => 'Test Event Secret',
                'type' => 'password',
                'sanitize_callback' => 'trim'
            ],
            'live_public_key' => [
                'title' => 'Live Public Key',
                'type' => 'text',
                'sanitize_callback' => 'trim'
            ],
            'live_private_key' => [
                'title' => 'Live Private Key',
                'type' => 'password',
                'sanitize_callback' => 'trim'
            ],
            'live_integrity_key' => [
                'title' => 'Live Integrity Secret',
                'type' => 'password',
                'sanitize_callback' => 'trim'
            ],
            'live_event_key' => [
                'title' => 'Live Event Secret',
                'type' => 'password',
                'sanitize_callback' => 'trim'
            ],
            'tax_section' => [
                'title' => '💰 Impuestos y Contabilidad (Colombia)',
                'type' => 'title',
                'description' => 'Desglose automático de IVA/Ico para reportes en Wompi.'
            ],
            'enable_taxes' => [
                'title' => 'Activar Desglose de IVA',
                'type' => 'checkbox',
                'label' => 'Enviar detalles de impuestos a Wompi',
                'default' => 'no'
            ],
            'tax_rate' => [
                'title' => 'Tasa de IVA (%)',
                'type' => 'number',
                'description' => 'Porcentaje de IVA por defecto (ej: 19). Si WooCommerce tiene impuestos configurados, se intentará detectar automáticamente.',
                'default' => '19',
                'custom_attributes' => ['step' => '0.01']
            ],
            'legal_section' => [
                'title' => '⚖️ Aceptación Legal',
                'type' => 'title',
                'description' => 'Gestión obligatoria de Términos y Condiciones.'
            ],
            'acceptance_token' => [
                'title' => 'Modo de Aceptación',
                'type' => 'select',
                'options' => [
                    'auto' => 'Automático (Recomendado)',
                    'manual' => 'Manual (Solo avanzados)'
                ],
                'default' => 'auto'
            ],
        ];
    }

    public function admin_options()
    {
        wp_enqueue_media();
        ?>
        <h2><?php echo esc_html($this->method_title); ?></h2>
        <div class="lm-wompi-settings-info"
            style="background: #ffffff; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                <span
                    style="background: #2563eb; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase;">Global &
                    Insights v5.4.0</span>
                <h3 style="margin: 0; color: #1e293b;"><?php echo __('🚀 Wompi Vitaminas: Edición Analista', 'wompi-con-vitaminas'); ?></h3>
            </div>
            <p style="color: #64748b; font-size: 13px; margin: 0;"><?php echo __('Has migrado a una identidad blindada. Los webhooks ahora cuentan con validación de firma criptográfica SHA256. Por favor, asegúrate de configurar el "Secreto de Eventos" para máxima seguridad.', 'wompi-con-vitaminas'); ?></p>
        </div>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <script>
            jQuery(document).ready(function ($) {
                // Nombres de los campos que deben tener selector de imagen
                var fields = [
                    'woocommerce_wompi_vitaminas_pro_image_url',
                    'woocommerce_wompi_vitaminas_pro_visa_logo',
                    'woocommerce_wompi_vitaminas_pro_mastercard_logo',
                    'woocommerce_wompi_vitaminas_pro_pse_logo',
                    'woocommerce_wompi_vitaminas_pro_nequi_logo',
                    'woocommerce_wompi_vitaminas_pro_content_banner_url'
                ];

                fields.forEach(function (fieldName) {
                    var $input = $('input[name="' + fieldName + '"]');
                    if ($input.length > 0) {
                        var $btn = $('<a href="#" class="button button-secondary" style="margin-left: 5px; margin-top: 2px;">📷 Seleccionar</a>');
                        $input.after($btn);

                        $btn.on('click', function (e) {
                            e.preventDefault();
                            var frame = wp.media({
                                title: 'Seleccionar Logo',
                                button: { text: 'Usar esta imagen' },
                                multiple: false
                            });
                            frame.on('select', function () {
                                var attachment = frame.state().get('selection').first().toJSON();
                                $input.val(attachment.url).trigger('change');
                            });
                            frame.open();
                        });
                    }
                });
            });
        </script>
        <?php
    }

    public function process_payment($order_id)
    {
        if (!function_exists('wc_get_order')) {
            return [
                'result' => 'failure',
                'messages' => 'WooCommerce non est cargado.',
            ];
        }
        $order = wc_get_order($order_id);
        $amount_in_cents = round($order->get_total() * 100);
        $currency = $order->get_currency();

        $mode = $this->get_option('mode', 'sandbox');

        if ($mode === 'sandbox') {
            $public_key = $this->get_option('test_public_key');
            $integrity_secret = $this->get_option('test_integrity_key');
        } else {
            $public_key = $this->get_option('live_public_key');
            $integrity_secret = $this->get_option('live_integrity_key');
        }

        // Obtener Acceptance Token dinámico para cumplimiento v1.2.0
        $acceptance_token = '';
        if ($this->get_option('acceptance_token', 'auto') === 'auto' && class_exists('LM_Wompi_Blocks_UI')) {
            $legal = $this->get_merchant_legal_data_helper($public_key, $mode);
            $acceptance_token = $legal['token'] ?? '';
        }

        // Desglose de Impuestos (IVA)
        $tax_vat = 0;
        if ($this->get_option('enable_taxes') === 'yes') {
            $tax_vat = round($order->get_total_tax() * 100);
        }

        $raw_signature = $order_id . $amount_in_cents . $currency . $integrity_secret;
        $signature = hash('sha256', $raw_signature);

        $wompi_url = "https://checkout.wompi.co/p/";
        $wompi_params = [
            'public-key' => $public_key,
            'currency' => $currency,
            'amount-in-cents' => $amount_in_cents,
            'reference' => $order_id,
            'signature:integrity' => $signature,
            'customer-data:email' => $order->get_billing_email(),
            'customer-data:full-name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'customer-data:phone-number' => $order->get_billing_phone(),
            'redirect-url' => $this->get_return_url($order)
        ];

        // Añadir parámetros Enterprise si están disponibles
        if (!empty($acceptance_token)) {
            $wompi_params['acceptance-token'] = $acceptance_token;
        }

        if ($tax_vat > 0) {
            $wompi_params['tax-in-cents:vat'] = $tax_vat;
        }

        return [
            'result' => 'success',
            'redirect' => add_query_arg($wompi_params, $wompi_url)
        ];
    }

    /**
     * Helper para obtener datos legales (compartido con Blocks)
     */
    private function get_merchant_legal_data_helper($public_key, $mode)
    {
        $cache_key = 'lm_wompi_legal_' . md5($public_key);
        $cached = get_transient($cache_key);
        if ($cached)
            return $cached;

        $base_url = ($mode === 'sandbox') ? 'https://sandbox.wompi.co/v1' : 'https://production.wompi.co/v1';
        $response = wp_remote_get($base_url . '/merchants/' . $public_key);
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return [];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $acceptance = $body['data']['presigned_acceptance'] ?? null;
        if (!$acceptance)
            return [];

        $data = ['token' => $acceptance['acceptance_token'], 'url' => $acceptance['permalink']];
        set_transient($cache_key, $data, DAY_IN_SECONDS);
        return $data;
    }
}
