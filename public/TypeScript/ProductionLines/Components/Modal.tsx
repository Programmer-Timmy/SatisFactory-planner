import {FC, ReactNode, useEffect, useRef, useState} from "react";

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

    // Manage mounting state so we can animate on close instead of unmounting instantly
    const modalRef = useRef<HTMLDivElement | null>(null);
    const [rendered, setRendered] = useState<boolean>(isOpen);
    const hideTimeoutRef = useRef<number | null>(null);

    useEffect(() => {
        const el = modalRef.current;
        // If opening: ensure rendered and add mounted class on next frame
        if (isOpen) {
            // clear any pending close timer
            if (hideTimeoutRef.current) {
                window.clearTimeout(hideTimeoutRef.current);
                hideTimeoutRef.current = null;
            }
            if (!rendered) setRendered(true);
            if (!el) return;

            // Start from 'opening' state (above) then animate to mounted (center)
            el.classList.remove('modal-mounted', 'modal-closing');
            el.classList.add('modal-opening');
            const raf = requestAnimationFrame(() => {
                el.classList.remove('modal-opening');
                el.classList.add('modal-mounted');
            });
            return () => cancelAnimationFrame(raf);
        }

        // If closing: remove mounted class to play hide animation (which goes down), then unmount after duration
        if (!isOpen && rendered) {
            if (el) {
                el.classList.remove('modal-mounted', 'modal-opening');
                el.classList.add('modal-closing');
            }
            // match the CSS transition duration (360ms)
            hideTimeoutRef.current = window.setTimeout(() => {
                setRendered(false);
                hideTimeoutRef.current = null;
            }, 380);
        }

        return () => {
            if (hideTimeoutRef.current) {
                window.clearTimeout(hideTimeoutRef.current);
                hideTimeoutRef.current = null;
            }
        };
    }, [isOpen, rendered]);

    // Only two states: fullscreen or not. No runtime toggle inside the component.
    const fullscreenActive = !!fullscreen;
    const dialogClass = `modal-dialog modal-dialog-scrollable${fullscreenActive ? ' modal-fullscreen' : (size ? ` modal-${size}` : '')}`;

    if (!rendered) return null;

    return (
        <>
            {/* Inline styles for a small fade-in animation scoped to this component */}
            <style>{`
                .custom-modal { opacity: 0; transform: translateY(-8px); transition: opacity 360ms ease, transform 360ms ease; }
                /* Opening starts from slightly above */
                .custom-modal.modal-opening { opacity: 0; transform: translateY(-8px); }
                /* Mounted (visible) */
                .custom-modal.modal-mounted { opacity: 1; transform: translateY(0); }
                /* Closing class (keeps same downward target as base) */
                .custom-modal.modal-closing { opacity: 0; transform: translateY(8px); }

                /* Backdrop slight fade to match modal */
                .modal-backdrop.fade { opacity: 0; transition: opacity 360ms ease; }
                .modal-backdrop.fade.show { opacity: 0.45; }
            `}</style>

            <div className={`modal-backdrop fade ${isOpen ? 'show' : ''}`}/>
            <div ref={modalRef} className={`modal fade d-block custom-modal ${className} ${isOpen ? 'show' : ''}`} tabIndex={-1} onClick={onClose}>
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