<?php
enum Permission: string {
    // Savegame-related
    case SAVEGAME_READ        = "savegame_read";
    case SAVEGAME_EDIT        = "savegame_edit";
    case SAVEGAME_DELETE      = "savegame_delete";
    case SAVEGAME_INVITE      = "savegame_invite";  // invite collaborators
    case SAVEGAME_METADATA    = "savegame_metadata"; // edit metadata

    // Server-related
    case SERVER_VIEW          = "server_view";       // can see server status
    case SERVER_MANAGE        = "server_manage";     // start/stop server, change config
    case SERVER_DEPLOY        = "server_deploy";     // deploy savegame to server
}
