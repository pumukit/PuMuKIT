import { Controller } from '@hotwired/stimulus';
import { trans } from '@pumukit/translator';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { url: String, redirectUrl: String };

    submit(event) {
        event.preventDefault();

        const serverSelectedPath = $('#server_selected_path').val();
        if (serverSelectedPath === '') {
            no_success('Select path to upload files from server.');
            return;
        }

        $.ajax({
            url: this.urlValue,
            method: 'POST',
            data: {
                filePath: serverSelectedPath,
                profile: document.querySelector('input[name="profile_option"]:checked').value,
            },
        })
            .done(() => {
                window.location.href = this.redirectUrlValue;
            })
            .fail(() => {
                no_success(trans('Cannot upload directory on series'));
            });
    }
}
