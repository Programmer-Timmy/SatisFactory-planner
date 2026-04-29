import {FC, ReactNode, useEffect, useRef} from "react";

type Size = 'sm' | 'md' | 'lg' | 'xl';

interface ModalProps {
    children: ReactNode;
    isOpen: boolean;
    onClose: () => void;
    title: string;
    className?: string;
    size?: Size; // optional size
    fullscreen?: boolean; // explicit fullscreen flag
}

const modal = ({
                   children,
                   isOpen,
                   onClose,
                   title,
                   className = '',
                   size,
                   fullscreen = false,
               }: ModalProps) => {
    // Disable page scrolling while modal is open. Restore previous overflow on close/unmount.
    const prevOverflowRef = useRef<string | null>(null);
    useEffect(() => {
        try {
            if (isOpen) {
                prevOverflowRef.current = document.body.style.overflow || '';
                document.body.style.overflow = 'hidden';
            }
        } catch (e) {
            // ignore in non-browser environments
        }
        return () => {
            try {
                if (prevOverflowRef.current !== null) {
                    document.body.style.overflow = prevOverflowRef.current;
                    prevOverflowRef.current = null;
                }
            } catch (e) {
                // ignore
            }
        };
    }, [isOpen]);

    // Add a ref and mount class to trigger CSS fade-in/slide animation
    const modalRef = useRef<HTMLDivElement | null>(null);
    useEffect(() => {
        const el = modalRef.current;
        if (!el) return;
        // Remove class first then add on next frame so transition runs
        el.classList.remove('modal-mounted');
        const raf = requestAnimationFrame(() => el.classList.add('modal-mounted'));
        return () => cancelAnimationFrame(raf);
    }, [isOpen]);

    if (!isOpen) return null;

    // Only two states: fullscreen or not. No runtime toggle inside the component.
    const fullscreenActive = !!fullscreen;
    const dialogClass = `modal-dialog modal-dialog-scrollable${fullscreenActive ? ' modal-fullscreen' : (size ? ` modal-${size}` : '')}`;

    return (
        <>
            {/* Inline styles for a small fade-in animation scoped to this component */}
            <style>{`
                .custom-modal { opacity: 0; transform: translateY(8px); transition: opacity 220ms ease, transform 220ms ease; }
                .custom-modal.modal-mounted { opacity: 1; transform: translateY(0); }
                /* Backdrop slight fade to match modal */
                .modal-backdrop.fade.show { opacity: 0.45; transition: opacity 220ms ease; }
            `}</style>

            <div className="modal-backdrop fade show"/>
            <div ref={modalRef} className={`modal fade show d-block custom-modal ${className}`} tabIndex={-1} onClick={onClose}>
                <div className={dialogClass} onClick={(e) => e.stopPropagation()}>
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title">{title}</h5>
                            <button type="button" className="btn-close" onClick={onClose}></button>
                        </div>
                        {children}
                    </div>
                </div>
            </div>
        </>
    );
}

const body = ({children, className}: { children: ReactNode, className?: string }) => {
    return (
        <div className={`modal-body ${className || ''}`}>
            {children}
        </div>
    );
}

const footer = ({children, className}: { children: ReactNode, className?: string }) => {
    return (
        <div className={`modal-footer ${className || ''}`}>
            {children}
        </div>
    )
}

modal.Body = body;
modal.Footer = footer;

export default modal;