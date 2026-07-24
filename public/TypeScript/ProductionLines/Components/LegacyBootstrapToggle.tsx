import React, {useEffect, useRef} from 'react';

interface BootstrapToggleWindow extends Window {
    $?: BootstrapToggleJQueryStatic;
}

interface BootstrapToggleJQueryStatic extends JQueryStatic {
    fn: JQueryStatic['fn'] & { bootstrapToggle?: unknown };
}

interface BootstrapToggleJQuery extends JQuery<HTMLInputElement> {
    bootstrapToggle: (action?: string) => BootstrapToggleJQuery;
}

export interface LegacyBootstrapToggleProps {
    id: string;
    checked?: boolean;
    defaultChecked?: boolean;
    disabled?: boolean;
    ariaLabel: string;
    onChange: (checked: boolean) => void;
    onStyle?: string;
    offStyle?: string;
    onLabel?: string;
    offLabel?: string;
    size?: 'sm' | 'md' | 'lg';
    styleName?: string;
    theme?: string;
    className?: string;
    dataAttributes?: Record<string, string | number | boolean>;
}

const pluginNamespace = '.plLegacyToggle';

const getBootstrapToggle = (element: HTMLInputElement): BootstrapToggleJQuery | null => {
    const jquery = (window as BootstrapToggleWindow).$;
    if (!jquery?.fn.bootstrapToggle) return null;
    return jquery(element) as BootstrapToggleJQuery;
};

const isPluginInitialized = (toggle: BootstrapToggleJQuery): boolean => {
    try {
        return !!toggle.data('bs.toggle') || toggle.parent('.toggle').length > 0;
    } catch (e) {
        return false;
    }
};

const setPluginState = (element: HTMLInputElement, checked: boolean): void => {
    const toggle = getBootstrapToggle(element);
    const alreadyChecked = element.checked === checked;
    element.checked = checked;
    if (!toggle || alreadyChecked) return;

    try {
        toggle.bootstrapToggle(checked ? 'on' : 'off');
    } catch (e) {
        // Native checked state above is the fallback source of truth.
    }
};

const LegacyBootstrapToggle: React.FC<LegacyBootstrapToggleProps> = ({
    id,
    checked,
    defaultChecked = false,
    disabled = false,
    ariaLabel,
    onChange,
    onStyle = 'success',
    offStyle = 'dark',
    onLabel = "<i class='fa-solid fa-check'></i>",
    offLabel = "<i class='fa-solid fa-times'></i>",
    size = 'sm',
    styleName = 'ios',
    theme = 'dark',
    className,
    dataAttributes = {},
}) => {
    const inputRef = useRef<HTMLInputElement | null>(null);
    const onChangeRef = useRef(onChange);

    useEffect(() => {
        onChangeRef.current = onChange;
    }, [onChange]);

    useEffect(() => {
        const element = inputRef.current;
        if (!element) return;

        const handleNativeChange = (event: Event) => {
            onChangeRef.current((event.currentTarget as HTMLInputElement).checked);
        };

        let removeNativeListener: (() => void) | null = null;
        const timer = window.setTimeout(() => {
            const toggle = getBootstrapToggle(element);
            if (!toggle) {
                element.addEventListener('change', handleNativeChange);
                removeNativeListener = () => element.removeEventListener('change', handleNativeChange);
                return;
            }

            try {
                if (isPluginInitialized(toggle)) toggle.bootstrapToggle('destroy');
            } catch (e) {
                // Ignore stale plugin state from a previous render.
            }

            try {
                toggle.bootstrapToggle();
                toggle.off(pluginNamespace).on(`change${pluginNamespace}`, function () {
                    const jquery = (window as BootstrapToggleWindow).$;
                    onChangeRef.current(jquery ? !!jquery(this).prop('checked') : element.checked);
                });
                if (checked !== undefined) setPluginState(element, checked);
            } catch (e) {
                element.addEventListener('change', handleNativeChange);
                removeNativeListener = () => element.removeEventListener('change', handleNativeChange);
            }
        }, 0);

        return () => {
            window.clearTimeout(timer);
            if (removeNativeListener) removeNativeListener();

            const toggle = getBootstrapToggle(element);
            if (!toggle) return;

            try {
                toggle.off(pluginNamespace);
                if (isPluginInitialized(toggle)) toggle.bootstrapToggle('destroy');
            } catch (e) {
                // Cleanup should not break React unmounting.
            }
        };
    }, [id, onStyle, offStyle, onLabel, offLabel, size, styleName, theme]);

    useEffect(() => {
        const element = inputRef.current;
        if (!element || checked === undefined) return;
        setPluginState(element, checked);
    }, [checked]);

    return (
        <input
            ref={inputRef}
            id={id}
            type="checkbox"
            className={className}
            defaultChecked={checked ?? defaultChecked}
            disabled={disabled}
            data-toggle="toggle"
            data-onstyle={onStyle}
            data-offstyle={offStyle}
            data-onlabel={onLabel}
            data-offlabel={offLabel}
            data-size={size}
            data-style={styleName}
            data-theme={theme}
            aria-label={ariaLabel}
            {...dataAttributes}
        />
    );
};

export default LegacyBootstrapToggle;