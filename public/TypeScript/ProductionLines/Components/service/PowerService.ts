// Service to calculate auto power rows extracted from ProductionLineApp
export function calculateAutoPowerRows(appData: any, productionRows: any[], recipeMap: Record<number, any>) {
    if (!appData) return [];
    const needs: Record<string, { buildingId: number; powerUsed: number; total: number; clockSpeed: number }> = {};
    const smallEps = 1e-6;

    for (const row of productionRows) {
        const rec = recipeMap[row.recipe_id] ?? null;
        if (!rec) continue;
        const qty = Number(row.product_quantity) || 0;
        const rawClock = (row.clock_speed === '' || row.clock_speed === undefined || row.clock_speed === null) ? 100 : Number(row.clock_speed);
        const clock = Math.max(0, Math.min(250, rawClock));
        const useSomersloop = !!row.use_somersloop;
        const exportPerMin = rec.export_amount_per_min || 0;
        if (!exportPerMin || qty <= 0) continue;

        const capacityPerMachine = exportPerMin * (clock / 100) * (useSomersloop ? 2 : 1);
        if (capacityPerMachine <= 0) continue;
        const machinesNeeded = qty / capacityPerMachine;

        const buildingInfo = (rec.building && rec.building[0]) || null;
        let foundBuilding = appData.buildings.find((b: any) => b.class_name === (buildingInfo?.class_name || '')) || appData.buildings.find((b: any) => b.id === (rec.buildings_id || 0)) || null;
        if (!foundBuilding) continue;

        const key = foundBuilding.class_name || String(foundBuilding.id);
        if (!needs[key]) needs[key] = { buildingId: foundBuilding.id, powerUsed: Number(foundBuilding.power_used || 0), total: 0, clockSpeed: clock };
        needs[key].total += machinesNeeded;
    }

    const autoRows: any[] = [];
    Object.values(needs).forEach(n => {
        const total = n.total;
        const integerPart = Math.floor(total + smallEps);
        let remainder = total - integerPart;
        if (remainder < smallEps) remainder = 0;

        if (integerPart > 0) {
            autoRows.push({
                idpower: Date.now() + Math.floor(Math.random() * 1000),
                building_ammount: integerPart,
                clock_speed: n.clockSpeed,
                buildings_id: n.buildingId,
                production_lines_id: appData.productLine.id,
                power_used: n.powerUsed,
                user: 0,
                building: appData.buildings.find((b: any) => b.id === n.buildingId) || null
            });
        }

        if (remainder > 0) {
            const clock = Math.round(remainder * 10000) / 100; // two decimals
            autoRows.push({
                idpower: Date.now() + Math.floor(Math.random() * 1000) + 10000,
                building_ammount: 1,
                clock_speed: clock,
                buildings_id: n.buildingId,
                production_lines_id: appData.productLine.id,
                power_used: n.powerUsed,
                user: 0,
                building: appData.buildings.find((b: any) => b.id === n.buildingId) || null
            });
        }
    });

    return autoRows;
}

// compute power consumption for a power row
export function computeConsumption(row: any, appData: any) {
    const clock = Number(row.clock_speed) || 0;
    const amount = Number(row.building_ammount) || 0;
    const powerUsed = Number(row.power_used) || (appData?.buildings.find((b: any) => b.id === row.buildings_id)?.power_used ?? 0);
    return amount * powerUsed * Math.pow(clock / 100, 1.321928);
}

// total consumption for a list of power rows
export function totalConsumption(rows: any[], appData: any) {
    return (rows || []).reduce((s, r) => s + computeConsumption(r, appData), 0);
}
