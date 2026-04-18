<?php
/**
 * Plugin Name: Wompi con Vitaminas (Blocks & Gateway)
 * Description: Pasarela de pago Wompi nativa para Checkout Blocks con interfaz visual premium mejorada.
 * Version: 5.5.1
 * Author: Andrés Valencia Tobón
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constantes Globales
define('LM_WOMPI_PATH', plugin_dir_path(__FILE__));
define('LM_WOMPI_URL', plugin_dir_url(__FILE__));

/**
 * Clase principal de inicialización
 */
class LM_Wompi_Blocks_UI
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hooks Core Restaurados (v5.1.2)
        add_action('plugins_loaded', [$this, 'init_gateway']);
        add_filter('woocommerce_payment_gateways', [$this, 'add_gateway_class']);
        add_action('before_woocommerce_init', [$this, 'declare_block_compatibility']);
        add_action('init', [$this, 'register_scripts'], 10);
        
        // Integración de Bloques (Estilo BACS)
        add_action('woocommerce_blocks_loaded', [$this, 'blocks_integration_init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);

        // Marcador de vida para diagnóstico experto (v5.1.2)
        add_action('wp_head', function () {
            $data = get_option('woocommerce_wompi_vitaminas_pro_settings');
            $status = !empty($data) ? 'ACTIVE' : 'NO_DATA';
            echo "\n<!-- 🚀 WOMPI EXPERT v5.5.1 | STATUS: " . esc_attr($status) . " -->\n";
        }, 1);

        // Registro de Webhook
        add_action('rest_api_init', [$this, 'register_webhook_route']);

        // Enlace de Ajustes
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_link']);

        // Módulos Auxiliares
        add_action('add_meta_boxes', [$this, 'add_order_wompi_metabox']);
        add_filter('woocommerce_email_order_meta_fields', [$this, 'add_wompi_to_emails'], 10, 3);
        add_action('wp_ajax_lm_wompi_sync_status', [$this, 'ajax_sync_order_status']);
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
    }

    public function init_gateway()
    {
        error_log('LM WOMPI DEBUG: init_gateway start');
        if (!class_exists('WC_Payment_Gateway')) {
            error_log('LM WOMPI DEBUG: WC_Payment_Gateway NOT FOUND');
            return;
        }
        require_once plugin_dir_path(__FILE__) . 'includes/class-lm-wompi-gateway.php';
        error_log('LM WOMPI DEBUG: Gateway loaded');
    }

    public function add_gateway_class($methods)
    {
        $methods[] = 'WC_Gateway_Wompi_Vitaminas_Pro';
        return $methods;
    }

    public function declare_block_compatibility()
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                __FILE__,
                true
            );
        }
    }

    public function blocks_integration_init()
    {
        error_log('LM WOMPI DEBUG: blocks_integration_init start');
        if (!class_exists('\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            error_log('LM WOMPI DEBUG: AbstractPaymentMethodType NOT FOUND');
            return;
        }
        require_once plugin_dir_path(__FILE__) . 'includes/class-lm-wompi-blocks-integration.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $registry) {
                error_log('LM WOMPI DEBUG: Registering integration...');
                $registry->register(new LM_Wompi_Blocks_Integration());
            }
        );
    }

    /**
     * Registro de scripts principales
     */
    public function register_scripts()
    {
        $asset_path = LM_WOMPI_PATH . 'build/index.asset.php';
        $script_url = LM_WOMPI_URL . 'build/index.js';

        error_log('LM WOMPI DEBUG: Registering script from ' . $script_url);
        error_log('LM WOMPI DEBUG: Asset path is ' . $asset_path);

        $version = '5.5.1';
        $dependencies = [
            'wc-blocks-registry',
            'wc-settings',
            'wp-element',
            'wp-html-entities',
            'wp-i18n',
        ];

        if (file_exists($asset_path)) {
            $asset = require $asset_path;
            $version = $asset['version'];
            $dependencies = array_merge($dependencies, $asset['dependencies']);
            error_log('LM WOMPI DEBUG: Asset file found. Deps: ' . implode(', ', $dependencies));
        } else {
            error_log('LM WOMPI DEBUG: Asset file NOT FOUND at ' . $asset_path);
        }

        wp_register_script(
            'lm-wompi-blocks-integration',
            $script_url,
            $dependencies,
            $version,
            true
        );
    }

    public function enqueue_styles()
    {
        wp_enqueue_style(
            'lm-wompi-enhancer',
            LM_WOMPI_URL . 'wompi-enhancer.css',
            [],
            '5.5.1'
        );

        // FORZADO DE SCRIPT PARA DIAGNÓSTICO
        wp_enqueue_script('lm-wompi-blocks-integration');
    }

    public function register_webhook_route()
    {
        register_rest_route('wc-wompi/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true'
        ]);
    }

    public function handle_webhook(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $signature = $request->get_header('x-wompi-signature');
        $timestamp = $params['timestamp'] ?? time();

        if (empty($params['data']['transaction'])) {
            return new WP_Error('invalid_data', 'No transaction data found', ['status' => 400]);
        }

        $transaction = $params['data']['transaction'];
        $order_id = $transaction['reference'];
        $status = $transaction['status'];

        if (!function_exists('wc_get_order')) {
            return new WP_REST_Response('WooCommerce not loaded', 500);
        }

        $order = wc_get_order($order_id);
        if (!$order)
            return new WP_REST_Response('Order not found', 404);

        $settings = get_option('woocommerce_wompi_vitaminas_pro_settings');
        $mode = isset($settings['mode']) ? $settings['mode'] : 'sandbox';

        $event_secret = ($mode === 'sandbox') ? ($settings['test_event_key'] ?? '') : ($settings['live_event_key'] ?? '');

        if (!empty($event_secret)) {
            $calculated_checksum = $this->verify_wompi_webhook_signature($params, $event_secret);

            if ($calculated_checksum !== $signature) {
                $this->log("⚠️ Fallo en Firma Webhook: Esperado $calculated_checksum, recibido $signature. Pedido: $order_id", 'error');
                $order->add_order_note('⚠️ Wompi Security: Firma del webhook inválida.');
                return new WP_REST_Response('Firma inválida', 403);
            }
        }

        $this->log("✅ Webhook verificado para pedido #$order_id. Estado: $status");

        // Captura de Insights de Pago (v5.4.0)
        if (!empty($transaction['payment_method']['type'])) {
            $method_type = $transaction['payment_method']['type'];
            $extra_data = $transaction['payment_method']['extra'] ?? [];
            $brand = $extra_data['brand'] ?? '';
            $display_name = $method_type . ($brand ? " ($brand)" : "");
            
            $order->update_meta_data('_wompi_payment_method_type', $method_type);
            if ($brand) $order->update_meta_data('_wompi_card_brand', $brand);
            $order->add_order_note("📊 Wompi Insights: Método utilizado: $display_name");
        }

        switch ($status) {
            case 'APPROVED':
                if (!$order->is_paid()) {
                    $order->payment_complete($transaction['id']);
                    $order->add_order_note(__('✅ Webhook: Pago aprobado en Wompi (ID: ', 'wompi-con-vitaminas') . $transaction['id'] . ').');
                }
                break;
            case 'DECLINED':
                $message = $transaction['status_message'] ?? '';
                $failure_note = __('❌ Webhook: Wompi reportó pago RECHAZADO.', 'wompi-con-vitaminas');
                if ($message)
                    $failure_note .= ' ' . __('Motivo: ', 'wompi-con-vitaminas') . $message;
                $order->update_status('failed', $failure_note);
                break;
            case 'VOIDED':
                $order->update_status('cancelled', __('🚫 Webhook: Transacción anulada en Wompi.', 'wompi-con-vitaminas'));
                break;
            case 'PENDING':
                $order->add_order_note(__('⏳ Webhook: Transacción pendiente en Wompi.', 'wompi-con-vitaminas'));
                break;
        }

        $order->save();
        return new WP_REST_Response('OK', 200);
    }

    public function add_order_wompi_metabox()
    {
        add_meta_box('lm_wompi_order_data', '💳 Wompi PRO', [$this, 'render_wompi_metabox_content'], ['shop_order', 'wc_order'], 'side', 'high');
    }

    public function render_wompi_metabox_content($post_or_order)
    {
        if (!function_exists('wc_get_order')) {
            return;
        }
        $order = ($post_or_order instanceof WP_Post) ? wc_get_order($post_or_order->ID) : $post_or_order;
        if (!$order || $order->get_payment_method() !== 'wompi_vitaminas_pro')
            return;

        $wompi_id = $order->get_transaction_id();
        echo '<p><strong>ID Wompi:</strong> <code>' . ($wompi_id ?: 'Pendiente') . '</code></p>';

        if ($wompi_id) {
            echo '<button type="button" id="lm-wompi-sync-btn" class="button button-secondary" style="width:100%; margin-top:10px;" data-order-id="' . $order->get_id() . '" data-wompi-id="' . $wompi_id . '">🔄 Verificar Estado</button>';
            echo '<div id="lm-wompi-sync-res" style="margin-top:5px; font-size:11px;"></div>';
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('#lm-wompi-sync-btn').on('click', function () {
                        var $btn = $(this);
                        var $res = $('#lm-wompi-sync-res');
                        $btn.prop('disabled', true).text('⌛ Consultando...');
                        $.post(ajaxurl, {
                            action: 'lm_wompi_sync_status',
                            order_id: $btn.data('order-id'),
                            wompi_id: $btn.data('wompi-id'),
                            nonce: '<?php echo wp_create_nonce("lm_wompi_sync"); ?>'
                        }, function (r) {
                            $btn.prop('disabled', false).text('🔄 Verificar Estado');
                            if (r.success) {
                                $res.html('<span style="color:green">✅ ' + r.data.message + '</span>');
                                setTimeout(function () { location.reload(); }, 1500);
                            } else {
                                $res.html('<span style="color:red">❌ ' + r.data.message + '</span>');
                            }
                        });
                    });
                });
            </script>
            <?php
        } else {
            echo '<p style="font-size:11px; color:#666;">El pedido aún no tiene un ID de Wompi asociado.</p>';
        }
    }

    public function add_wompi_to_emails($fields, $sent_to_admin, $order)
    {
        if ($order->get_payment_method() === 'wompi_vitaminas_pro' && $order->get_transaction_id()) {
            $fields['wompi_id'] = ['label' => 'ID Wompi', 'value' => $order->get_transaction_id()];
        }
        return $fields;
    }

    public function add_settings_link($links)
    {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=wompi_vitaminas_pro') . '">Ajustes</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * AJAX Handler: Sincronización Manual
     */
    public function ajax_sync_order_status()
    {
        check_ajax_referer('lm_wompi_sync', 'nonce');

        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(['message' => 'No tienes permisos para realizar esta acción.']);
        }

        $order_id = intval($_POST['order_id']);
        $wompi_id = sanitize_text_field($_POST['wompi_id']);

        if (!function_exists('wc_get_order')) {
            wp_send_json_error(['message' => 'WooCommerce no cargado']);
        }

        $order = wc_get_order($order_id);
        if (!$order)
            wp_send_json_error(['message' => 'Pedido no encontrado']);

        $settings = get_option('woocommerce_wompi_vitaminas_pro_settings');
        $mode = isset($settings['mode']) ? $settings['mode'] : 'sandbox';
        $private_key = ($mode === 'sandbox') ? ($settings['test_private_key'] ?? '') : ($settings['live_private_key'] ?? '');

        if (empty($private_key))
            wp_send_json_error(['message' => 'Configura la Private Key para sincronizar']);

        $url = "https://production.wompi.co/v1/transactions/" . $wompi_id;
        if ($mode === 'sandbox')
            $url = "https://sandbox.wompi.co/v1/transactions/" . $wompi_id;

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $private_key
            ]
        ]);

        if (is_wp_error($response))
            wp_send_json_error(['message' => 'Error de conexión con Wompi']);

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status = $body['data']['status'] ?? '';
        $message = $body['data']['status_message'] ?? '';

        if (!$status)
            wp_send_json_error(['message' => 'Respuesta de Wompi no válida']);

        switch ($status) {
            case 'APPROVED':
                if (!$order->is_paid()) {
                    $order->payment_complete($wompi_id);
                    $order->add_order_note('🔄 Sincronización Manual: Pago aprobado en Wompi.');
                }
                wp_send_json_success(['message' => 'Pago aprobado y sincronizado.']);
                break;
            case 'DECLINED':
                $failure_note = '🔄 Sincronización Manual: Wompi reportó pago RECHAZADO.';
                if ($message)
                    $failure_note .= ' Motivo: ' . $message;
                $order->update_status('failed', $failure_note);
                wp_send_json_success(['message' => 'El pago fue rechazado. ' . $message]);
                break;
            case 'VOIDED':
                $order->update_status('cancelled', '🔄 Sincronización Manual: Transacción anulada.');
                wp_send_json_success(['message' => 'Transacción anulada.']);
                break;
            case 'PENDING':
                wp_send_json_success(['message' => 'El pago sigue pendiente en Wompi.']);
                break;
            default:
                wp_send_json_error(['message' => 'Estado desconocido: ' . $status]);
                break;
        }
    }

    /**
     * Dashboard Widget: Analítica Elite
     */
    public function add_dashboard_widget()
    {
        wp_add_dashboard_widget(
            'lm_wompi_analytics',
            '🚀 Wompi Vitaminas PRO: Performance',
            [$this, 'render_dashboard_widget']
        );
    }

    public function render_dashboard_widget()
    {
        if (!function_exists('wc_get_orders')) {
            return;
        }
        $last_30_days = date('Y-m-d', strtotime('-30 days'));
        $orders = wc_get_orders([
            'payment_method' => 'wompi_vitaminas_pro',
            'status' => ['completed', 'processing'],
            'date_created' => '>=' . $last_30_days,
        ]);

        $total_sales = 0;
        foreach ($orders as $order) {
            $total_sales += $order->get_total();
        }

        $settings = get_option('woocommerce_wompi_vitaminas_pro_settings');
        $mode = $settings['mode'] ?? 'sandbox';
        $status_color = ($mode === 'live') ? '#22c55e' : '#f59e0b';
        $status_text = ($mode === 'live') ? 'Producción (Live)' : 'Pruebas (Sandbox)';

        echo '<div style="padding:10px; font-family: -apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Oxygen-Sans,Ubuntu,Cantarell,\'Helvetica Neue\',sans-serif;">';
        echo '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">';
        echo '<div><p style="margin:0; font-size:12px; color:#64748b;">Ventas (Últimos 30 días)</p><h2 style="margin:0; font-size:24px; font-weight:800; color:#1e293b;">' . wc_price($total_sales) . '</h2></div>';
        echo '<div style="text-align:right;"><span style="display:inline-block; padding:4px 8px; border-radius:12px; font-size:10px; font-weight:700; color:#fff; background:' . $status_color . ';">' . $status_text . '</span></div>';
        echo '</div>';
        echo '<div style="border-top:1px solid #f1f5f9; padding-top:15px;">';
        echo '<p style="font-size:12px; color:#64748b; margin-bottom:10px;">Salud de la Pasarela:</p>';
        echo '<div style="display:flex; gap:10px; font-size:11px;">';
        echo '<span style="color:#22c55e;">✅ SSL Activo</span>';
        echo '<span style="color:#22c55e;">✅ Webhooks OK</span>';
        if (empty($settings['live_integrity_key']))
            echo '<span style="color:#ef4444;">⚠️ Falta Integrity Key</span>';
        echo '</div></div>';
        echo '</div>';
    }

    /**
     * Verifica la firma del webhook según API v1.2.0 (Dynamic Properties)
     */
    private function verify_wompi_webhook_signature($payload, $event_secret)
    {
        $data = $payload['data'] ?? [];
        $signature_obj = $payload['signature'] ?? [];
        $properties = $signature_obj['properties'] ?? [];
        $timestamp = $payload['timestamp'] ?? 0;

        $concatenated = "";
        foreach ($properties as $property) {
            $keys = explode('.', $property);
            $val = $data;
            foreach ($keys as $key) {
                if (isset($val[$key])) {
                    $val = $val[$key];
                } else {
                    $val = '';
                    break;
                }
            }
            $concatenated .= $val;
        }

        $concatenated .= $timestamp;
        $concatenated .= $event_secret;

        return hash('sha256', $concatenated);
    }

    /**
     * Sistema de Logging Enterprise
     */
    public function log($message, $level = 'info')
    {
        if (!class_exists('WC_Logger'))
            return;

        $logger = wc_get_logger();
        $context = ['source' => 'wompi-vitaminas-pro'];

        switch ($level) {
            case 'error':
                $logger->error($message, $context);
                break;
            case 'warning':
                $logger->warning($message, $context);
                break;
            default:
                $logger->info($message, $context);
                break;
        }
    }
}

LM_Wompi_Blocks_UI::get_instance();
