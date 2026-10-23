import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['form', 'url', 'requiredUrl', 'urlError', 'fileError', 'fileGroup', 'urlGroup'];
    static values = {
        refresh: Array,
        successMessage: String,
        emptyMessage: String,
        invalidUrlMessage: String,
    };

    submit(event) {
        event.preventDefault();
        this.hideErrors();

        const url = this.alternativeUrl();
        if (null !== url) {
            if (this.isUrl(url)) {
                this.send();
            } else {
                this.invalidUrl();
            }

            return;
        }

        const uploader = this.uploader();
        if (!uploader?.uppy?.getFiles().length) {
            this.emptyFile();

            return;
        }

        if (this.hasRequiredUrlTarget && !this.isUrl(this.requiredUrlTarget.value)) {
            this.invalidUrl();

            return;
        }

        uploader.upload();
    }

    mode(event) {
        const urlMode = 'url' === event.target.value;
        this.fileGroupTargets.forEach((element) => {
            element.hidden = urlMode;
        });
        this.urlGroupTargets.forEach((element) => {
            element.hidden = !urlMode;
        });
    }

    uploaded(event) {
        this.done(event.detail.body ?? {});
    }

    failed(event) {
        event.preventDefault();
        this.fail(event.detail.message);
    }

    async send() {
        const form = this.form();
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const body = await response.json();
            if (!response.ok || 'failed' === body.uploaded) {
                this.fail(body.message ?? '');

                return;
            }
            this.done(body);
        } catch {
            this.fail('');
        }
    }

    done(body) {
        $('#myModal').modal('hide');
        this.refreshValue.forEach(({ target, url }) => $(target).load(url));
        const message = this.successMessageValue || body.message;
        if (message) {
            window.success?.(message);
        }
    }

    fail(message) {
        $('#myModal').modal('hide');
        window.no_success?.(message);
    }

    alternativeUrl() {
        if (!this.hasUrlTarget || this.urlTarget.closest('[hidden]')) {
            return null;
        }
        const url = this.urlTarget.value.trim();
        if ('' !== url) {
            return url;
        }

        return this.hasUrlGroupTarget ? '' : null;
    }

    isUrl(value) {
        try {
            const url = new URL(value.trim());

            return ['http:', 'https:'].includes(url.protocol) && url.hostname.includes('.');
        } catch {
            return false;
        }
    }

    invalidUrl() {
        if (this.hasUrlErrorTarget) {
            this.urlErrorTarget.hidden = false;
        } else if (this.invalidUrlMessageValue) {
            window.alert(this.invalidUrlMessageValue);
        }
    }

    emptyFile() {
        if (this.hasFileErrorTarget) {
            this.fileErrorTarget.hidden = false;
        } else if (this.emptyMessageValue) {
            window.alert(this.emptyMessageValue);
        }
    }

    hideErrors() {
        [...this.urlErrorTargets, ...this.fileErrorTargets].forEach((element) => {
            element.hidden = true;
        });
    }

    form() {
        return this.hasFormTarget ? this.formTarget : this.element.querySelector('form');
    }

    uploader() {
        const element = this.element.querySelector('[data-controller~="core--uploader"]');

        return element ? this.application.getControllerForElementAndIdentifier(element, 'core--uploader') : null;
    }
}
