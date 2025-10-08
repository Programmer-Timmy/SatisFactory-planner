import {SaveHeader} from "./SaveHeader";

export interface Session {
    sessionName: string;
    saveHeaders: SaveHeader[];
}