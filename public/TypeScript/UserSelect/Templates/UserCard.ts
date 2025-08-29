import User from "../Data/User";
import Role from "../Data/Role";

export enum userCardType {
    add,
    requested,
    remove
}

function generateUserCard(user: User, roles: Role[], type: userCardType = userCardType.add): string {
    roles.sort((a, b) => a.role_order - b.role_order);

    const roleOptions = roles.map(role => {
        return `
        <option value="${role.id}" data-desc="${role.description}" 
          ${(role.selected && type == userCardType.add) || (user.role?.id === role.id) ? "selected" : ""}>
          ${role.name} - ${role.description}
        </option>
    `}).join('');

    // Determine button properties based on type
    let btnClass = '';
    let btnText = '';
    let btnDisabled = false;

    switch (type) {
        case userCardType.requested:
            btnClass = 'btn btn-warning btn-sm px-3 cancel_request';
            btnText = 'Cancel';
            btnDisabled = false;
            break;
        case userCardType.remove:
            btnClass = 'btn btn-danger btn-sm px-3 remove_user';
            btnText = 'Remove';
            btnDisabled = false;
            break;
        default: // add
            btnClass = 'btn btn-success btn-sm px-3 add_user';
            btnText = 'Add';
            btnDisabled = false;
            break;
    }

    return `
    <div class="card shadow-sm rounded-3 mb-2" data-sp-user-id="${user.id}">
        <div class="card-body d-flex justify-content-between align-items-center">
            <!-- Username -->
            <div style="width: 300px;" class="text-truncate">
                <h6 class="mb-0 fw-semibold text-primary text-truncate">
                    ${user.username}
                </h6>
            </div>

            <!-- Role select with description -->
            <div class="mx-3 w-100" style="flex-grow: 1;">
                <select name="role" class="form-select form-select-sm text-truncate">
                    ${roleOptions}
                </select>
            </div>

            <!-- Single dynamic button -->
            <button type="button" data-sp-type="" class="${btnClass}" ${btnDisabled ? 'disabled' : ''}>
                ${btnText}
            </button>
        </div>
    </div>
    `;
}

export default generateUserCard;
