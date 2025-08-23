import User from "./User";

interface SearchUserResponse {
    success: boolean;
    error?: string;
    length?: number;
    data?: User[];
}

export default SearchUserResponse;