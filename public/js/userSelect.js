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
		exports["userSelect"] = factory();
	else
		root["userSelect"] = factory();
})(this, () => {
return /******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./public/TypeScript/UserSelect/Classes/UserSelect.ts"
/*!************************************************************!*\
  !*** ./public/TypeScript/UserSelect/Classes/UserSelect.ts ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__)\n/* harmony export */ });\n/* harmony import */ var _Templates_UserCard__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../Templates/UserCard */ \"./public/TypeScript/UserSelect/Templates/UserCard.ts\");\n\nclass UserSelect {\n    element;\n    form;\n    searchElement;\n    userList;\n    selectedUsersElement;\n    selectedUsersList;\n    allowedUsersElement;\n    allowedUsersList;\n    requestedUsers = [];\n    allowedUsers = [];\n    roles = [];\n    users = [];\n    gameId;\n    constructor(element, roles, form, gameId, allowedUsers = [], requestedUsers = []) {\n        this.element = element;\n        this.form = form;\n        this.searchElement = this.element.find(\"input[type='search']\");\n        this.userList = this.element.find(\".users\");\n        this.selectedUsersElement = this.element.find(\".requested-users-container\");\n        this.selectedUsersList = this.element.find(\".requested-users-list\");\n        this.allowedUsersElement = this.element.find(\".allowed-users-container\");\n        this.allowedUsersList = this.element.find(\".allowed-users-list\");\n        this.roles = roles;\n        if (allowedUsers) {\n            const users = Object.values(allowedUsers);\n            this.allowedUsers = users.map(user => ({\n                id: user.id,\n                username: user.username,\n                role: roles.find(r => r.id === user.role_id)\n            }));\n        }\n        if (requestedUsers) {\n            const users = Object.values(requestedUsers);\n            this.requestedUsers = users.map(user => ({\n                id: user.id,\n                username: user.username,\n                role: roles.find(r => r.id === user.role_id)\n            }));\n        }\n        this.roles = roles;\n        this.gameId = gameId;\n        this.fetchUsers();\n        this.showSelectedAllowedUsers();\n        this.applyEventListeners();\n    }\n    applyEventListeners() {\n        this.searchElement.on(\"input\", () => {\n            const searchTerm = this.searchElement.val();\n            this.searchUsers(searchTerm);\n        });\n        this.userList.on(\"click\", \".add_user\", (event) => {\n            this.handlerAddUserClick(event);\n        });\n        this.element.on(\"change\", \"select[name='role']\", (event) => {\n            this.handleChangeRole(event);\n        });\n        this.selectedUsersList.on(\"click\", \".cancel_request\", (event) => {\n            this.handlerCancelRequestClick(event);\n        });\n        this.allowedUsersList?.on(\"click\", \".remove_user\", (event) => {\n            this.handleRemoveAllowedUserClick(event);\n        });\n        this.form.on(\"submit\", (event) => {\n            this.onFormSubmit(event);\n        });\n    }\n    fetchUsers() {\n        const ajaxUrl = '/searchUser';\n        const token = $('meta[name=\"csrf-token\"]').attr('content');\n        if (!token) {\n            console.error('CSRF token not found');\n            return;\n        }\n        $.ajax({\n            url: ajaxUrl,\n            type: 'POST',\n            data: { gameId: this.gameId, search: '' },\n            headers: { 'X-CSRF-Token': token },\n            dataType: 'json',\n            success: (response) => {\n                if (response.success && response.data) {\n                    this.users = response.data; // store all users locally\n                    this.roles.forEach(role => role.selected = role.role_order === 3); // reset selected roles\n                    this.searchUsers(''); // display initial users\n                }\n            },\n            error: (xhr, status, error) => {\n                console.error('Error fetching users:', error);\n            }\n        });\n    }\n    searchUsers(searchTerm) {\n        const filtered = this.users.filter(user => user.username.toLowerCase().includes(searchTerm.toLowerCase()) && !this.requestedUsers.some(u => u.id === user.id) && !this.allowedUsers.some(u => u.id === user.id));\n        this.renderUsers(filtered.slice(0, 5));\n        if (filtered.length > 5) {\n            this.userList.append(`<p class=\"text-muted\">And ${filtered.length - 5} more. Search for more users.</p>`);\n        }\n    }\n    renderUsers(users) {\n        this.userList.empty();\n        if (users.length === 0) {\n            this.userList.append('<h6 class=\"text-center\">No users found</h6>');\n            return;\n        }\n        users.forEach(user => {\n            const userCardHtml = (0,_Templates_UserCard__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(user, this.roles);\n            this.userList.append(userCardHtml);\n        });\n    }\n    handlerAddUserClick(event) {\n        const button = $(event.currentTarget);\n        const userCard = button.closest('.card');\n        const userId = userCard.data('sp-user-id');\n        // @ts-ignore\n        const roleId = +userCard.find('select[name=\"role\"]').val();\n        const user = this.users.find(u => u.id === userId);\n        if (!user) {\n            return;\n        }\n        if (this.requestedUsers.some(u => u.id === user.id)) {\n            return;\n        }\n        user.role = this.roles.find(r => r.id === roleId);\n        this.requestedUsers.push(user);\n        this.searchUsers(this.searchElement.val());\n        this.showSelectedAllowedUsers();\n        this.saveState();\n    }\n    showSelectedAllowedUsers() {\n        this.selectedUsersList.empty();\n        if (this.requestedUsers.length === 0) {\n            this.selectedUsersElement.find('.requested').addClass('hidden');\n        }\n        else {\n            this.requestedUsers.forEach(user => {\n                this.selectedUsersElement.find('.requested').removeClass('hidden');\n                const userCardHtml = (0,_Templates_UserCard__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(user, this.roles, _Templates_UserCard__WEBPACK_IMPORTED_MODULE_0__.userCardType.requested);\n                this.selectedUsersList.append(userCardHtml);\n            });\n        }\n        this.allowedUsersList?.empty();\n        if (this.allowedUsers && this.allowedUsers.length > 0) {\n            this.allowedUsersElement?.find('.allowed').removeClass('hidden');\n            this.allowedUsers.forEach(user => {\n                const userCardHtml = (0,_Templates_UserCard__WEBPACK_IMPORTED_MODULE_0__[\"default\"])(user, this.roles, _Templates_UserCard__WEBPACK_IMPORTED_MODULE_0__.userCardType.remove);\n                this.allowedUsersList?.append(userCardHtml);\n            });\n        }\n        else {\n            this.allowedUsersElement?.find('.allowed').addClass('hidden');\n        }\n    }\n    handlerCancelRequestClick(event) {\n        const button = $(event.currentTarget);\n        const userCard = button.closest('.card');\n        const userId = userCard.data('sp-user-id');\n        const userIndex = this.requestedUsers.findIndex(u => u.id === userId);\n        if (userIndex === -1) {\n            console.error('User not found in requested list');\n            return;\n        }\n        this.requestedUsers.splice(userIndex, 1);\n        this.users.find(u => u.id === userId).role = undefined; // reset role\n        this.showSelectedAllowedUsers();\n        this.searchUsers(this.searchElement.val());\n        this.saveState();\n    }\n    onFormSubmit(event) {\n        const form = $(event.currentTarget);\n        const requestedInput = form.find('input[name=\"requested_users\"]');\n        const allowedInput = form.find('input[name=\"allowed_users\"]');\n        if (requestedInput.length) {\n            requestedInput.val(JSON.stringify(this.requestedUsers.map(user => ({\n                id: user.id,\n                roleId: user.role ? user.role.id : null\n            }))));\n        }\n        if (allowedInput.length) {\n            allowedInput.val(JSON.stringify(this.allowedUsers.map(user => ({\n                id: user.id,\n                roleId: user.role ? user.role.id : null\n            }))));\n        }\n    }\n    handleChangeRole(event) {\n        const select = $(event.currentTarget);\n        const userCard = select.closest('.card');\n        const userId = userCard.data('sp-user-id');\n        // @ts-ignore\n        const roleId = +select.val();\n        const user = this.requestedUsers.find(u => u.id === userId) || this.allowedUsers.find(u => u.id === userId);\n        if (!user) {\n            return;\n        }\n        const newRole = this.roles.find(r => r.id === roleId);\n        if (!newRole) {\n            console.error('Role not found');\n            return;\n        }\n        user.role = newRole;\n        this.saveState();\n    }\n    saveState() {\n        if (!this.gameId) {\n            return;\n        }\n        $.ajax({\n            url: '/userPermissions',\n            type: 'POST',\n            data: {\n                gameId: this.gameId,\n                requestedUsers: JSON.stringify(this.requestedUsers.map(user => ({\n                    id: user.id,\n                    roleId: user.role ? user.role.id : null\n                }))),\n                allowedUsers: JSON.stringify(this.allowedUsers.map(user => ({\n                    id: user.id,\n                    roleId: user.role ? user.role.id : null\n                })))\n            },\n            headers: { 'X-CSRF-Token': $('meta[name=\"csrf-token\"]').attr('content') || '' },\n            dataType: 'json',\n            success: (response) => {\n                if (response.success) {\n                    const allowedUsers = response.data?.allowedUsers || [];\n                    this.allowedUsers = allowedUsers.map((user) => ({\n                        id: user.id,\n                        username: user.username,\n                        role: this.roles.find(r => r.id === user.role_id)\n                    }));\n                    const requestedUsers = response.data?.requestedUsers || [];\n                    this.requestedUsers = requestedUsers.map((user) => ({\n                        id: user.id,\n                        username: user.username,\n                        role: this.roles.find(r => r.id === user.role_id)\n                    }));\n                    this.showSelectedAllowedUsers();\n                    this.searchUsers(this.searchElement.val());\n                }\n                else {\n                    console.error('Error saving state:', response.error);\n                }\n            },\n            error: (xhr, status, error) => {\n                console.error('Error saving state:', error);\n            }\n        });\n    }\n    handleRemoveAllowedUserClick(event) {\n        const button = $(event.currentTarget);\n        const userCard = button.closest('.card');\n        const userId = userCard.data('sp-user-id');\n        const userIndex = this.allowedUsers.findIndex(u => u.id === userId);\n        if (userIndex === -1) {\n            console.error('User not found in allowed list');\n            return;\n        }\n        this.allowedUsers.splice(userIndex, 1);\n        this.users.find(u => u.id === userId).role = undefined; // reset role\n        this.showSelectedAllowedUsers();\n        this.searchUsers(this.searchElement.val());\n        this.saveState();\n    }\n}\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (UserSelect);\n\n\n//# sourceURL=webpack:///./public/TypeScript/UserSelect/Classes/UserSelect.ts?\n}");

/***/ },

/***/ "./public/TypeScript/UserSelect/Templates/UserCard.ts"
/*!************************************************************!*\
  !*** ./public/TypeScript/UserSelect/Templates/UserCard.ts ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   \"default\": () => (__WEBPACK_DEFAULT_EXPORT__),\n/* harmony export */   userCardType: () => (/* binding */ userCardType)\n/* harmony export */ });\nvar userCardType;\n(function (userCardType) {\n    userCardType[userCardType[\"add\"] = 0] = \"add\";\n    userCardType[userCardType[\"requested\"] = 1] = \"requested\";\n    userCardType[userCardType[\"remove\"] = 2] = \"remove\";\n})(userCardType || (userCardType = {}));\nfunction generateUserCard(user, roles, type = userCardType.add) {\n    roles.sort((a, b) => a.role_order - b.role_order);\n    const roleOptions = roles.map(role => {\n        return `\r\n        <option value=\"${role.id}\" data-desc=\"${role.description}\" \r\n          ${(role.selected && type == userCardType.add) || (user.role?.id === role.id) ? \"selected\" : \"\"}>\r\n          ${role.name} - ${role.description}\r\n        </option>\r\n    `;\n    }).join('');\n    // Determine button properties based on type\n    let btnClass = '';\n    let btnText = '';\n    let btnDisabled = false;\n    switch (type) {\n        case userCardType.requested:\n            btnClass = 'btn btn-warning btn-sm px-3 cancel_request';\n            btnText = 'Cancel';\n            btnDisabled = false;\n            break;\n        case userCardType.remove:\n            btnClass = 'btn btn-danger btn-sm px-3 remove_user';\n            btnText = 'Remove';\n            btnDisabled = false;\n            break;\n        default: // add\n            btnClass = 'btn btn-success btn-sm px-3 add_user';\n            btnText = 'Add';\n            btnDisabled = false;\n            break;\n    }\n    return `\r\n    <div class=\"card shadow-sm rounded-3 mb-2\" data-sp-user-id=\"${user.id}\">\r\n        <div class=\"card-body d-flex justify-content-between align-items-center\">\r\n            <!-- Username -->\r\n            <div style=\"width: 300px;\" class=\"text-truncate\">\r\n                <h6 class=\"mb-0 fw-semibold text-primary text-truncate\">\r\n                    ${user.username}\r\n                </h6>\r\n            </div>\r\n\r\n            <!-- Role select with description -->\r\n            <div class=\"mx-3 w-100\" style=\"flex-grow: 1;\">\r\n                <select name=\"role\" class=\"form-select form-select-sm text-truncate\">\r\n                    ${roleOptions}\r\n                </select>\r\n            </div>\r\n\r\n            <!-- Single dynamic button -->\r\n            <button type=\"button\" data-sp-type=\"\" class=\"${btnClass}\" ${btnDisabled ? 'disabled' : ''}>\r\n                ${btnText}\r\n            </button>\r\n        </div>\r\n    </div>\r\n    `;\n}\n/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (generateUserCard);\n\n\n//# sourceURL=webpack:///./public/TypeScript/UserSelect/Templates/UserCard.ts?\n}");

/***/ },

/***/ "./public/TypeScript/UserSelect/index.ts"
/*!***********************************************!*\
  !*** ./public/TypeScript/UserSelect/index.ts ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _Classes_UserSelect__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Classes/UserSelect */ \"./public/TypeScript/UserSelect/Classes/UserSelect.ts\");\n\n// Make UserSelect globally accessible\nwindow.UserSelect = _Classes_UserSelect__WEBPACK_IMPORTED_MODULE_0__[\"default\"];\n\n\n//# sourceURL=webpack:///./public/TypeScript/UserSelect/index.ts?\n}");

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
/******/ 	var __webpack_exports__ = __webpack_require__("./public/TypeScript/UserSelect/index.ts");
/******/ 	
/******/ 	return __webpack_exports__;
/******/ })()
;
});