/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else if(typeof exports === 'object')
		exports["powerProduction"] = factory();
	else
		root["powerProduction"] = factory();
})(this, () => {
return /******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./public/TypeScript/PowerProduction/Classes/PowerProduction.ts":
/*!**********************************************************************!*\
  !*** ./public/TypeScript/PowerProduction/Classes/PowerProduction.ts ***!
  \**********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   PowerProduction: () => (/* binding */ PowerProduction)\n/* harmony export */ });\nvar ActionType;\n(function (ActionType) {\n    ActionType[\"Add\"] = \"add\";\n    ActionType[\"Delete\"] = \"delete\";\n    ActionType[\"Update\"] = \"update\";\n    ActionType[\"Calculate\"] = \"calculate\";\n})(ActionType || (ActionType = {}));\nclass PowerProduction {\n    powerProduction;\n    newInput;\n    constructor() {\n        this.powerProduction = $('#powerProduction');\n        this.newInput = $('#powerProductionCardNew');\n    }\n    async addNewPowerPlant(element) {\n        // Select display name, amount, and clock speed\n        const name = this.newInput.find('#building option:selected').text();\n        const amount = this.newInput.find('#amount').val() || '1'; // Default amount to '1'\n        const clockSpeed = this.newInput.find('#clock_speed').val() || '100'; // Default clock speed to '100'\n        // Validate inputs and return early if invalid\n        if (!this.validateAndHandleInput(name, amount, clockSpeed)) {\n            return;\n        }\n        const newCard = this.newCard();\n        const json = await this.applyToDatabase(ActionType.Add, element);\n        if (json.hasOwnProperty('error')) {\n            console.error(json.error);\n            return;\n        }\n        if (this.powerProduction.find('#noPowerBuildings').length > 0) {\n            this.powerProduction.find('#noPowerBuildings').remove();\n        }\n        // update id to be unique\n        const newId = `powerProductionCard${json.powerProductionId}`;\n        newCard.attr('id', newId);\n        // Update the cloned card with new input values\n        newCard.find('.col-5 h6').text(name);\n        newCard.find('#amount').val(amount.toString());\n        newCard.find('#clock_speed').val(clockSpeed);\n        // Apply event listeners to the new card\n        this.applyEventListenersToCard(newCard);\n        // Append the updated card to the power production section\n        this.powerProduction.append(newCard);\n        // Reset the form for adding new power plants\n        this.resetAddCard();\n        // Calculate the new power production\n        this.calculatePowerProduction();\n    }\n    async deletePowerPlant(element) {\n        const json = await this.applyToDatabase(ActionType.Delete, element);\n        if (json.hasOwnProperty('error')) {\n            console.error(json.error);\n            return;\n        }\n        const card = $(element).closest('.card');\n        card.remove();\n        if (this.powerProduction.find('.card').length === 0) {\n            this.powerProduction.append('<div class=\"alert alert-warning text-center\" id=\"noPowerBuildings\" role=\"alert\">\\n' +\n                '                                Oh no! You don\\'t have any power production buildings yet. Add some down below.\\n' +\n                '                            </div>');\n        }\n        this.calculatePowerProduction();\n    }\n    async updatePowerPlant(element) {\n        const json = await this.applyToDatabase(ActionType.Update, element);\n        if (json.hasOwnProperty('error')) {\n            console.error(json.error);\n            return;\n        }\n        this.calculatePowerProduction();\n    }\n    async calculatePowerProduction() {\n        const json = await this.applyToDatabase(ActionType.Calculate, document.createElement('div'));\n        if (json.hasOwnProperty('error')) {\n            console.error(json.error);\n            return;\n        }\n        const totalPowerProduction = json.totalPowerProduction;\n        updatePowerProduction(totalPowerProduction);\n    }\n    applyEventListenersToCard(card) {\n        const deleteButton = card.find('.deletePowerProduction');\n        const updateInputs = card.find('input');\n        if (deleteButton.length > 0) {\n            deleteButton.on('click', (event) => {\n                this.deletePowerPlant(event.currentTarget);\n            });\n        }\n        if (updateInputs.length > 0) {\n            updateInputs.on('change', (event) => {\n                this.updatePowerPlant(event.currentTarget);\n            });\n        }\n    }\n    applyEventListeners() {\n        const addButton = this.newInput.find('button');\n        const deleteButtons = this.powerProduction.find('.deletePowerProduction');\n        const updateInputs = this.powerProduction.find('input');\n        if (addButton) {\n            addButton.on('click', (event) => {\n                this.addNewPowerPlant(event.currentTarget);\n            });\n        }\n        if (deleteButtons) {\n            deleteButtons.on('click', (event) => {\n                this.deletePowerPlant(event.currentTarget);\n            });\n        }\n        if (updateInputs) {\n            updateInputs.on('change', (event) => {\n                this.updatePowerPlant(event.currentTarget);\n            });\n        }\n    }\n    checkAndHandleNoPowerPlants() {\n        if (this.powerProduction.find('.card').length === 0) {\n            this.powerProduction.append('<h6>No power plants added yet</h6>');\n        }\n    }\n    newCard() {\n        // Create the card element\n        const card = document.createElement('div');\n        card.className = 'card mb-2';\n        const cardBody = document.createElement('div');\n        cardBody.className = 'card-body p-2 row d-flex justify-content-between align-items-center';\n        // Create the content of the card\n        cardBody.innerHTML = `\r\n        <div class=\"col-5 col-lg-6 ps-3 pe-1\">\r\n            <h6 class=\"m-0\"></h6>\r\n        </div>\r\n        <div class=\"col-2 px-1\">\r\n            <input type=\"number\" class=\"form-control\" id=\"amount\" name=\"amount\"\r\n                   min=\"1\" max=\"1000\">\r\n        </div>\r\n        <div class=\"col-2 px-1\">\r\n            <input type=\"number\" class=\"form-control\" id=\"clock_speed\"\r\n                   name=\"clock_speed\" min=\"1\" max=\"250\" step=\"any\">\r\n        </div>\r\n        <div class=\"col-3 col-lg-2 text-end ps-1 pe-3\">\r\n            <button type=\"button\" class=\"btn btn-danger deletePowerProduction\">\r\n            Delete\r\n            </button>\r\n        </div>\r\n    `;\n        card.appendChild(cardBody);\n        return $(card);\n    }\n    resetAddCard() {\n        this.newInput.find('#amount').val('1');\n        this.newInput.find('#clock_speed').val('100');\n        // Reset select to the first option\n        this.newInput.find('select').prop('selectedIndex', 0);\n    }\n    validateAndHandleInput(name, amount, clockSpeed) {\n        const addInvalidClass = (selector) => this.newInput.find(selector).addClass('is-invalid');\n        const removeInvalidClass = (selector) => this.newInput.find(selector).removeClass('is-invalid');\n        let error = false;\n        // If validation passed, clear any error states\n        removeInvalidClass('#building');\n        removeInvalidClass('#amount');\n        removeInvalidClass('#clock_speed');\n        // Check for empty or default values\n        if (!name || name === 'Select a building') {\n            addInvalidClass('#building');\n            error = true;\n        }\n        // Validate amount\n        const parsedAmount = parseFloat(Array.isArray(amount) ? amount[0] : amount.toString());\n        if (isNaN(parsedAmount) || parsedAmount < 1 || parsedAmount > 1000) {\n            addInvalidClass('#amount');\n            error = true;\n        }\n        // Validate clock speed\n        const parsedClockSpeed = parseFloat(Array.isArray(clockSpeed) ? clockSpeed[0] : clockSpeed.toString());\n        if (isNaN(parsedClockSpeed) || parsedClockSpeed < 1 || parsedClockSpeed > 250) {\n            addInvalidClass('#clock_speed');\n            error = true;\n        }\n        return !error;\n    }\n    static _getCsrfToken() {\n        const meta = $('meta[name=\"csrf-token\"]');\n        if (meta.length === 0 || meta.attr('content') === undefined) {\n            throw new Error('CSRF token not found');\n        }\n        return meta.attr('content');\n    }\n    // apply to database using ajax\n    async applyToDatabase(action, element) {\n        const card = $(element).closest('.card');\n        const cardId = card.attr('id') || '';\n        const powerProductionId = cardId.replace('powerProductionCard', '');\n        const url = new URL(window.location.href);\n        const gameSaveId = url.searchParams.get('id');\n        switch (action) {\n            case ActionType.Add:\n                const buildingId = card.find('#building').val();\n                const amount = card.find('#amount').val();\n                const clockSpeed = card.find('#clock_speed').val();\n                return new Promise(function (resolve, reject) {\n                    $.ajax({\n                        url: 'powerProduction/add',\n                        type: 'POST',\n                        dataType: 'json',\n                        headers: { 'X-CSRF-Token': PowerProduction._getCsrfToken() },\n                        data: {\n                            gameSaveId: gameSaveId,\n                            buildingId: buildingId,\n                            amount: amount,\n                            clockSpeed: clockSpeed\n                        },\n                        success: function (data) {\n                            resolve(data);\n                        }\n                    });\n                });\n            case ActionType.Delete:\n                return new Promise(function (resolve, reject) {\n                    $.ajax({\n                        url: 'powerProduction/delete',\n                        type: 'POST',\n                        dataType: 'json',\n                        headers: { 'X-CSRF-Token': PowerProduction._getCsrfToken() },\n                        data: {\n                            gameSaveId: gameSaveId,\n                            powerProductionId: powerProductionId\n                        },\n                        success: function (data) {\n                            resolve(data);\n                        }\n                    });\n                });\n            case ActionType.Update:\n                const amount1 = card.find('#amount').val();\n                const clockSpeed1 = card.find('#clock_speed').val();\n                return new Promise(function (resolve, reject) {\n                    $.ajax({\n                        url: 'powerProduction/update',\n                        type: 'POST',\n                        dataType: 'json',\n                        headers: { 'X-CSRF-Token': PowerProduction._getCsrfToken() },\n                        data: {\n                            gameSaveId: gameSaveId,\n                            powerProductionId: powerProductionId,\n                            amount: amount1,\n                            clockSpeed: clockSpeed1\n                        },\n                        success: function (data) {\n                            resolve(data);\n                        }\n                    });\n                });\n            case ActionType.Calculate:\n                return new Promise(function (resolve, reject) {\n                    $.ajax({\n                        url: 'powerProduction/calculate',\n                        type: 'POST',\n                        dataType: 'json',\n                        headers: { 'X-CSRF-Token': PowerProduction._getCsrfToken() },\n                        data: {\n                            gameSaveId: gameSaveId\n                        },\n                        success: function (data) {\n                            resolve(data);\n                        }\n                    });\n                });\n        }\n    }\n}\n\n\n//# sourceURL=webpack:///./public/TypeScript/PowerProduction/Classes/PowerProduction.ts?");

/***/ }),

/***/ "./public/TypeScript/PowerProduction/index.ts":
/*!****************************************************!*\
  !*** ./public/TypeScript/PowerProduction/index.ts ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _Classes_PowerProduction__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Classes/PowerProduction */ \"./public/TypeScript/PowerProduction/Classes/PowerProduction.ts\");\n\nconst powerProduction = new _Classes_PowerProduction__WEBPACK_IMPORTED_MODULE_0__.PowerProduction();\npowerProduction.applyEventListeners();\n\n\n//# sourceURL=webpack:///./public/TypeScript/PowerProduction/index.ts?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./public/TypeScript/PowerProduction/index.ts");
/******/ 	
/******/ 	return __webpack_exports__;
/******/ })()
;
});