interface Role {
    id: number;
    name: string;
    description: string;
    role_order: number;
    selected?: boolean; // not send by the api but used in the UI
}

export default Role;