import type {AppData} from '../../Types/global';

interface UmamiWindow extends Window {
    umami?: {
        track: (eventName: string, payload?: Record<string, unknown>) => void;
    };
}

export type ProductionLineEventName =
    | 'Add Power Row'
    | 'Add Recipe'
    | 'Add Recipe From Import'
    | 'Calculate Power'
    | 'Change Recipe'
    | 'Change Recipe Quantity'
    | 'Delete Recipe'
    | 'Open Checklist'
    | 'Open Help'
    | 'Open Power'
    | 'Open Settings'
    | 'Open Visualization'
    | 'Save Power'
    | 'Save Production Line';

export function trackProductionLineEvent(
    appData: AppData | null,
    eventName: ProductionLineEventName,
    extra: Record<string, unknown> = {}
): void {
    try {
        const umami = (window as UmamiWindow).umami;
        if (!umami) return;

        umami.track(eventName, {
            game_save: appData?.productLine.game_saves_id,
            production_line: appData?.productLine.id,
            ...extra,
        });
    } catch (e) {
        // Analytics should never affect editor behavior.
    }
}
