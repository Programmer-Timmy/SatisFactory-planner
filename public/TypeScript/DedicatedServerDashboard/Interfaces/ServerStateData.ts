export interface ServerStateData {
    activeSessionName: string;
    numConnectedPlayers: number;
    playerLimit: number;
    techTier: string;
    gamePhase: string;
    isGameRunning: boolean;
    isGamePaused: boolean;
    averageTickRate: number;
    totalGameDuration: string;
    autoLoadSessionName: string;
}