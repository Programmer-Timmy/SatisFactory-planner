export class Calculations {

    /**
     * Applies a mathematical calculation from a string input.
     * Only allows numbers and basic arithmetic operators (+, -, *, /, parentheses).
     * Returns the result of the calculation or undefined if the input is invalid.
     * @param calculation - The string containing the mathematical expression.
     * @param defaultValue - The value to return if the calculation is invalid.
     * @returns The result of the calculation or undefined.
     *
     * @example
     * // Returns 7
     * Calculations.applyMathCalculation("3 + 4", "0");
     * @example
     * // Returns 14
     * Calculations.applyMathCalculation("2 * (3 + 4)", "0");
     * @example
     * // Returns "0" (invalid input)
     * Calculations.applyMathCalculation("2 * (3 + abc)", "0");
     */
    public static applyMathCalculation(calculation: string, defaultValue: string): string {

        const valid = /^[0-9+\-*/().\s]*$/;
        if(!valid.test(calculation as string)){
            return defaultValue
        }

        try {
            const result = Function(`"use strict"; return (${calculation})`)();
            if (!isNaN(result)) {
                return result.toString();
            }
            return defaultValue;
        } catch (e) {
            return defaultValue;
        }
    }
}