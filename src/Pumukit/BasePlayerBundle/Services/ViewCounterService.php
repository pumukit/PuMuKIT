<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Services;

use Pumukit\BasePlayerBundle\Event\BasePlayerEvents;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Punto unico de decision sobre cuando se contabiliza una visualizacion.
 *
 * Antes la comprobacion de `pumukitplayer.when_dispatch_view_event` estaba
 * repartida por los controladores, y no todos la hacian: la pagina de video
 * despachaba el evento en cada GET aunque la configuracion fuese `on_play`,
 * con lo que una reproduccion real se contaba dos veces (una al cargar y otra
 * por AJAX) y cada peticion de un rastreador con User-Agent de navegador se
 * contaba como visualizacion.
 *
 * Quien llama solo declara QUE ha pasado (se cargo la pagina / se le dio al
 * play); este servicio decide SI eso cuenta. Para cambiar el criterio en el
 * futuro solo hay que tocar aqui.
 */
class ViewCounterService
{
    public const ON_LOAD = 'on_load';
    public const ON_PLAY = 'on_play';

    private EventDispatcherInterface $eventDispatcher;
    private string $whenDispatchViewEvent;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        string $pumukitPlayerWhenDispatchViewEvent
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->whenDispatchViewEvent = $pumukitPlayerWhenDispatchViewEvent;
    }

    /**
     * Se ha renderizado la pagina del video. Solo cuenta con `on_load`.
     */
    public function registerOnLoad(MultimediaObject $multimediaObject, ?MediaInterface $track = null): bool
    {
        return $this->register(self::ON_LOAD, $multimediaObject, $track);
    }

    /**
     * El reproductor ha empezado a reproducir. Solo cuenta con `on_play`.
     */
    public function registerOnPlay(MultimediaObject $multimediaObject, ?MediaInterface $track = null): bool
    {
        return $this->register(self::ON_PLAY, $multimediaObject, $track);
    }

    /**
     * Previsualizacion del reproductor desde el backoffice.
     *
     * Cuenta SIEMPRE, con independencia de `when_dispatch_view_event`. No es un
     * descuido: es el comportamiento historico de PlayerController, que
     * despachaba el evento cuando el referer contenia "admin" sin mirar la
     * configuracion. Se recoge aqui tal cual para que la excepcion sea visible
     * en el mismo sitio que el resto de la politica; si algun dia se decide que
     * una previsualizacion no deberia contar como visualizacion, se cambia aqui
     * y ya.
     */
    public function registerAdminPreview(MultimediaObject $multimediaObject, ?MediaInterface $track = null): bool
    {
        return $this->dispatch($multimediaObject, $track);
    }

    public function countsOnLoad(): bool
    {
        return self::ON_LOAD === $this->whenDispatchViewEvent;
    }

    public function countsOnPlay(): bool
    {
        return self::ON_PLAY === $this->whenDispatchViewEvent;
    }

    private function register(string $trigger, MultimediaObject $multimediaObject, ?MediaInterface $track): bool
    {
        if ($trigger !== $this->whenDispatchViewEvent) {
            return false;
        }

        return $this->dispatch($multimediaObject, $track);
    }

    private function dispatch(MultimediaObject $multimediaObject, ?MediaInterface $track): bool
    {
        $this->eventDispatcher->dispatch(
            new ViewedEvent($multimediaObject, $track),
            BasePlayerEvents::MULTIMEDIAOBJECT_VIEW
        );

        return true;
    }
}
