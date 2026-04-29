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

    if (!isOpen) return null;

    // Only two states: fullscreen or not. No runtime toggle inside the component.
    const fullscreenActive = !!fullscreen;
    const dialogClass = `modal-dialog modal-dialog-scrollable${fullscreenActive ? ' modal-fullscreen' : (size ? ` modal-${size}` : '')}`;

    return (
        <>
            <div className="modal-backdrop fade show"/>
            <div className={`modal fade show d-block ${className}`} tabIndex={-1} onClick={onClose}>
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