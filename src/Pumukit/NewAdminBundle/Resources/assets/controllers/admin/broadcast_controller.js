import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['submit', 'password', 'fail', 'empty', 'passwordPanel', 'groupsPanel', 'lists', 'current', 'mm', 'warning'];
    static values = {
        url: String,
        infoUrl: String,
        passwordType: String,
        groupsType: String,
        description: String,
        messages: Object,
    };

    type(event) {
        const type = event.currentTarget.value;
        if (this.passwordTypeValue === type) {
            this.groupsPanelTarget.classList.add('pumukitnewadmin-broadcast-hidden');
            this.passwordPanelTarget.classList.remove('pumukitnewadmin-broadcast-hidden');
            this.updatePasswordButton();
        } else if (this.groupsTypeValue === type) {
            this.passwordPanelTarget.classList.add('pumukitnewadmin-broadcast-hidden');
            this.groupsPanelTarget.classList.remove('pumukitnewadmin-broadcast-hidden');
            this.updateGroupsButton();
        } else {
            this.passwordPanelTarget.classList.add('pumukitnewadmin-broadcast-hidden');
            this.groupsPanelTarget.classList.add('pumukitnewadmin-broadcast-hidden');
            this.submitTarget.disabled = false;
        }
    }

    check() {
        const [first, second] = this.passwords();
        const empty = '' === first || '' === second;
        const valid = !empty && first === second;
        $(this.failTarget).toggle(!empty && !valid);
        $(this.emptyTarget).toggle(empty);
        this.submitTarget.disabled = !valid;

        return valid;
    }

    showHide() {
        const type = 'text' === this.passwordTargets[0].type ? 'password' : 'text';
        this.passwordTargets.forEach((input) => {
            input.type = type;
        });
    }

    generate() {
        const length = Math.floor(Math.random() * 7 + 6);
        const password = Array.from({ length }, () => String.fromCharCode(Math.floor(Math.random() * 26 + 65))).join('');
        $(this.failTarget).hide();
        $(this.emptyTarget).hide();
        this.passwordTargets.forEach((input) => {
            input.value = password;
        });
        this.updatePasswordButton();
    }

    groupsChanged() {
        this.updateGroupsButton();
    }

    checkOne() {
        const checked = this.checkedIds().length;
        $(this.warningTarget).toggle(checked === this.mmTargets.length);
        this.submitTarget.disabled = 0 === checked;
    }

    selectAll(event) {
        event.preventDefault();
        const { checked } = event.params;
        this.mmTargets.forEach((input) => {
            input.checked = checked;
        });
        $(this.warningTarget).toggle(checked);
        this.submitTarget.disabled = !checked;
    }

    reset(event) {
        event.preventDefault();
        $.ajax({ url: this.infoUrlValue, type: 'GET' })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${xhr.responseText}`))
            .done((response) => {
                const radio = this.element.querySelector(`input[type=radio][value="${response.type}"]`);
                if (radio) {
                    radio.checked = true;
                }
                this.passwordTargets.forEach((input) => {
                    input.value = response.password;
                });
                const { addGroups, deleteGroups } = response.groups;
                this.dispatch('load', { target: this.listsTarget, prefix: 'admin--group-lists', detail: { add: addGroups, delete: deleteGroups } });
                if (0 !== Object.keys(addGroups).length + Object.keys(deleteGroups).length) {
                    this.updateGroupsButton();
                }
            });
    }

    save(event) {
        event.preventDefault();
        if (this.hasMmTarget && 0 === this.checkedIds().length) {
            this.submitTarget.disabled = true;

            return;
        }
        $('#myModal').modal('hide');
        const type = this.element.querySelector('input[type=radio]:checked')?.value;
        if (this.passwordTypeValue === type && !this.check()) {
            window.no_success(this.messagesValue.mismatch);

            return;
        }
        const data = {
            type,
            password: this.passwords()[0],
            addGroups: this.ids('a.add-broadcast-group-item'),
            deleteGroups: this.ids('a.delete-broadcast-group-item'),
        };
        if (this.hasMmTarget) {
            data.ids = this.checkedIds();
        }
        $.ajax({ url: this.urlValue, type: 'POST', data })
            .fail((xhr, textStatus, errorThrown) => window.no_success(`${errorThrown}: ${xhr.responseText}`))
            .done((response) => {
                if (undefined !== response.descriptioni18n) {
                    $(this.descriptionValue).text(response.descriptioni18n);
                }
                window.success(this.messagesValue.saved);
            });
    }

    updatePasswordButton() {
        const [first, second] = this.passwords();
        this.submitTarget.disabled = '' === first || '' === second || first !== second;
    }

    updateGroupsButton() {
        this.submitTarget.disabled = !this.hasCurrentTarget || '' === this.currentTarget.innerHTML.trim();
    }

    checkedIds() {
        return this.mmTargets.filter((input) => input.checked).map((input) => input.id);
    }

    passwords() {
        return this.passwordTargets.map((input) => input.value);
    }

    ids(selector) {
        return [...this.element.querySelectorAll(selector)].map((item) => item.id);
    }
}
