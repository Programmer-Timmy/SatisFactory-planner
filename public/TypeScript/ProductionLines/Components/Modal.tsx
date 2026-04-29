import {FC, ReactNode} from "react";

type Size = 'sm' | 'md' | 'lg' | 'xl';

interface ModalProps {
    children: ReactNode;
    isOpen: boolean;
    onClose: () => void;
    title: string;
    className?: string;
}

const modal = ({
                   children,
                   isOpen,
                   onClose,
                   title,
                   className = '',
               }: ModalProps) => {
    if (!isOpen) return null;

    return (
        <>
            <div className="modal-backdrop fade show"/>
            <div className={`modal fade show d-block ${className}`} tabIndex={-1} onClick={onClose}>
                <div className="modal-dialog" onClick={(e) => e.stopPropagation()}>
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

const body = ({children}: { children: ReactNode }) => {
    return (
        <div className="modal-body">
            {children}
        </div>
    );
}

const footer = ({children}: { children: ReactNode }) => {
    return (
        <div className="modal-footer">
            {children}
        </div>
    )
}

modal.Body = body;
modal.Footer = footer;

export default modal;