<?php

declare(strict_types = 1);

namespace Drupal\localgov_eu_cookie_compliance\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

/**
 * Presents the EU cookie compliance form as a block.
 *
 * @Block(
 *   id = "cookie_settings_block",
 *   admin_label = @Translation("EU Cookie settings block")
 * )
 */
class CookieSettings extends BlockBase {

  /**
   * Wrapper for the EU Cookie compliance settings form.
   *
   * The EU Cookie compliance module presents the Cookie settings form in a
   * popup.  We provide the same form as a block so that it can be placed
   * anywhere.
   *
   * {@inheritdoc}
   *
   * @todo Provide hook to adjust cookie domain.  This is useful where
   * multiple subdomains share the same cookie settings page on the main site.
   */
  public function build(): array {

    $attachments = [];
    eu_cookie_compliance_page_attachments($attachments);

    // Avoid using cached cookie data.
    $cookie_data = eu_cookie_compliance_build_data();

    $build = [
      '#markup' => Markup::create($cookie_data['variables']['popup_html_info']),
      '#cache' => $attachments['#cache'],
    ];

    // We provide two variations of the Cookie popup - one for the Cookie
    // settings page, another for every other page.
    $build['#cache']['contexts'][] = 'url.path';

    $build['#attached'] = $attachments['#attached'];
    $build['#attached']['library'][] = 'localgov_eu_cookie_compliance/localgov_eu_cookie_compliance';

    return $build;
  }

}
