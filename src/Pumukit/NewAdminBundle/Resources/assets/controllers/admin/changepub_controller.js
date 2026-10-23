import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { url: String, target: String };

    checkAll(event) {
        this.find('input.checkbox_change_pub_mms').prop('checked', event.currentTarget.checked);
    }

    checkOne(event) {
        if (!event.currentTarget.checked) {
            this.find('input.checkbox_change_pub_mms_all').prop('checked', false);
        }
    }

    select(event) {
        event.preventDefault();
        this.find('input.checkbox_change_pub_mms_all, input.checkbox_change_pub_mms').prop('checked', event.params.checked);
    }

    status(event) {
        const status = event.currentTarget.value;
        if (0 !== status.length) {
            this.find('select.status_change_pub').val(status);
        }
    }

    checkChannel(event) {
        this.find(`input.pub_channel_input_checkbox.pub_channel_change_pub_${event.params.channel}`).prop('checked', event.currentTarget.checked);
    }

    checkOneChannel(event) {
        if (!event.currentTarget.checked) {
            this.find(`input.checkbox_change_pub_channel_${event.params.channel}`).prop('checked', false);
        }
    }

    submit(event) {
        event.preventDefault();
        $('#myModal').modal('hide');
        const channels = this.find('input.checkbox_all_channels').map((index, input) => input.id).get();
        const values = {};
        this.find('.checkbox_change_pub_mms:checked').each((index, input) => {
            const mmId = input.id;
            values[mmId] = {
                status: this.find(`#status_change_pub_${mmId}`).val(),
                channels: Object.fromEntries(channels.map((channel) => [channel, this.find(`#pub_channel_change_pub_${channel}_mm_${mmId}`).prop('checked')])),
            };
        });
        $(this.targetValue).load(this.urlValue, { _method: 'POST', values });
    }

    find(selector) {
        return $(this.element).find(selector);
    }
}
