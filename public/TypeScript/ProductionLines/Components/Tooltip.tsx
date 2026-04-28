import React, { ReactNode, useState } from 'react';
import {
    useFloating,
    offset,
    flip,
    shift,
    useHover,
    useFocus,
    useDismiss,
    useRole,
    useInteractions,
    arrow, FloatingArrow, useTransitionStyles,
} from '@floating-ui/react';

interface Props {
    content: ReactNode;
    children?: ReactNode;
    className?: string;
    placement?: 'top' | 'bottom' | 'left' | 'right';
}

const Tooltip: React.FC<Props> = ({
                                      content,
                                      children,
                                      className = '',
                                      placement = 'top',
                                  }) => {
    const [open, setOpen] = useState(false);
    const [arrowRef, setArrowRef] = useState<SVGSVGElement | null>(null);

    const { refs, floatingStyles, context } = useFloating({
        open,
        onOpenChange: setOpen,
        placement,
        middleware: [
            offset(8),
            flip(),
            shift({ padding: 8 }),
            arrow({ element: arrowRef }),
        ],
    });

    const { isMounted, styles: transitionStyles } = useTransitionStyles(context, {
        duration: 150,
    });

    const hover = useHover(context);
    const focus = useFocus(context);
    const dismiss = useDismiss(context);
    const role = useRole(context, { role: 'tooltip' });

    const { getReferenceProps, getFloatingProps } = useInteractions([
        hover,
        focus,
        dismiss,
        role,
    ]);

    return (
        <>
            <span
                ref={refs.setReference}
                {...getReferenceProps()}
                className={className}
                style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    cursor: 'help',
                }}
            >
                {children}
            </span>

            {isMounted && (
                <div
                    ref={refs.setFloating}
                    className={`tooltip bs-tooltip-auto fade show`}
                    style={{
                        ...floatingStyles,
                        ...transitionStyles,
                        zIndex: 1080,
                    }}
                    {...getFloatingProps()}
                >
                    <FloatingArrow
                        ref={setArrowRef}
                        context={context}
                        className="tooltip-arrow-svg"
                        style={{ fill: 'var(--bs-tooltip-bg, #000)' }}
                    />


                    <div
                        className="tooltip-inner"
                        style={{
                            maxWidth: '240px',
                        }}
                    >
                        {content}
                    </div>
                </div>
            )}
        </>
    );
};

export default Tooltip;