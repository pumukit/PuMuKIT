import {Paella,defaultLoadVideoManifestFunction, utils, Events} from 'paella-core';
import getBasicPluginContext from 'paella-basic-plugins';
import getSlidePluginContext from 'paella-slide-plugins';
import getZoomPluginContext from 'paella-zoom-plugin';
import getUserTrackingPluginContext from 'paella-user-tracking';
import getTeltekPluginsContext from "paella-teltek-plugins";

import fullscreenIcon from './icons/fullscreen.svg';
import windowedIcon from './icons/fullscreen.svg';
import captionsIcon from './icons/captions.svg';
import playIcon from './icons/play.svg';
import pauseIcon from './icons/pause.svg';
import volumeHighIcon from './icons/volume-high.svg';
import volumeLowIcon from './icons/volume-low.svg';
import volumeMidIcon from './icons/volume-mid.svg';
import volumeMuteIcon from './icons/volume-mute.svg';

import packageData from "../package.json";

window.onload = async () => {

    const baseURL = window.location.href;
    let configID = "";

    const params = new URLSearchParams(window.location.search);

    function getIdFromPath() {
        const path = window.location.pathname;
        const segments = path.split('/');
        const filtered = [];

        for (let i = 0; i < segments.length; i++) {
            if (segments[i]) {
                filtered.push(segments[i]);
            }
        }

        return filtered[filtered.length - 1];
    }

    if (params.has('track_id')) {
        configID = getIdFromPath();
    } else if (params.has('id')) {
        configID = params.get('id');
    } else if (baseURL.indexOf('playlist') !== -1) {
        configID = utils.getUrlParameter('videoId');
    } else {
        configID = getIdFromPath();
    }

    let loadIntro = false;
    let loadTail = false;
    let loadVideo = false;
    let intro = false;
    let tail = false;
    let video = false;

    const initParams = {
        loadVideoManifest: async (videoManifestUrl, config, player) => {
            const result = await defaultLoadVideoManifestFunction(videoManifestUrl, config, player);

            intro = (typeof result.intro !== 'undefined');
            tail = (typeof result.tail !== 'undefined');
            video = result;

            if(intro && !loadVideo && !loadTail) {
                videoManifestUrl = result.intro;
                loadIntro = true;

                return await defaultLoadVideoManifestFunction(videoManifestUrl, config, player);
            }

            if(tail && loadTail) {
                videoManifestUrl = result.tail;

                return await defaultLoadVideoManifestFunction(videoManifestUrl, config, player);
            }

            loadVideo = true;
            return result;
        },
        customPluginContext: [
            getBasicPluginContext(),
            getSlidePluginContext(),
            getZoomPluginContext(),
            getUserTrackingPluginContext(),
            getTeltekPluginsContext()
        ],
        configResourcesUrl: '/paella/',
        configUrl: '/paella/config.json' + '?configID='+ configID,
        repositoryUrl: '/paellarepository/',
        getVideoId: (config, player) => {
            return configID;
        },
        getManifestUrl: (repoUrl,videoId) => {
            let location = window.location.href;

            if(location.search('playlist') !== -1 && location.search('secret') !== -1) {
                console.log('is secret playlist');
                let playlistId = utils.getUrlParameter('playlistId');
                let pos = utils.getUrlParameter('videoPos');
                return '/secret/paellaplaylist/' + playlistId + '?videoPos=' + pos;
            }

            if(location.search('playlist') !== -1) {
                console.log('is playlist');
                let playlistId = utils.getUrlParameter('playlistId');
                let pos = utils.getUrlParameter('videoPos');
                return '/paellaplaylist/' + playlistId + '?videoPos=' + pos;
            }

            if(location.search("secret") !== -1) {
                console.log('is secret');
                return '/secret' + `${repoUrl}${videoId}`;
            }

            console.log('no secret, no playlist');
            return `${repoUrl}${videoId}`;
        },
        getManifestFileUrl: (manifestUrl) => {
            return manifestUrl;
        },
    };

    class PaellaPlayer extends Paella {
        get version() {
            const player = packageData.version;
            const coreLibrary = super.version;
            const pluginModules = this.pluginModules.map(m => `${m.moduleName}: ${m.moduleVersion}`);
            return {
                player,
                coreLibrary,
                pluginModules
            };
        }
    }

    const paella = new PaellaPlayer('player-container', initParams);

    paella.addDictionary('es', {
        'Show player controls': 'Mostrar controles del reproductor',
        'Play video': 'Reproducir vídeo',
    });
    paella.addDictionary('ca', {
        'Show player controls': 'Mostra els controls del reproductor',
        'Play video': 'Reprodueix el vídeo',
    });
    paella.addDictionary('gl', {
        'Show player controls': 'Amosar os controis do reprodutor',
        'Play video': 'Reproducir o vídeo',
    });
    paella.addDictionary('eu', {
        'Show player controls': 'Erakutsi erreproduzitzailearen kontrolak',
        'Play video': 'Erreproduzitu bideoa',
    });

    const playerContainer = document.getElementById('player-container');
    let accessibilityEntry = null;

    if (playerContainer) {
        accessibilityEntry = document.createElement('button');

        accessibilityEntry.type = 'button';
        accessibilityEntry.className = 'player-accessibility-entry';
        accessibilityEntry.textContent = 'Show player controls';
        accessibilityEntry.tabIndex = -1;
        accessibilityEntry.hidden = true;

        playerContainer.prepend(accessibilityEntry);

        const normalizeTabIndex = (element) => {
            if (!(element instanceof HTMLElement)) {
                return;
            }

            if (element !== accessibilityEntry && element.tabIndex > 0) {
                element.tabIndex = 0;
            }

            element.querySelectorAll('[tabindex]').forEach((child) => {
                if (child !== accessibilityEntry && child.tabIndex > 0) {
                    child.tabIndex = 0;
                }
            });
        };

        const translatePreview = (element) => {
            if (!(element instanceof HTMLElement)) {
                return;
            }

            if (element.getAttribute('aria-label') === 'Play video') {
                element.setAttribute(
                    'aria-label',
                    paella.translate('Play video')
                );
            }

            element.querySelectorAll('[aria-label="Play video"]').forEach((child) => {
                child.setAttribute(
                    'aria-label',
                    paella.translate('Play video')
                );
            });
        };

        const updateAccessibilityEntry = () => {
            const playbackBar = playerContainer.querySelector('.playback-bar');

            if (!playbackBar) {
                return;
            }

            const hidden = playbackBar.style.display === 'none';

            if (hidden && accessibilityEntry.hidden) {
                accessibilityEntry.tabIndex = 0;
                accessibilityEntry.hidden = false;
            } else if (!hidden && !accessibilityEntry.hidden) {
                accessibilityEntry.tabIndex = -1;
                accessibilityEntry.hidden = true;
            }
        };

        const tabIndexObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes') {
                    normalizeTabIndex(mutation.target);
                    return;
                }

                mutation.addedNodes.forEach((node) => {
                    normalizeTabIndex(node);
                    translatePreview(node);
                });
            });
            updateAccessibilityEntry();
        });

        normalizeTabIndex(playerContainer);
        translatePreview(playerContainer);

        tabIndexObserver.observe(playerContainer, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: ['tabindex', 'style'],
        });

        const showControls = () => {
            paella.showUserInterface();
        };

        accessibilityEntry.addEventListener('focus', showControls);
        accessibilityEntry.addEventListener('click', showControls);
    }

    try {
        await paella.loadManifest().then(() => {
            paella.addCustomPluginIcon("es.upv.paella.playPauseButton","play",playIcon);
            paella.addCustomPluginIcon("es.upv.paella.playPauseButton","pause",pauseIcon);
            paella.addCustomPluginIcon("es.upv.paella.fullscreenButton","fullscreenIcon",fullscreenIcon);
            paella.addCustomPluginIcon("es.upv.paella.fullscreenButton","windowedIcon",windowedIcon);
            paella.addCustomPluginIcon("es.upv.paella.captionsSelectorPlugin","captionsIcon",captionsIcon);
            paella.addCustomPluginIcon("es.upv.paella.volumeButtonPlugin","volumeHighIcon",volumeHighIcon);
            paella.addCustomPluginIcon("es.upv.paella.volumeButtonPlugin","volumeLowIcon",volumeLowIcon);
            paella.addCustomPluginIcon("es.upv.paella.volumeButtonPlugin","volumeMidIcon",volumeMidIcon);
            paella.addCustomPluginIcon("es.upv.paella.volumeButtonPlugin","volumeMuteIcon",volumeMuteIcon);
        });
        await utils.loadStyle('src/style.css');
        if (accessibilityEntry) {
            accessibilityEntry.textContent = paella.translate('Show player controls');
        }
    } catch (e) {
        console.error(e);
    }

    paella.bindEvent(Events.ENDED, async () => {
        if(loadIntro) {
            loadIntro = false;
            loadVideo = true;
            loadTail = false;
            await paella.reload();
            await paella.play();
            return;
        }

        if(loadVideo) {
            loadIntro = (!tail);
            loadTail = (tail);
            loadVideo = false;

            await paella.reload();
            if(loadTail) {
                await paella.play();
            }

            return;
        }

        if(loadTail) {
            loadIntro = (intro);
            loadVideo = (!intro);
            loadTail = false;

            await paella.reload();
        }

    }, false);

    paella.bindEvent(Events.PLAYER_LOADED, async () => {
        // Check time param in URL and seek: ?time=00:01:30
        const timeString = utils.getHashParameter('time') || utils.getUrlParameter('time');
        if (timeString) {
            const totalTime = utils.timeToSeconds(timeString);
            await paella.videoContainer.setCurrentTime(totalTime);
        }
    });
}
