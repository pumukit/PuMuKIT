import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['available', 'current'];
    static values = { name: String, showCodes: Boolean, editable: { type: Boolean, default: true }, message: String };

    select(event) {
        event.preventDefault();
        if (!this.editableValue) {
            return;
        }
        $(event.currentTarget).siblings('.active').removeClass('active').end().addClass('active');
    }

    toggle(event) {
        event.preventDefault();
        this.move(event.currentTarget);
    }

    moveFromCurrent(event) {
        event.preventDefault();
        this.moveActive(this.currentTarget);
    }

    moveFromAvailable(event) {
        event.preventDefault();
        this.moveActive(this.availableTarget);
    }

    moveActive(list) {
        if (!this.editableValue) {
            window.alert(this.messageValue);

            return;
        }
        list.querySelectorAll(':scope > a.active').forEach((item) => this.move(item));
    }

    move(item) {
        if (!this.editableValue) {
            window.alert(this.messageValue);

            return;
        }
        const toCurrent = this.availableTarget.contains(item);
        const list = toCurrent ? 'add' : 'delete';
        item.classList.remove('active', 'notload', `add-${this.nameValue}-group-item`, `delete-${this.nameValue}-group-item`);
        item.classList.add(`${list}-${this.nameValue}-group-item`);
        item.id = `${list}_${this.nameValue}_group_${item.dataset.groupId}`;
        (toCurrent ? this.currentTarget : this.availableTarget).append(item);
        this.dispatch('changed');
    }

    load(event) {
        const { add, delete: available } = event.detail;
        this.currentTarget.replaceChildren(...Object.entries(add).map(([id, group]) => this.item('add', id, group)));
        this.availableTarget.replaceChildren(...Object.entries(available).map(([id, group]) => this.item('delete', id, group)));
    }

    item(list, id, group) {
        const item = document.createElement('a');
        item.href = '#';
        item.id = `${list}_${this.nameValue}_group_${id}`;
        item.className = `list-group-item ${list}-${this.nameValue}-group-item pumukitnewadmin-trim-row group_${group.key}`;
        item.dataset.groupId = id;
        item.dataset.action = `click->${this.identifier}#select dblclick->${this.identifier}#toggle`;
        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-users';
        item.append(icon, ' ', this.showCodesValue ? `${group.key} - ${group.name} - (${group.origin})` : group.name);

        return item;
    }
}
