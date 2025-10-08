import {ServerStateData} from "./ServerStateData";

export interface StateResponse {
    data?: { serverGameState: ServerStateData };
}