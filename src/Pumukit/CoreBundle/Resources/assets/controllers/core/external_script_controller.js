import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { src: String, scriptId: String };

    connect() {
        if (document.getElementById(this.scriptIdValue)) {
            return;
        }

        const script = document.createElement('script');
        script.id = this.scriptIdValue;
        script.src = this.srcValue;
        document.head.appendChild(script);
    }
}
