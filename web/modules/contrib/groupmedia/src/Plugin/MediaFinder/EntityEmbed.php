<?php

namespace Drupal\groupmedia\Plugin\MediaFinder;

/**
 * Tracks usage of entities related in text fields.
 *
 * @MediaFinder(
 *   id = "groupmedia_entity_embed",
 *   label = @Translation("Groupmedia: Entity Embed"),
 *   description = @Translation("Tracks relationships created with 'Entity Embed' in formatted text fields."),
 *   field_types = {"text", "text_long", "text_with_summary"},
 *   element = "drupal-entity",
 * )
 */
class EntityEmbed extends TextFieldEmbedBase {}
