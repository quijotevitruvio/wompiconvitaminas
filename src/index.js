console.log('%c🚀 WOMPI VANGUARD: Global & Insights [v5.5.1]...', 'color: #3b82f6; font-size: 16px; font-weight: bold;');

import WompiLabel from './components/WompiLabel';

const { registerPaymentMethod } = window.wc ? window.wc.wcBlocksRegistry : {};
const { getSetting } = window.wc ? window.wc.wcSettings : {};
const { decodeEntities } = window.wp ? window.wp.htmlEntities : { decodeEntities: (str) => str };

if (!registerPaymentMethod) {
    console.error('❌ WOMPI ERROR: registerPaymentMethod NOT found');
}

const settings = getSetting ? getSetting('wompi_vitaminas_pro_data', {}) : {};

const Label = ( props ) => {
	const components = props?.components || {};
	const { PaymentMethodLabel } = components;
	
	const titleElement = PaymentMethodLabel ? (
		<PaymentMethodLabel text={ settings.title || '💳 Wompi (Pasarela Segura)' } />
	) : (
		<span>{ settings.title || '💳 Wompi (Pasarela Segura)' }</span>
	);

	return <WompiLabel settings={settings} titleElement={titleElement} />;
};

const Content = () => {
    const description = settings.description || 'Paga de forma rápida y segura.';
    const mode = settings.mode || 'production';
    
    return (
        <div 
            className="lm-wompi-vanguard-container lm-wompi-premium-container" 
            style={{ 
                marginTop: '15px', 
                padding: '25px', 
                backgroundColor: '#ffffff',
                borderRadius: '20px', 
                border: '1px solid #f1f5f9',
                fontSize: '14.5px', 
                color: '#334155',
                lineHeight: '1.6',
                position: 'relative',
                boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.05)',
                overflow: 'hidden'
            }}
        >
            {mode === 'sandbox' && (
                <div style={{
                    position: 'absolute',
                    top: '0',
                    left: '0',
                    width: '100%',
                    backgroundColor: '#fef3c7',
                    color: '#92400e',
                    fontSize: '10px',
                    fontWeight: '800',
                    textAlign: 'center',
                    padding: '4px 0',
                    textTransform: 'uppercase',
                    letterSpacing: '0.05em',
                    borderBottom: '1px solid #fde68a',
                    zIndex: 10
                }}>
                    ⚠️ MODO PRUEBAS (SANDBOX) ACTIVO
                </div>
            )}

            <div style={{ paddingTop: (mode === 'sandbox' ? '20px' : '0') }}>
                <p style={{ margin: '0 0 20px 0', fontWeight: '500', color: '#1e293b', fontSize: '16px' }}>
                    {decodeEntities(description)}
                </p>

                {/* Banner de Contenido Directo */}
                {settings.banner_url && (
                    <div style={{ 
                        margin: '20px 0', 
                        padding: '12px', 
                        backgroundColor: '#f8fafc', 
                        borderRadius: '16px', 
                        border: '1px solid #f1f5f9',
                        textAlign: 'center',
                        boxShadow: 'inset 0 2px 4px rgba(0,0,0,0.02)'
                    }}>
                        <img 
                            src={settings.banner_url} 
                            alt="Wompi Banner" 
                            style={{ maxWidth: '100%', height: 'auto', borderRadius: '10px' }} 
                        />
                    </div>
                )}

                {/* Iconos de Marca Refinados */}
                <div style={{ 
                    display: 'flex', 
                    alignItems: 'center', 
                    gap: '15px', 
                    padding: '15px 0', 
                    borderTop: '1px solid #f8fafc',
                    marginBottom: '20px'
                }}>
                    <img src={settings.visa_logo || "https://static.wompi.co/assets/img/visa-color.svg"} style={{ height: '24px' }} alt="Visa" onError={(e) => e.target.style.display='none'} />
                    <img src={settings.mastercard_logo || "https://static.wompi.co/assets/img/mastercard-color.svg"} style={{ height: '24px' }} alt="Mastercard" onError={(e) => e.target.style.display='none'} />
                    <img src={settings.pse_logo || "https://static.wompi.co/assets/img/pse-color.svg"} style={{ height: '28px' }} alt="PSE" onError={(e) => e.target.style.display='none'} />
                    <img src={settings.nequi_logo || "https://static.wompi.co/assets/img/nequi-color.svg"} style={{ height: '24px' }} alt="Nequi" onError={(e) => e.target.style.display='none'} />
                </div>

                {/* Trust Badge con Pulso Animado (v5.2.0) */}
                <div style={{ 
                    display: 'flex', 
                    alignItems: 'center', 
                    gap: '12px', 
                    fontSize: '13px', 
                    color: '#059669',
                    backgroundColor: '#ecfdf5',
                    padding: '10px 15px',
                    borderRadius: '12px',
                    fontWeight: '600',
                    border: '1px solid #d1fae5'
                }}>
                    <div className="lm-wompi-pulse-dot"></div>
                    <span>Transacción Cifrada y Segura (Wompi Certified)</span>
                </div>
            </div>
        </div>
    );
};

try {
    registerPaymentMethod({
        name: 'wompi_vitaminas_pro',
        label: <Label />,
        content: <Content />,
        edit: <Content />,
        canMakePayment: () => true,
        ariaLabel: settings.title || 'Wompi con Vitaminas',
        supports: {
            features: settings.supports || ['products']
        }
    });
} catch ( error ) {
	console.error('❌ WOMPI ERROR:', error);
}
