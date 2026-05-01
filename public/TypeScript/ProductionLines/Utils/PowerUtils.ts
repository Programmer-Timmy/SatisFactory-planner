export function calculateBuildingAmount(recipe: any, row: any): number {
    const amount = Number(row.quantity) || 0;
    const maxClockSpeed = row.recipeSetting?.clockSpeed || 100;
    const useSomersloop = !!row.recipeSetting?.useSomersloop;

    if (!recipe || !recipe.export_amount_per_min || recipe.export_amount_per_min === 0) return 0;

    return amount / (recipe.export_amount_per_min * (maxClockSpeed / 100)) / (useSomersloop ? 2 : 1);
}
