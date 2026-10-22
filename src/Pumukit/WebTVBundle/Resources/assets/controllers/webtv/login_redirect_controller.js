import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { url: String };

    redirect() {
        try {
            if (window.parent.location.origin === window.location.origin) {
                window.parent.location.href = this.loginUrl(window.parent.location.href);
                return;
            }
        } catch (e) {
        }
        window.location.href = this.loginUrl(window.location.href);
    }

    loginUrl(referer) {
        return `${this.urlValue}?referer=${encodeURIComponent(referer)}`;
    }
}
