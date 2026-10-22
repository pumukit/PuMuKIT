import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { player: { type: String, default: 'paellaiframe' } };

    seek(event) {
        const player = document.getElementById(this.playerValue).contentWindow.__paella_instances__[0];
        player.videoContainer.setCurrentTime(event.params.time - player.videoContainer.trimStart);
    }
}
