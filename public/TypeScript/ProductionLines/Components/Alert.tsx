import React, {FC, useEffect, useState} from 'react';

type AlertItem = { id: number; type: 'success' | 'danger' | 'info' | 'warning'; message: string };

const Alert: FC = () => {
    const [alerts, setAlerts] = useState<AlertItem[]>([]);

    useEffect(() => {
        const handler = (e: Event) => {
            const detail = (e as CustomEvent).detail as { type?: string; message?: string } | undefined;
            if (!detail || !detail.message) return;
            const type = (detail.type as any) || 'info';
            const id = Date.now() + Math.floor(Math.random() * 1000);
            const item: AlertItem = { id, type: (['success','danger','info','warning'].includes(type) ? (type as any) : 'info'), message: detail.message };
            setAlerts(prev => [...prev, item]);

            // Auto remove after 4s
            setTimeout(() => {
                setAlerts(prev => prev.filter(a => a.id !== id));
            }, 4000);
        };

        window.addEventListener('pl-alert', handler as EventListener);
        return () => window.removeEventListener('pl-alert', handler as EventListener);
    }, []);

    const remove = (id: number) => setAlerts(prev => prev.filter(a => a.id !== id));

    if (alerts.length === 0) return null;

    return (
        <div style={{ position: 'fixed', top: 16, right: 16, zIndex: 2000 }}>
            {alerts.map(alert => (
                <div key={alert.id} className={`alert alert-${alert.type} alert-dismissible fade show`} role="alert" style={{ minWidth: 260, boxShadow: '0 2px 6px rgba(0,0,0,0.12)', marginBottom: 8 }}>
                    <div dangerouslySetInnerHTML={{ __html: alert.message }} />
                    <button type="button" className="btn-close" aria-label="Close" onClick={() => remove(alert.id)} />
                </div>
            ))}
        </div>
    );
}

export default Alert;
