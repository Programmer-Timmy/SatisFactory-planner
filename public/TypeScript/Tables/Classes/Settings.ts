import {Ajax} from "./Functions/Ajax";

export class Settings {
    public autoImportExport: boolean = true;
    public autoPowerMachine: boolean = true;
    public autoSave: boolean = false;


    constructor() {
        this.addEventListeners();
        this.applyChanges();
    }

    public applyChanges() {
        const url = new URL(window.location.href);
        const productionLineId = parseInt( url.pathname.split('/')[2]);

        const autoImportExportCheckbox: HTMLInputElement = document.getElementById('auto_import_export') as HTMLInputElement;
        const autoPowerMachineCheckbox: HTMLInputElement = document.getElementById('auto_power_machine') as HTMLInputElement;

        if (autoImportExportCheckbox) {
            this.autoImportExport = autoImportExportCheckbox.checked;
        }

        if (autoPowerMachineCheckbox) {
            this.autoPowerMachine = autoPowerMachineCheckbox.checked;
        }

        Ajax.saveSettings(productionLineId, this.autoImportExport, this.autoPowerMachine, this.autoSave);
    }

    public addEventListeners() {
        const autoImportExportCheckbox: HTMLInputElement = document.getElementById('auto_import_export') as HTMLInputElement;
        const autoPowerMachineCheckbox: HTMLInputElement = document.getElementById('auto_power_machine') as HTMLInputElement;

        if (autoImportExportCheckbox) {
            // when on click give the element
            autoImportExportCheckbox.addEventListener('change', () => {
                $('#auto_import_export').parent().parent().tooltip('hide');
                this.applyChanges();
            });
        }

        if (autoPowerMachineCheckbox) {
            autoPowerMachineCheckbox.addEventListener('change', () => {
                $('#auto_power_machine').parent().parent().tooltip('hide');
                this.applyChanges();
            });
        }
    }


}