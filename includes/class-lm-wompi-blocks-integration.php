<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase de Integración Enterprise para WooCommerce Blocks
 */
final class LM_Wompi_Blocks_Integration extends AbstractPaymentMethodType {

	protected $name = 'wompi_vitaminas_pro';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_wompi_vitaminas_pro_settings', [] );
	}

	public function is_active() {
		return true;
	}

	public function get_payment_method_script_handles() {
		return [ 'lm-wompi-blocks-integration' ];
	}

	public function get_payment_method_data() {
        $mode = $this->get_setting( 'mode', 'sandbox' );
        $pub_key = ( $mode === 'production' ) ? $this->get_setting( 'live_public_key', '' ) : $this->get_setting( 'test_public_key', '' );

		return [
			'title'           => $this->get_setting( 'title', '💳 Wompi (Pasarela Segura)' ),
			'description'     => $this->get_setting( 'description', 'Paga de forma segura con Tarjetas, PSE o Nequi.' ),
			'image'           => $this->get_setting( 'image_url', '' ),
			'public_key'      => $pub_key,
            'mode'            => $mode,
            'visa_logo'       => $this->get_setting( 'visa_logo', '' ),
            'mastercard_logo' => $this->get_setting( 'mastercard_logo', '' ),
            'pse_logo'        => $this->get_setting( 'pse_logo', '' ),
            'nequi_logo'      => $this->get_setting( 'nequi_logo', '' ),
            'banner_url'      => $this->get_setting( 'content_banner_url', '' ),
            'badgeText'       => $this->get_setting( 'badge_text', '' ),
            'badgeColor'      => $this->get_setting( 'badge_color', '#22c55e' ),
			'supports'        => [ 'products' ]
		];
	}

    /**
     * Obtiene y cachea los datos legales del comercio desde Wompi
     */
    private function get_merchant_legal_data($public_key, $mode) {
        if (empty($public_key)) return ['token' => '', 'url' => ''];

        $cache_key = 'lm_wompi_legal_' . md5($public_key);
        $cached = get_transient($cache_key);
        if ($cached) return $cached;

        $base_url = ($mode === 'sandbox') ? 'https://sandbox.wompi.co/v1' : 'https://production.wompi.co/v1';
        $response = wp_remote_get($base_url . '/merchants/' . $public_key);

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return [ 'token' => '', 'url' => '' ];
		}

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $acceptance = $body['data']['presigned_acceptance'] ?? null;

        if (!$acceptance || !isset($acceptance['acceptance_token'])) return ['token' => '', 'url' => ''];

        $data = [
            'token' => $acceptance['acceptance_token'],
            'url' => $acceptance['permalink']
        ];

        set_transient($cache_key, $data, DAY_IN_SECONDS);
        return $data;
    }
}
