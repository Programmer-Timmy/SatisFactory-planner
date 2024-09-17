enum ActionType {
    Add = 'add',
    Delete = 'delete',
    Update = 'update',
    Calculate = 'calculate'
}

declare function updatePowerProduction(power : number) : void;

export class PowerProduction {
    private powerProduction: JQuery<HTMLElement>;
    private newInput: JQuery<HTMLElement>;

    constructor() {
        this.powerProduction = $('#powerProduction');
        this.newInput = $('#powerProductionCardNew');

    }

    async addNewPowerPlant(element: HTMLElement) {
        // Select display name, amount, and clock speed
        const name = this.newInput.find('#building option:selected').text();
        const amount = this.newInput.find('#amount').val() || '1';  // Default amount to '1'
        const clockSpeed = this.newInput.find('#clock_speed').val() || '100';  // Default clock speed to '100'

        // Validate inputs and return early if invalid
        if (!this.validateAndHandleInput(name, amount, clockSpeed)) {
            return;
        }

        const newCard = $(this.newCard());
        const json = await this.applyToDatabase(ActionType.Add, element);
        if (json.hasOwnProperty('error')) {
            console.error(json.error);
            return;
        }

        if (this.powerProduction.find('h5').length > 0) {
            this.powerProduction.find('h5').remove();
        }

        // update id to be unique
        const newId = `powerProductionCard${json.powerProductionId}`;
        newCard.attr('id', newId);

        // Update the cloned card with new input values
        newCard.find('.col-6 h6').text(name);
        newCard.find('#amount').val(amount.toString());
        newCard.find('#clock_speed').val(clockSpeed);

        // Append the updated card to the power production section
        this.powerProduction.append(newCard);

        // Reset the form for adding new power plants
        this.resetAddCard();

        // Apply event listeners to the new card
        this.applyEventListenersToCard(newCard);
        this.calculatePowerProduction();

    }

    async deletePowerPlant(element: HTMLElement) {
        const json = await this.applyToDatabase(ActionType.Delete, element);
        console.log(json);
        if (json.hasOwnProperty('error')) {
            console.error(json.error);
            return;
        }
        const card = $(element).closest('.card');
        card.remove();

        if (this.powerProduction.find('.card').length === 0) {
            this.powerProduction.append('<h5 class="text-center mt-2">No power production buildings added yet</h5>');
        }
        console.log('Deleted power plant');
        this.calculatePowerProduction();
    }

    async updatePowerPlant(element: HTMLElement) {
        const json = await this.applyToDatabase(ActionType.Update, element);
        if (json.hasOwnProperty('error')) {
            console.error(json.error);
            return;
        }
        console.log('Updated power plant');
        this.calculatePowerProduction();
    }

    async calculatePowerProduction() {
        const json = await this.applyToDatabase(ActionType.Calculate, document.createElement('div'));
        console.log(json);
        if (json.hasOwnProperty('error')) {
            console.error(json.error);
            return;
        }

        const totalPowerProduction = json.totalPowerProduction;
        updatePowerProduction(totalPowerProduction);
    }

    applyEventListenersToCard(card: JQuery<HTMLElement>) {
        const deleteButton = card.find('.deletePowerProduction');
        const updateInputs = card.find('input');

        if (deleteButton) {
            deleteButton.on('click', (event) => {
                this.deletePowerPlant(event.currentTarget);
            });
        }

        if (updateInputs) {
            updateInputs.on('change', (event) => {
                this.updatePowerPlant(event.currentTarget);
            });
        }
    }

    applyEventListeners() {
        const addButton = this.newInput.find('button');
        const deleteButtons = this.powerProduction.find('.deletePowerProduction');
        const updateInputs = this.powerProduction.find('input');

        if (addButton) {
            addButton.on('click', (event) => {
                this.addNewPowerPlant(event.currentTarget);
            });
        }

        if (deleteButtons) {
            deleteButtons.on('click', (event) => {
                this.deletePowerPlant(event.currentTarget);
            });
        }

        if (updateInputs) {
            updateInputs.on('change', (event) => {
                this.updatePowerPlant(event.currentTarget);
            });
        }

    }

    checkAndHandleNoPowerPlants() {
        if (this.powerProduction.find('.card').length === 0) {
            this.powerProduction.append('<h6>No power plants added yet</h6>');
        }
    }

    newCard(): JQuery<HTMLElement> {
        // Create the card element
        const card = document.createElement('div');
        card.className = 'card mb-2';

        const cardBody = document.createElement('div');
        cardBody.className = 'card-body p-2 row d-flex justify-content-between align-items-center';

        // Create the content of the card
        cardBody.innerHTML = `
        <div class="col-6">
            <h6 class="m-0"></h6>
        </div>
        <div class="col-2">
            <input type="number" class="form-control" id="amount" name="amount"
                   min="1" max="1000">
        </div>
        <div class="col-2">
            <input type="number" class="form-control" id="clock_speed"
                   name="clock_speed" min="1" max="250" step="any">
        </div>
        <div class="col-2 text-end">
            <button type="button" class="btn btn-danger deletePowerProduction">
            Delete
            </button>
        </div>
    `;
        card.appendChild(cardBody);

        return $(card);
    }

    resetAddCard() {
        this.newInput.find('#amount').val('1');
        this.newInput.find('#clock_speed').val('100');
        // Reset select to the first option
        this.newInput.find('select').prop('selectedIndex', 0);
    }

    validateAndHandleInput(name: string, amount: string | number | string[], clockSpeed: string | number | string[]) {
        const addInvalidClass = (selector: string) => this.newInput.find(selector).addClass('is-invalid');
        const removeInvalidClass = (selector: string) => this.newInput.find(selector).removeClass('is-invalid');

        let error: boolean = false;
        // If validation passed, clear any error states
        removeInvalidClass('#building');
        removeInvalidClass('#amount');
        removeInvalidClass('#clock_speed');

        // Check for empty or default values
        if (!name || name === 'Select a building') {
            addInvalidClass('#building');
            error = true;
        }

        // Validate amount
        const parsedAmount = parseFloat(Array.isArray(amount) ? amount[0] : amount.toString());
        if (isNaN(parsedAmount) || parsedAmount < 1 || parsedAmount > 1000) {
            addInvalidClass('#amount');
            error = true;
        }

        // Validate clock speed
        const parsedClockSpeed = parseFloat(Array.isArray(clockSpeed) ? clockSpeed[0] : clockSpeed.toString());
        if (isNaN(parsedClockSpeed) || parsedClockSpeed < 1 || parsedClockSpeed > 250) {
            addInvalidClass('#clock_speed');
            error = true;
        }

        return !error;
    }

    // apply to database using ajax
    async applyToDatabase(action: ActionType, element: HTMLElement): Promise<any> {
        const card = $(element).closest('.card');
        const cardId = card.attr('id') || '';
        const powerProductionId = cardId.replace('powerProductionCard', '');

        const url = new URL(window.location.href);
        const gameSaveId = url.searchParams.get('id');

        switch (action) {
            case ActionType.Add:
                const buildingId = card.find('#building').val();
                const amount = card.find('#amount').val();
                const clockSpeed = card.find('#clock_speed').val();

                return new Promise(function (resolve, reject) {
                    $.ajax({
                        url: 'powerProduction/add',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            gameSaveId: gameSaveId,
                            buildingId: buildingId,
                            amount: amount,
                            clockSpeed: clockSpeed
                        },
                        success: function (data) {
                            resolve(data)
                        }
                    });
                });
            case ActionType.Delete:
                return new Promise(function (resolve, reject) {
                    $.ajax({
                        url: 'powerProduction/delete',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            powerProductionId: powerProductionId
                        },
                        success: function (data) {
                            resolve(data)
                        }
                    });
                });

            case ActionType.Update:

                const amount1 = card.find('#amount').val();
                const clockSpeed1 = card.find('#clock_speed').val();

                return new Promise(function (resolve, reject) {
                    $.ajax({
                        url: 'powerProduction/update',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            powerProductionId: powerProductionId,
                            amount: amount1,
                            clockSpeed: clockSpeed1
                        },
                        success: function (data) {
                            console.log(data);
                            resolve(data)
                        }
                    });
                });
            case ActionType.Calculate:
                return new Promise(function (resolve, reject) {
                    $.ajax({
                        url: 'powerProduction/calculate',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            gameSaveId: gameSaveId
                        },
                        success: function (data) {
                            resolve(data)
                        }
                    });
                });
        }
    }
}
