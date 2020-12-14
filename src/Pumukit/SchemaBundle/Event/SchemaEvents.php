<?php

namespace Pumukit\SchemaBundle\Event;

final class SchemaEvents
{
    /**
     * The series.update event is thrown each time a
     * series is updated.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\SeriesEvent instance.
     *
     * @var string
     */
    const SERIES_UPDATE = 'series.update';

    /**
     * The series.create event is thrown each time a
     * series is created.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\SeriesEvent instance.
     *
     * @var string
     */
    const SERIES_CREATE = 'series.create';

    /**
     * The series.delete event is thrown each time a
     * series is deleted.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\SeriesEvent instance.
     *
     * @var string
     */
    const SERIES_DELETE = 'series.delete';

    /**
     * The multimediaobject.update event is thrown each time a
     * multimedia object is updated.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\MultimediaObjectEvent instance.
     *
     * @var string
     */
    const MULTIMEDIAOBJECT_UPDATE = 'multimediaobject.update';

    /**
     * The multimediaobject.create event is thrown each time a
     * multimedia object is created.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\MultimediaObjectEvent instance.
     *
     * @var string
     */
    const MULTIMEDIAOBJECT_CREATE = 'multimediaobject.create';

    /**
     * The multimediaobject.clone event is thrown each time a
     * multimedia object is cloned.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\MultimediaObjectEvent instance
     * and the same cloned
     *
     * @var string
     */
    const MULTIMEDIAOBJECT_CLONE = 'multimediaobject.clone';

    /**
     * The multimediaobject.delete event is thrown each time a
     * multimedia object is deleted.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\MultimediaObjectEvent instance.
     *
     * @var string
     */
    const MULTIMEDIAOBJECT_DELETE = 'multimediaobject.delete';

    /**
     * This event is thrown each time a owner is added on multimedia object.
     */
    const MULTIMEDIA_OBJECT_ADD_OWNER = 'multimedia_object.add_owner';

    /**
     * The material.create event is thrown each time a
     * material is created.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\MaterialEvent instance.
     *
     * @var string
     */
    const MATERIAL_CREATE = 'material.create';

    /**
     * The material.update event is thrown each time a
     * material is updated.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\MaterialEvent instance.
     *
     * @var string
     */
    const MATERIAL_UPDATE = 'material.update';

    /**
     * The material.delete event is thrown each time a
     * material is deleted.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\MaterialEvent instance.
     *
     * @var string
     */
    const MATERIAL_DELETE = 'material.delete';

    /**
     * The track.create event is thrown each time a
     * track is created.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\TrackEvent instance.
     *
     * @var string
     */
    const TRACK_CREATE = 'track.create';

    /**
     * The track.update event is thrown each time a
     * track is updated.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\TrackEvent instance.
     *
     * @var string
     */
    const TRACK_UPDATE = 'track.update';

    /**
     * The track.delete event is thrown each time a
     * track is deleted.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\TrackEvent instance.
     *
     * @var string
     */
    const TRACK_DELETE = 'track.delete';

    /**
     * The link.create event is thrown each time a
     * link is created.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\LinkEvent instance.
     *
     * @var string
     */
    const LINK_CREATE = 'link.create';

    /**
     * The link.update event is thrown each time a
     * link is updated.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\LinkEvent instance.
     *
     * @var string
     */
    const LINK_UPDATE = 'link.update';

    /**
     * The link.delete event is thrown each time a
     * link is deleted.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\LinkEvent instance.
     *
     * @var string
     */
    const LINK_DELETE = 'link.delete';

    /**
     * The pic.create event is thrown each time a
     * pic is created.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\PicEvent instance.
     *
     * @var string
     */
    const PIC_CREATE = 'pic.create';

    /**
     * The pic.update event is thrown each time a
     * pic is updated.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\PicEvent instance.
     *
     * @var string
     */
    const PIC_UPDATE = 'pic.update';

    /**
     * The pic.delete event is thrown each time a
     * pic is deleted.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\PicEvent instance.
     *
     * @var string
     */
    const PIC_DELETE = 'pic.delete';

    /**
     * The personwithrole.create event is thrown each time a
     * personwithrole is created.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\PersonWithRoleEvent instance.
     *
     * @var string
     */
    const PERSONWITHROLE_CREATE = 'personwithrole.create';

    /**
     * The personwithrole.update event is thrown each time a
     * personwithrole is updated.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\PersonWithRoleEvent instance.
     *
     * @var string
     */
    const PERSONWITHROLE_UPDATE = 'personwithrole.update';

    /**
     * The personwithrole.delete event is thrown each time a
     * personwithrole is deleted.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\PersonWithRoleEvent instance.
     *
     * @var string
     */
    const PERSONWITHROLE_DELETE = 'personwithrole.delete';

    /**
     * The permissionprofile.create event is thrown each time a
     * permissionprofile is created.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\PermissionProfileEvent instance.
     *
     * @var string
     */
    const PERMISSIONPROFILE_CREATE = 'permissionprofile.create';

    /**
     * The permissionprofile.update event is thrown each time a
     * permissionprofile is updated.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\PermissionProfileEvent instance.
     *
     * @var string
     */
    const PERMISSIONPROFILE_UPDATE = 'permissionprofile.update';

    /**
     * The permissionprofile.delete event is thrown each time a
     * permissionprofile is deleted.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\PermissionProfileEvent instance.
     *
     * @var string
     */
    const PERMISSIONPROFILE_DELETE = 'permissionprofile.delete';

    /**
     * The user.create event is thrown each time a
     * user is created.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\UserEvent instance.
     *
     * @var string
     */
    const USER_CREATE = 'user.create';

    /**
     * The user.update event is thrown each time a
     * user is updated.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\UserEvent instance.
     *
     * @var string
     */
    const USER_UPDATE = 'user.update';

    /**
     * The user.delete event is thrown each time a
     * user is deleted.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\UserEvent instance.
     *
     * @var string
     */
    const USER_DELETE = 'user.delete';

    /**
     * The group.create event is thrown each time a
     * group is created.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\GroupEvent instance.
     *
     * @var string
     */
    const GROUP_CREATE = 'group.create';

    /**
     * The group.update event is thrown each time a
     * group is updated.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\GroupEvent instance.
     *
     * @var string
     */
    const GROUP_UPDATE = 'group.update';

    /**
     * The group.delete event is thrown each time a
     * group is deleted.
     *
     * The event listener receives an
     * Pumukit\SchemaBundle\Event\GroupEvent instance.
     *
     * @var string
     */
    const GROUP_DELETE = 'group.delete';
}
