<?php

/**
 * @file
 * API documentation for the Replicate module.
 */

/**
 * Unlike the D7 version the D8 version no longer uses hooks.
 *
 * Available events are:
 * 
 * - \Drupal\replicate\Events\ReplicatorEvents::AFTER_SAVE
 *   This event is fired after the entire entity got replicated and saved.
 * - replicate__entity__{$entity_type_id}
 *   This event allows to change the logic how an entity is replicated. It retrieves
 *   a copy of the duplicated entity, before any field level customization is 
 *   executed
 * - replicate__entity_field__{$field_type_id}
 *   This event allows you to manipulate the replication of a specific field type.
 * - \Drupal\replicate\Events\ReplicatorEvents::REPLICATE_ALTER
 *   This event is fired after the entire entity got replicated but before it is saved.
 */
