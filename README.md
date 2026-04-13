# 💳 Wompi con Vitaminas (Global & Insights Edition)

![Version](https://img.shields.io/badge/version-5.4.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.x-777bb4.svg)
![React](https://img.shields.io/badge/React-18-61dafb.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-Blocks-blue)
![License](https://img.shields.io/badge/License-MIT-green.svg)

**Wompi con Vitaminas** es el estándar de oro para integrar la pasarela de pago **Wompi (Bancolombia)** en WooCommerce Checkout Blocks. Diseñado para ofrecer una experiencia premium, blindada y orientada a datos.

---

## 💎 Características Vanguard v5.4.0

### 🛡️ Enterprise Shield (Seguridad Blindada)
- **Validación SHA256**: Autenticación criptográfica de webhooks para prevenir fraude y suplantación.
- **AJAX Hardened**: Blindaje de puntos finales para asegurar que solo usuarios autorizados operen sobre los pedidos.
- **Sanitización Total**: Protección nativa contra inyecciones XSS y ataques de bypass.

### 📊 Payment Insights (Inteligencia de Datos)
- **Trazabilidad de Métodos**: Detecta automáticamente si el cliente usó **PSE, Tarjeta (Visa/Mastercard), Nequi o Corresponsal**.
- **Metadatos de Pedido**: Almacenamiento granular de detalles de transacción para facilitar la conciliación bancaria.
- **Notas de Pedido Dinámicas**: Informes automatizados directamente en el dashboard de WooCommerce.

### 🌍 Global & i18n Ready
- **Traducciones (i18n)**: 100% preparado para traducción con funciones nativas de WordPress (`__()`).
- **Blindaje de Moneda**: Detección automática de moneda **COP** para evitar discrepancias operativas.

### 🎨 Experiencia Visual Premium
- **Glassmorphism UI**: Diseño moderno y elegante que combina perfectamente con cualquier tema premium.
- **Trust Pulse**: Animaciones de estado de seguridad en tiempo real para aumentar la confianza del comprador.

---

## 🚀 Instalación Rápida

1. Descarga el repositorio y súbelo a `/wp-content/plugins/lm-wompi-blocks-ui`.
2. Activa el plugin desde el panel administrativo.
3. Configura tus llaves (Pública, Privada e Integridad) en **WooCommerce > Ajustes > Pagos > Wompi con Vitaminas**.
4. **IMPORTANTE**: Registra la URL del Webhook en tu portal Wompi:
   `https://tu-dominio.com/wp-json/wc-wompi/v1/webhook`

---

## 🛠 Stack Tecnológico

- **Núcleo**: PHP 8.1+ para máxima compatibilidad con firmas HMAC-SHA256.
- **Frontend**: React 18 & @wordpress/scripts para una integración nativa con Gutenberg.
- **Estilos**: Vanilla CSS con micro-interacciones de alto rendimiento.

---

## 👤 Autor

**Andrés Valencia Tobón**
- GitHub: [@Rocka](https://github.com/Rocka)

---

> [!NOTE]
> *Este proyecto fue optimizado y blindado por **Antigravity AI** para el ecosistema Vanguard Technical Sovereignty.*
