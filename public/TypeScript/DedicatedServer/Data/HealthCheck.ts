class HealthData {
    public health: string;
    public serverCustomData: string;

    constructor(Health: string = "", serverCustomData: string = "") {
        this.health = Health;
        this.serverCustomData = serverCustomData;
    }
}

export class HealthCheck {
    public status: string;
    public data: HealthData;

    constructor(Health: string, data: HealthData) {
        this.status = Health;
        this.data = data;
    }
}