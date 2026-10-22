import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { playerUrl: String };

    play() {
        let playerUrl = this.playerUrlValue;
        playerUrl += (playerUrl.indexOf('?') > -1 ? '&' : '?') + 'autoclick=1';
        if (window.location.search) {
            playerUrl += '&' + window.location.search.substring(1);
        }
        window.location.href = playerUrl;
    }
}
