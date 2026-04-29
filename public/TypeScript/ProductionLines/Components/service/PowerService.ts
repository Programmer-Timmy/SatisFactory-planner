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

        const buildingInfo = (rec.building && rec.building[0]) || (rec.buildings && rec.buildings[0]) || null;
        let foundBuilding: any = null;
        const normalize = (s: any) => typeof s === 'string' ? s.toLowerCase().replace(/[^a-z0-9]/g, '') : '';

        // try by explicit buildings_id
        if (rec.buildings_id) {
            foundBuilding = appData.buildings.find((b: any) => b.id === rec.buildings_id) || null;
        }
        // try buildingInfo id
        if (!foundBuilding && buildingInfo?.id) {
            foundBuilding = appData.buildings.find((b: any) => b.id === buildingInfo.id) || null;
        }
        // try exact class_name match
        if (!foundBuilding && buildingInfo?.class_name) {
            const target = normalize(buildingInfo.class_name);
            foundBuilding = appData.buildings.find((b: any) => normalize(b.class_name) === target) || null;
        }
        // try recipe-level class_name
        if (!foundBuilding && rec.class_name) {
            const target2 = normalize(rec.class_name);
            foundBuilding = appData.buildings.find((b: any) => normalize(b.class_name) === target2) || null;
        }
        // try contains match (loose)
        if (!foundBuilding && buildingInfo?.class_name) {
            const targetLo = (buildingInfo.class_name || '').toLowerCase();
            foundBuilding = appData.buildings.find((b: any) => (b.class_name || '').toLowerCase().includes(targetLo) || targetLo.includes((b.class_name || '').toLowerCase())) || null;
        }
        if (!foundBuilding) continue;

        // Group by building id + exact clock speed string. Do not merge fractional clock speeds unless they are exactly the same.
        const clockKey = String(rawClock); // preserve exact provided clock value
        // include recipe id so fractional remainders from different recipes aren't merged when clocks are identical
        const key = `${foundBuilding.id}|${clockKey}|${row.recipe_id}`;

        if (!needs[key]) needs[key] = { buildingId: foundBuilding.id, powerUsed: Number(foundBuilding.power_used || 0), total: 0, clockSpeed: rawClock };
        needs[key].total += machinesNeeded;
    }

    const autoRows: any[] = [];

    // Build entries per (buildingId|clock) key
    const entries = Object.values(needs).map((n: any) => {
        const total = n.total;
        const integerPart = Math.floor(total + smallEps);
        let remainder = total - integerPart;
        if (remainder < smallEps) remainder = 0;
        return { n, total, integerPart, remainder };
    });

    // Sum integer parts per building id so all full machines are combined into one full-machine row (100%) per building
    const integerSums: Record<number, number> = {};
    for (const e of entries) {
        if (e.integerPart > 0) {
            integerSums[e.n.buildingId] = (integerSums[e.n.buildingId] || 0) + e.integerPart;
        }
    }

    for (const [bidStr, sum] of Object.entries(integerSums)) {
        const bid = Number(bidStr);
        if (sum > 0) {
            autoRows.push({
                idpower: Date.now() + Math.floor(Math.random() * 1000),
                building_ammount: sum,
                clock_speed: 100,
                buildings_id: bid,
                production_lines_id: appData.productLine.id,
                power_used: appData.buildings.find((b: any) => b.id === bid)?.power_used || 0,
                user: 0,
                building: appData.buildings.find((b: any) => b.id === bid) || null
            });
        }
    }

    // For each original group keep fractional remainder as a single 1-machine at fractional clock (preserve distinct fractional clocks)
    for (const e of entries) {
        const remainder = e.remainder;
        if (remainder > 0) {
            // express as percentage with 4 decimals (e.g., 8.3333)
            const percent = Math.round(remainder * 1000000) / 10000;
            autoRows.push({
                idpower: Date.now() + Math.floor(Math.random() * 1000) + 10000,
                building_ammount: 1,
                clock_speed: percent,
                buildings_id: e.n.buildingId,
                production_lines_id: appData.productLine.id,
                power_used: e.n.powerUsed,
                user: 0,
                building: appData.buildings.find((b: any) => b.id === e.n.buildingId) || null
            });
        }
    }

    autoRows.sort((a:any,b:any)=>{
        const an = (a.building?.name || '').toLowerCase();
        const bn = (b.building?.name || '').toLowerCase();
        if (an < bn) return -1;
        if (an > bn) return 1;
        if (b.building_ammount !== a.building_ammount) return b.building_ammount - a.building_ammount;
        return (b.clock_speed || 0) - (a.clock_speed || 0);
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
