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
		exports["dedicatedServer"] = factory();
	else
		root["dedicatedServer"] = factory();
})(this, () => {
return /******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./public/TypeScript/DedicatedServer/Data/ToastElement.ts":
/*!****************************************************************!*\
  !*** ./public/TypeScript/DedicatedServer/Data/ToastElement.ts ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   ToastElement: () => (/* binding */ ToastElement)\n/* harmony export */ });\nlet ToastElement = $(`\r\n  <div class=\"toast-container position-fixed p-3 bottom-0 end-0\" id=\"toastPlacement\" data-original-class=\"toast-container position-absolute p-3\">\r\n    <div class=\"toast\" id=\"DedicatedServerStatus\" data-bs-autohide=\"false\" style=\"background-color: rgba(var(--bs-body-bg-rgb),1);\">\r\n      <div class=\"toast-header\">\r\n        <svg class=\"bd-placeholder-img rounded me-2\" width=\"20\" height=\"20\" xmlns=\"http://www.w3.org/2000/svg\" aria-hidden=\"true\" preserveAspectRatio=\"xMidYMid slice\" focusable=\"false\"><rect width=\"100%\" height=\"100%\" fill=\"#007aff\"></rect></svg>\r\n        <strong class=\"me-auto name\">Dedicated Server status</strong>\r\n        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"toast\" aria-label=\"Close\"></button>\r\n      </div>\r\n      <div class=\"toast-body\">\r\n        Hello, world! This is a toast message.\r\n      </div>\r\n    </div>\r\n  </div>\r\n`);\n\n\n//# sourceURL=webpack:///./public/TypeScript/DedicatedServer/Data/ToastElement.ts?");

/***/ }),

/***/ "./public/TypeScript/DedicatedServer/classes/DedicatedServer.ts":
/*!**********************************************************************!*\
  !*** ./public/TypeScript/DedicatedServer/classes/DedicatedServer.ts ***!
  \**********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   DedicatedServer: () => (/* binding */ DedicatedServer)\n/* harmony export */ });\n/* harmony import */ var _Data_ToastElement__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../Data/ToastElement */ \"./public/TypeScript/DedicatedServer/Data/ToastElement.ts\");\n\nclass FakeToast {\n    constructor(_element) { }\n    show() { }\n    hide() { }\n    dispose() { }\n}\n// Use FakeToast as a fallback when Bootstrap's Toast is not available\nconst ToastConstructor = (window.bootstrap?.Toast || FakeToast);\nclass DedicatedServer {\n    show = false;\n    toast;\n    toastMessage = \"\";\n    closed = false;\n    gameSaveId = NaN;\n    fillColor = '#57e389';\n    constructor(gameSaveId) {\n        this.gameSaveId = gameSaveId;\n        // insert the toast element\n        document.body.appendChild(_Data_ToastElement__WEBPACK_IMPORTED_MODULE_0__.ToastElement[0]);\n        this.toast = new ToastConstructor(document.getElementById('DedicatedServerStatus'));\n        if (document.cookie.includes('toastClosed=true')) {\n            this.closed = true;\n        }\n        else {\n            this.closed = false;\n        }\n        if (!this.closed) {\n            this.HealthCheck();\n            this.applyeventListeners();\n            const intervalId = setInterval(() => {\n                if (!this.closed) {\n                    this.HealthCheck();\n                }\n                else {\n                    clearInterval(intervalId);\n                }\n            }, 60000);\n        }\n    }\n    ShowToast() {\n        $('#DedicatedServerStatus .toast-header svg rect').attr('fill', this.fillColor);\n        $('#DedicatedServerStatus .toast-body').html(this.toastMessage);\n        if (!$('#DedicatedServerStatus').hasClass('show')) {\n            this.toast.show();\n        }\n    }\n    HealthCheck() {\n        this.AjaxHealthCheck().then((response) => {\n            if (response.data.health === 'healthy') {\n                this.fillColor = '#57e389';\n                this.AjaxQueryServer().then((response) => {\n                    let message = '';\n                    message += `<div class=\"text-success mb-2 fw-bold\">Status: Healthy</div>`;\n                    message += `<div><strong>Session Name:</strong> ${response.data.serverGameState.activeSessionName}</div>`;\n                    message += `<div><strong>Game Paused:</strong> ${response.data.serverGameState.isGamePaused ? '<span class=\"text-danger\">Yes</span>' : '<span class=\"text-success\">No</span>'}</div>`;\n                    message += `<div><strong>Connected Players:</strong> ${response.data.serverGameState.numConnectedPlayers}</div>`;\n                    message += `<div><strong>Total Game Time:</strong> ${response.data.serverGameState.totalGameDuration} minutes</div>`;\n                    this.toastMessage = message;\n                    this.ShowToast();\n                }).catch((error) => {\n                    this.fillColor = '#ff6b6b';\n                    this.toastMessage = `<div class=\"text-danger fw-bold\">The dedicated server is down.</div>`;\n                    this.ShowToast();\n                });\n            }\n        }).catch((error) => {\n            this.fillColor = '#ff6b6b';\n            this.toastMessage = `<div class=\"text-danger fw-bold\">The dedicated server is down.</div>`;\n            this.ShowToast();\n        });\n    }\n    AjaxHealthCheck(gameSaveId = this.gameSaveId) {\n        const token = this._getCsrfToken();\n        return new Promise(function (resolve, reject) {\n            $.ajax({\n                url: 'dedicatedServerAPI/healthCheck', // Replace with your actual PHP file path\n                type: 'POST',\n                data: {\n                    saveGameId: gameSaveId\n                },\n                headers: { 'X-CSRF-Token': token },\n                dataType: 'json',\n                success: function (response) {\n                    try {\n                        if (response.status === 'success') {\n                            resolve(response);\n                        }\n                        else {\n                            reject(response);\n                        }\n                    }\n                    catch (error) {\n                        reject(error);\n                    }\n                },\n                error: function (xhr, status, error) {\n                    $('#apiResponse').html(`<div class=\"alert alert-danger\">Error: ${error}</div>`);\n                }\n            });\n        });\n    }\n    AjaxQueryServer(gameSaveId = this.gameSaveId) {\n        const token = this._getCsrfToken();\n        return new Promise(function (resolve, reject) {\n            $.ajax({\n                url: 'dedicatedServerAPI/queryServerState', // Replace with your actual PHP file path\n                type: 'POST',\n                data: {\n                    saveGameId: gameSaveId\n                },\n                headers: { 'X-CSRF-Token': token },\n                dataType: 'json',\n                success: function (response) {\n                    try {\n                        if (response.status === 'success') {\n                            resolve(response);\n                        }\n                        else {\n                            reject(response);\n                        }\n                    }\n                    catch (error) {\n                        reject(error);\n                    }\n                },\n                error: function (xhr, status, error) {\n                    $('#apiResponse').html(`<div class=\"alert alert-danger\">Error: ${error}</div>`);\n                }\n            });\n        });\n    }\n    applyeventListeners() {\n        $('#DedicatedServerStatus').on('hidden.bs.toast', () => {\n            // set cookie to not show the toast\n            this.closed = true;\n            document.cookie = 'toastClosed=true';\n        });\n    }\n    _getCsrfToken() {\n        const meta = $('meta[name=\"csrf-token\"]');\n        if (meta.length === 0) {\n            throw new Error('CSRF token not found');\n        }\n        return meta.attr('content');\n    }\n}\n\n\n//# sourceURL=webpack:///./public/TypeScript/DedicatedServer/classes/DedicatedServer.ts?");

/***/ }),

/***/ "./public/TypeScript/DedicatedServer/index.ts":
/*!****************************************************!*\
  !*** ./public/TypeScript/DedicatedServer/index.ts ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _classes_DedicatedServer__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./classes/DedicatedServer */ \"./public/TypeScript/DedicatedServer/classes/DedicatedServer.ts\");\n\nwindow.DedicatedServer = _classes_DedicatedServer__WEBPACK_IMPORTED_MODULE_0__.DedicatedServer;\n\n\n//# sourceURL=webpack:///./public/TypeScript/DedicatedServer/index.ts?");

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
/******/ 	var __webpack_exports__ = __webpack_require__("./public/TypeScript/DedicatedServer/index.ts");
/******/ 	
/******/ 	return __webpack_exports__;
/******/ })()
;
});