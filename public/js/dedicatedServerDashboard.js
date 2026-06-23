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
		exports["dedicatedServerDashboard"] = factory();
	else
		root["dedicatedServerDashboard"] = factory();
})(this, () => {
return /******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./public/TypeScript/DedicatedServerDashboard/Classes/SaveDownloadHandler.ts"
/*!***********************************************************************************!*\
  !*** ./public/TypeScript/DedicatedServerDashboard/Classes/SaveDownloadHandler.ts ***!
  \***********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   SaveDownloadHandler: () => (/* binding */ SaveDownloadHandler)\n/* harmony export */ });\nclass SaveDownloadHandler {\n    csrfToken;\n    saveGameId;\n    sessions;\n    sessionSelect;\n    saveSelect;\n    downloadButton;\n    constructor(csrfToken, saveGameId, sessions) {\n        this.csrfToken = csrfToken;\n        this.saveGameId = saveGameId;\n        this.sessions = sessions;\n        this.sessionSelect = document.getElementById('saveGameDownloadSelect');\n        this.saveSelect = document.getElementById('saveFileDownloadSelect');\n        this.downloadButton = document.getElementById('downloadSaveLink');\n    }\n    init() {\n        if (!this.sessionSelect || !this.saveSelect || !this.downloadButton)\n            return;\n        this.sessionSelect.addEventListener('change', () => this.updateSaveOptions());\n        this.saveSelect.addEventListener('change', () => this.handleSaveSelection());\n        this.downloadButton.addEventListener('click', () => this.downloadSave());\n        if (this.sessions.length > 0) {\n            this.updateSaveOptions();\n        }\n    }\n    // ---------- UI Handling ----------\n    updateSaveOptions() {\n        const selectedSession = this.sessionSelect.value;\n        this.saveSelect.innerHTML = '';\n        const session = this.sessions.find(s => s.sessionName === selectedSession);\n        if (session && session.saveHeaders.length > 0) {\n            const defaultOption = new Option('Select a save file', '', true, true);\n            defaultOption.disabled = true;\n            this.saveSelect.appendChild(defaultOption);\n            session.saveHeaders.forEach(save => {\n                const option = new Option(save.saveName, save.saveName);\n                this.saveSelect.appendChild(option);\n            });\n        }\n        else {\n            const option = new Option('No saves available', '', true, true);\n            option.disabled = true;\n            this.saveSelect.appendChild(option);\n        }\n        this.toggleDownloadButton(false);\n    }\n    handleSaveSelection() {\n        const hasSelection = !!(this.sessionSelect.value && this.saveSelect.value);\n        this.toggleDownloadButton(hasSelection);\n    }\n    toggleDownloadButton(enable) {\n        if (enable) {\n            this.downloadButton.classList.remove('disabled');\n            this.downloadButton.removeAttribute('aria-disabled');\n        }\n        else {\n            this.downloadButton.classList.add('disabled');\n            this.downloadButton.setAttribute('aria-disabled', 'true');\n        }\n    }\n    // ---------- Modal States ----------\n    showLoading(show) {\n        const loadingDiv = document.getElementById('downloadSaveModalLoading');\n        const completedDiv = document.getElementById('downloadCompleted');\n        const contentDiv = loadingDiv?.previousElementSibling;\n        const spinner = this.downloadButton.querySelector('span');\n        if (!loadingDiv || !completedDiv || !contentDiv || !spinner)\n            return;\n        if (show) {\n            loadingDiv.classList.remove('d-none');\n            completedDiv.classList.add('d-none');\n            contentDiv.classList.add('d-none');\n            spinner.classList.remove('d-none');\n            this.toggleDownloadButton(false);\n        }\n        else {\n            loadingDiv.classList.add('d-none');\n            completedDiv.classList.add('d-none');\n            contentDiv.classList.remove('d-none');\n            spinner.classList.add('d-none');\n            this.toggleDownloadButton(true);\n        }\n    }\n    showDownloadCompleted(show) {\n        const completedDiv = document.getElementById('downloadCompleted');\n        const loadingDiv = document.getElementById('downloadSaveModalLoading');\n        const contentDiv = loadingDiv?.previousElementSibling;\n        const spinner = this.downloadButton.querySelector('span');\n        if (!completedDiv || !loadingDiv || !contentDiv || !spinner)\n            return;\n        if (show) {\n            completedDiv.classList.remove('d-none');\n            loadingDiv.classList.add('d-none');\n            contentDiv.classList.add('d-none');\n            spinner.classList.add('d-none');\n            this.toggleDownloadButton(false);\n        }\n        else {\n            completedDiv.classList.add('d-none');\n            loadingDiv.classList.add('d-none');\n            contentDiv.classList.remove('d-none');\n            spinner.classList.add('d-none');\n            this.toggleDownloadButton(true);\n        }\n    }\n    resetModal() {\n        this.sessionSelect.selectedIndex = 0;\n        this.saveSelect.innerHTML = '<option disabled selected>Select a session first</option>';\n        this.toggleDownloadButton(false);\n    }\n    // ---------- AJAX ----------\n    downloadSave() {\n        this.showLoading(true);\n        $.ajax({\n            url: '/dedicatedServerAPI/downloadSave',\n            type: 'POST',\n            data: {\n                gameSaveId: this.saveGameId,\n                sessionName: $(this.sessionSelect).val(),\n                saveName: $(this.saveSelect).val(),\n            },\n            headers: { 'X-CSRF-Token': this.csrfToken },\n            xhrFields: { responseType: 'blob' }, // Important to receive binary data\n            success: (response) => {\n                const saveName = $(this.saveSelect).val();\n                const blob = new Blob([response], { type: 'application/octet-stream' });\n                const url = window.URL.createObjectURL(blob);\n                const a = document.createElement('a');\n                a.href = url;\n                a.download = saveName.endsWith('.sav') ? saveName : `${saveName}.sav`;\n                document.body.appendChild(a);\n                a.click();\n                a.remove();\n                window.URL.revokeObjectURL(url);\n                this.showDownloadCompleted(true);\n                setTimeout(() => {\n                    this.resetModal();\n                    this.showDownloadCompleted(false);\n                    // @ts-ignore\n                    $('#downloadSaveModal').modal('hide');\n                }, 3000);\n            },\n            error: (xhr, status, error) => {\n                alert('Error downloading save: ' + (xhr.responseJSON?.error || error));\n                this.showLoading(false);\n            },\n        });\n    }\n}\n\n\n//# sourceURL=webpack:///./public/TypeScript/DedicatedServerDashboard/Classes/SaveDownloadHandler.ts?\n}");

/***/ },

/***/ "./public/TypeScript/DedicatedServerDashboard/Classes/ServerStatusUpdater.ts"
/*!***********************************************************************************!*\
  !*** ./public/TypeScript/DedicatedServerDashboard/Classes/ServerStatusUpdater.ts ***!
  \***********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   ServerStatusUpdater: () => (/* binding */ ServerStatusUpdater)\n/* harmony export */ });\n/* harmony import */ var _SaveDownloadHandler__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SaveDownloadHandler */ \"./public/TypeScript/DedicatedServerDashboard/Classes/SaveDownloadHandler.ts\");\n\nclass ServerStatusUpdater {\n    csrfToken;\n    saveGameId;\n    refreshInterval;\n    saveDownloadHandler;\n    sessions;\n    constructor(csrfToken, saveGameId, sessions = [], refreshIntervalMs = 60_000) {\n        this.csrfToken = csrfToken;\n        this.saveGameId = saveGameId;\n        this.refreshInterval = refreshIntervalMs;\n        this.sessions = sessions;\n    }\n    init() {\n        document.addEventListener('DOMContentLoaded', () => {\n            this.updateLastChecked();\n            setInterval(() => this.checkHealth(), this.refreshInterval);\n        });\n        this.bindStopServerButton();\n        this.saveDownloadHandler = new _SaveDownloadHandler__WEBPACK_IMPORTED_MODULE_0__.SaveDownloadHandler(this.csrfToken, this.saveGameId, this.sessions);\n        this.saveDownloadHandler.init();\n    }\n    // ---------- Private Helpers ----------\n    updateListItem(label, html) {\n        const elements = Array.from(document.querySelectorAll('li strong'));\n        const target = elements.find(el => el.textContent?.includes(label));\n        if (target?.parentElement) {\n            target.parentElement.innerHTML = html;\n        }\n    }\n    cleanGamePhase(phaseString) {\n        const match = phaseString.match(/GP_([^\\.]+)/);\n        return match ? match[1].replace(/_/g, ' ') : phaseString;\n    }\n    formatHealth(healthy) {\n        return healthy\n            ? '<span class=\"text-success\">Healthy <i class=\"fa-solid fa-check fa-lg\"></i></span>'\n            : '<span class=\"text-danger\">Unhealthy <i class=\"fa-solid fa-xmark fa-lg\"></i></span>';\n    }\n    formatRunning(isRunning) {\n        return isRunning\n            ? '<i class=\"fa-solid fa-check text-success fa-lg\"></i> Yes'\n            : '<i class=\"fa-solid fa-xmark text-danger fa-lg\"></i> No';\n    }\n    formatPaused(isPaused) {\n        return isPaused\n            ? '<i class=\"fa-solid fa-pause text-danger fa-lg\"></i> Yes'\n            : '<i class=\"fa-solid fa-play text-info fa-lg\"></i> No';\n    }\n    updateLastChecked() {\n        const now = new Date();\n        const timeString = now.toLocaleTimeString([], { hour12: false });\n        this.updateListItem('Last Checked:', `<strong>Last Checked:</strong> ${timeString}`);\n    }\n    // ---------- Core Logic (AJAX) ----------\n    checkHealth() {\n        $.ajax({\n            url: '/dedicatedServerAPI/healthCheck',\n            type: 'POST',\n            data: { gameSaveId: this.saveGameId },\n            headers: { 'X-CSRF-Token': this.csrfToken },\n            success: (response) => {\n                if (response?.data?.health === 'healthy') {\n                    this.updateServerState();\n                }\n                else {\n                    console.warn('Health check failed:', response.message);\n                }\n            },\n            error: (xhr, status, error) => {\n                console.error('Health check error:', xhr.responseJSON?.error || error);\n            }\n        });\n    }\n    updateServerState() {\n        $.ajax({\n            url: '/dedicatedServerAPI/queryServerState',\n            type: 'POST',\n            data: { gameSaveId: this.saveGameId },\n            headers: { 'X-CSRF-Token': this.csrfToken },\n            success: (response) => {\n                const data = response?.data?.serverGameState;\n                if (!data)\n                    return;\n                const statusIndicator = document.querySelector('.status-indicator');\n                const statusBlink = document.querySelector('.status-indicator .status-blink');\n                const statusTitle = document.querySelector('h4.fw-bold');\n                statusIndicator?.classList.remove('offline');\n                statusIndicator?.classList.add('online');\n                statusBlink?.classList.remove('blink-offline');\n                statusBlink?.classList.add('blink-online');\n                if (statusTitle)\n                    statusTitle.textContent = 'Server Online';\n                this.updateListItem('Health:', `<strong>Health:</strong> ${this.formatHealth(true)}`);\n                this.updateLastChecked();\n                this.updateListItem('Session:', `<strong>Session:</strong> ${data.activeSessionName}`);\n                this.updateListItem('Players:', `<strong>Players:</strong> ${data.numConnectedPlayers}/${data.playerLimit}`);\n                this.updateListItem('Tech Tier:', `<strong>Tech Tier:</strong> ${data.techTier}`);\n                this.updateListItem('Phase:', `<strong>Phase:</strong> ${this.cleanGamePhase(data.gamePhase)}`);\n                this.updateListItem('Running:', `<strong>Running:</strong> ${this.formatRunning(data.isGameRunning)}`);\n                this.updateListItem('Paused:', `<strong>Paused:</strong> ${this.formatPaused(data.isGamePaused)}`);\n                this.updateListItem('Tick Rate:', `<strong>Tick Rate:</strong> ${data.averageTickRate.toFixed(2)} TPS`);\n                this.updateListItem('Total Duration:', `<strong>Total Duration:</strong> ${data.totalGameDuration}`);\n                this.updateListItem('Auto-load Session:', `<strong>Auto-load Session:</strong> ${data.autoLoadSessionName}`);\n            },\n            error: (xhr, status, error) => {\n                console.error('State update error:', xhr.responseJSON?.error || error);\n            }\n        });\n    }\n    // ---------- Stop Server Logic ----------\n    bindStopServerButton() {\n        const stopBtn = document.getElementById('confirmStopServerBtn');\n        if (!stopBtn)\n            return;\n        stopBtn.addEventListener('click', () => this.stopServer(stopBtn));\n    }\n    stopServer(button) {\n        const alertDiv = document.getElementById('stopServerAlert');\n        const modalBody = button.parentElement;\n        const shutdownDiv = document.getElementById('shutdownConfirmation');\n        alertDiv.classList.add('d-none');\n        alertDiv.classList.remove('alert-success', 'alert-danger');\n        alertDiv.textContent = '';\n        button.setAttribute('disabled', 'true');\n        button.innerHTML = '<span class=\"spinner-border spinner-border-sm\" role=\"status\" aria-hidden=\"true\"></span> Stopping...';\n        $.ajax({\n            url: '/dedicatedServerAPI/shutdown',\n            type: 'POST',\n            data: { gameSaveId: this.saveGameId },\n            headers: { 'X-CSRF-Token': this.csrfToken },\n            success: () => {\n                modalBody.classList.add('d-none');\n                shutdownDiv.classList.remove('d-none');\n                setTimeout(() => {\n                    // @ts-ignore (Bootstrap modal jQuery integration)\n                    $('#stopServerModal').modal('hide');\n                    location.reload();\n                }, 3000);\n            },\n            error: (xhr, status, error) => {\n                alertDiv.classList.remove('d-none');\n                alertDiv.classList.add('alert-danger');\n                alertDiv.textContent =\n                    'Error stopping server: ' + (xhr.responseJSON?.error || error);\n            },\n            complete: () => {\n                button.removeAttribute('disabled');\n                button.innerHTML = 'Yes, Stop Server';\n            },\n        });\n    }\n}\n\n\n//# sourceURL=webpack:///./public/TypeScript/DedicatedServerDashboard/Classes/ServerStatusUpdater.ts?\n}");

/***/ },

/***/ "./public/TypeScript/DedicatedServerDashboard/index.ts"
/*!*************************************************************!*\
  !*** ./public/TypeScript/DedicatedServerDashboard/index.ts ***!
  \*************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _Classes_ServerStatusUpdater__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Classes/ServerStatusUpdater */ \"./public/TypeScript/DedicatedServerDashboard/Classes/ServerStatusUpdater.ts\");\n\nwindow.ServerStatusUpdater = _Classes_ServerStatusUpdater__WEBPACK_IMPORTED_MODULE_0__.ServerStatusUpdater;\n\n\n//# sourceURL=webpack:///./public/TypeScript/DedicatedServerDashboard/index.ts?\n}");

/***/ }

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
/******/ 		if (!(moduleId in __webpack_modules__)) {
/******/ 			delete __webpack_module_cache__[moduleId];
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
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
/******/ 	var __webpack_exports__ = __webpack_require__("./public/TypeScript/DedicatedServerDashboard/index.ts");
/******/ 	
/******/ 	return __webpack_exports__;
/******/ })()
;
});