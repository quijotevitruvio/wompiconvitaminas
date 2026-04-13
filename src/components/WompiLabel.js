/**
 * Componente Visual Premium para la Etiqueta del Método de Pago Wompi
 */
const WompiLabel = ({ settings, titleElement }) => {
    return (
        <div 
            className="lm-wompi-premium-label" 
            style={{ 
                display: 'flex', 
                alignItems: 'center', 
                gap: '8px', 
                width: '100%', 
                flexWrap: 'wrap',
                padding: '2px 0'
            }}
        >
            {/* Logo o Icono Principal */}
            {settings.image && settings.image.trim() !== '' ? (
                <div style={{ 
                    background: '#fff', 
                    padding: '4px 8px', 
                    borderRadius: '8px', 
                    boxShadow: '0 2px 4px rgba(0,0,0,0.05)',
                    display: 'flex',
                    alignItems: 'center'
                }}>
                    <img 
                        src={settings.image.trim()} 
                        alt="Wompi" 
                        style={{ height: '22px', objectFit: 'contain' }} 
                    />
                </div>
            ) : (
                <div style={{ 
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    width: '32px',
                    height: '32px',
                    backgroundColor: '#000',
                    borderRadius: '8px',
                    color: '#fff',
                    fontWeight: '900',
                    fontSize: '10px'
                }}>
                    W
                </div>
            )}
            
            <div style={{ display: 'flex', flexDirection: 'column' }}>
                <span className="lm-wompi-custom-title" style={{ fontWeight: '700', color: '#111', fontSize: '15.5px' }}>
                    {titleElement}
                </span>
            </div>

            {settings.badgeText && settings.badgeText.trim() !== '' && (
                <span 
                    className="lm-wompi-badge" 
                    style={{
                        backgroundColor: settings.badgeColor || '#22c55e',
                        color: '#fff',
                        padding: '3px 10px',
                        borderRadius: '20px',
                        fontSize: '11px',
                        fontWeight: '600',
                        textTransform: 'uppercase',
                        letterSpacing: '0.5px',
                        boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
                        whiteSpace: 'nowrap',
                        marginLeft: 'auto'
                    }}
                >
                    {settings.badgeText.trim()}
                </span>
            )}
        </div>
    );
};

export default WompiLabel;
