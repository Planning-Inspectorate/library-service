<?php

namespace Drupal\pins\Controller;

use Drupal\Core\Controller\ControllerBase;

class PinsCookiePageController extends ControllerBase {

  public function content() {
    $build = [];

    $build['intro1'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Cookies are small files saved on your phone, tablet or computer when you visit a website. We use cookies to store information about how you use the application service, such as the pages you visit.'),
    ];

    $build['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Cookie settings'),
      '#attributes' => [
        'class' => ['govuk-heading-m'],
      ],
    ];

    $build['intro2'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('We use Google Analytics to measure how you use the Library so we can improve it based on user needs. We do not allow Google to use or share the data about how you use this site.'),
    ];

    $build['intro3'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('You agree to the Planning Inspectorate using your data to help improve the Library.'),
    ];

    $build['main_message'] = [
      '#type' => 'container',
      'buttons' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['govuk-button-group']],

        'accept' => [
          '#type' => 'html_tag',
          '#tag' => 'button',
          '#value' => $this->t('Accept analytics cookies'),
          '#attributes' => [
            'type' => 'button',
            'name' => 'cookies',
            'value' => 'accept',
            'class' => ['govuk-button'],
            'onclick' => "handleCookieChoice('accept')"
          ],
        ],

        'reject' => [
          '#type' => 'html_tag',
          '#tag' => 'button',
          '#value' => $this->t('Reject analytics cookies'),
          '#attributes' => [
            'type' => 'button',
            'name' => 'cookies',
            'value' => 'reject',
            'class' => ['govuk-button'],
            'onclick' => "handleCookieChoice('reject')"
          ],
        ],
      ],
    ];

    $build['accepted'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'cookie-accepted',
        'hidden' => 'hidden',
      ],
      'content' => [
        '#markup' => '<p>You’ve accepted analytics cookies.</p>',
      ],
    ];

    $build['rejected'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'cookie-rejected',
        'hidden' => 'hidden',
      ],
      'content' => [
        '#markup' => '<p>You’ve rejected analytics cookies.</p>',
      ],
    ];

    return $build;
  }
}
