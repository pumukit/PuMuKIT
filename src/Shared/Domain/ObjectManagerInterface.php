<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Object Manager interface for framework-independent persistence operations.
 *
 * This interface provides a generic abstraction over persistence managers
 * (like Doctrine ODM's DocumentManager or ORM's EntityManager) to keep
 * the Domain and Application layers independent from specific ORM implementations.
 *
 * Implementation should be provided in the Infrastructure layer.
 */
interface ObjectManagerInterface
{
    /**
     * Tells the ObjectManager to make an instance managed and persistent.
     *
     * The object will be entered into the database at or before transaction commit
     * or as a result of the flush operation.
     *
     * @param object $object The instance to make managed and persistent
     */
    public function persist(object $object): void;

    /**
     * Removes an object instance.
     *
     * A removed object will be removed from the database at or before transaction
     * commit or as a result of the flush operation.
     *
     * @param object $object The object instance to remove
     */
    public function remove(object $object): void;

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     *
     * This effectively synchronizes the in-memory state of managed objects with the database.
     */
    public function flush(): void;

    /**
     * Clears the ObjectManager, causing all managed objects to become detached.
     *
     * @param string|null $objectName If given, only objects of this type will be detached
     */
    public function clear(?string $objectName = null): void;

    /**
     * Refreshes the persistent state of an object from the database,
     * overriding any local changes that have not yet been persisted.
     *
     * @param object $object The object to refresh
     */
    public function refresh(object $object): void;

    /**
     * Finds an object by its identifier.
     *
     * @param string $className The class name of the object to find
     * @param mixed  $id        The identity of the object to find
     *
     * @return object|null The found object or null if not found
     */
    public function find(string $className, mixed $id): ?object;

    /**
     * Gets the repository for a class.
     *
     * @param string $className The class name
     *
     * @return object The repository instance
     */
    public function getRepository(string $className): object;
}
