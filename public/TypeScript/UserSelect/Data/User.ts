import Role from "./Role";

interface User {
    id: number;
    username: string;

    role?: Role; // not send by the api but used in the UI
    role_id?: number; // role id when sending to the api
}

export default User;