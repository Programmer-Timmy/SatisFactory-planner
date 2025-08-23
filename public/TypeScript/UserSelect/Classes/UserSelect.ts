import Role from "../Data/Role";
import SearchUserResponse from "../Data/SearchUserResponse";
import generateUserCard, {userCardType} from "../Templates/UserCard";
import User from "../Data/User";

class UserSelect {

    element: JQuery<HTMLElement>;
    form: JQuery<HTMLElement>;
    searchElement: JQuery<HTMLElement>;
    userList: JQuery<HTMLElement>;
    selectedUsersElement: JQuery<HTMLElement>;
    selectedUsersList: JQuery<HTMLElement>;
    allowedUsersElement?: JQuery<HTMLElement>;
    allowedUsersList?: JQuery<HTMLElement>;

    requestedUsers: User[] = [];
    allowedUsers: User[] = [];


    roles: Role[] = [];
    users: User[] = [];
    gameId?: number;
    constructor(element: JQuery<HTMLElement>, roles: Role[], form:JQuery<HTMLElement>,  gameId?: number, allowedUsers: User[] = [], requestedUsers: User[] = []) {
        this.element = element;
        this.form = form;
        this.searchElement = this.element.find("input[type='search']");
        this.userList = this.element.find(".users");
        this.selectedUsersElement = this.element.find(".requested-users-container");
        this.selectedUsersList = this.element.find(".requested-users-list");
        this.allowedUsersElement = this.element.find(".allowed-users-container");
        this.allowedUsersList = this.element.find(".allowed-users-list");

        console.log(allowedUsers, requestedUsers);
        if (allowedUsers) {
            const users = Object.values(allowedUsers);
            this.allowedUsers = users.map(user => ({
                id: user.id,
                username: user.username,

                role: roles.find(r => r.id === user.role_id)
            }));
        }

        if (requestedUsers) {
            const users = Object.values(requestedUsers);
            this.requestedUsers = users.map(user => ({
                id: user.id,
                username: user.username,

                role: roles.find(r => r.id === user.role_id)
            }));
            this.showSelectedUsers();
        }

        console.log(this.allowedUsers, this.requestedUsers);



        this.roles = roles;
        this.gameId = gameId;

        this.fetchUsers();
        this.applyEventListeners();
    }


    applyEventListeners(): void {
        this.searchElement.on("input", () => {
            const searchTerm = this.searchElement.val() as string;
            this.searchUsers(searchTerm);

        });

        this.userList.on("click", ".add_user", (event:JQuery.ClickEvent) => {
            this.handlerAddUserClick(event);
        });

        this.selectedUsersList.on("click", ".cancel_request", (event:JQuery.ClickEvent) => {
           this.handlerCancelRequestClick(event);
        });

        this.form.on("submit", (event:JQuery.SubmitEvent) => {
            this.onFormSubmit(event);
        });

    }

    private fetchUsers(): void {
        const ajaxUrl = '/searchUser';
        const token = $('meta[name="csrf-token"]').attr('content');
        if (!token) {
            console.error('CSRF token not found');
            return;
        }

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: { gameId: this.gameId, search: '' },
            headers: { 'X-CSRF-Token': token },
            dataType: 'json',
            success: (response: SearchUserResponse) => {
                if (response.success && response.data) {
                    this.users = response.data; // store all users locally
                    this.roles.forEach(role => role.selected = role.role_order === 3); // reset selected roles
                    this.searchUsers(''); // display initial users
                }
            },
            error: (xhr, status, error) => {
                console.error('Error fetching users:', error);
            }
        });
    }

    private searchUsers(searchTerm: string): void {
        const filtered = this.users.filter(user =>
            user.username.toLowerCase().includes(searchTerm.toLowerCase()) && !this.requestedUsers.some(u => u.id === user.id)
        );

        this.renderUsers(filtered.slice(0, 5));

        if (filtered.length > 5) {
            this.userList.append(`<p class="text-muted">And ${filtered.length - 5} more. Search for more users.</p>`);
        }
    }



    private renderUsers(users: User[]): void {
        this.userList.empty();

        if (users.length === 0) {
            this.userList.append('<h6 class="text-center">No users found</h6>');
            return;
        }

        users.forEach(user => {
            const userCardHtml = generateUserCard(user, this.roles);
            this.userList.append(userCardHtml);
        });
    }


    private handlerAddUserClick(event: JQuery.ClickEvent) {
        console.log(event);
        const button = $(event.currentTarget);
        const userCard = button.closest('.card');
        const userId = userCard.data('sp-user-id');
        // @ts-ignore
        const roleId = +userCard.find('select[name="role"]').val()
        const user = this.users.find(u => u.id === userId);
        if (!user) {
            console.error('User not found');
            return;
        }

        if (this.requestedUsers.some(u => u.id === user.id)) {
            console.warn('User already requested');
            return;
        }

        user.role = this.roles.find(r => r.id === roleId);
        this.requestedUsers.push(user);
        this.searchUsers(this.searchElement.val() as string);
        this.showSelectedUsers();
        console.log(this.requestedUsers);


    }

    private showSelectedUsers(): void {
        this.selectedUsersList.empty();

        if (this.requestedUsers.length === 0) {
            this.selectedUsersElement.find('.requested').addClass('hidden');
            return;
        }

        this.requestedUsers.forEach(user => {
            this.selectedUsersElement.find('.requested').removeClass('hidden');
            const userCardHtml = generateUserCard(user, this.roles, userCardType.requested);
            this.selectedUsersList.append(userCardHtml);
        });
    }

    private handlerCancelRequestClick(event: JQuery.ClickEvent) {
        const button = $(event.currentTarget);
        const userCard = button.closest('.card');
        const userId = userCard.data('sp-user-id');

        const userIndex = this.requestedUsers.findIndex(u => u.id === userId);

        if (userIndex === -1) {
            console.error('User not found in requested list');
            return;
        }

        this.requestedUsers.splice(userIndex, 1);
        this.users.find(u => u.id === userId)!.role = undefined; // reset role
        this.showSelectedUsers();
        this.searchUsers(this.searchElement.val() as string);
    }

    private onFormSubmit(event: JQuery.SubmitEvent) {
        const form = $(event.currentTarget);
        const requestedInput = form.find('input[name="requested_users"]');
        const allowedInput = form.find('input[name="allowed_users"]');

        if (requestedInput.length) {
            requestedInput.val(JSON.stringify(this.requestedUsers.map(user => ({
                id: user.id,
                roleId: user.role ? user.role.id : null
            }))));
        }

        if (allowedInput.length) {
            allowedInput.val(JSON.stringify(this.allowedUsers.map(user => ({
                id: user.id,
                roleId: user.role ? user.role.id : null
            }))));
        }
    }
}

export default UserSelect;