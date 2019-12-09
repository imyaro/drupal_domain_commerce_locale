<?php

namespace Drupal\domain_commerce_locale\Resolver;

use Drupal\commerce\CurrentCountryInterface;
use Drupal\commerce\Locale;
use Drupal\commerce\Resolver\LocaleResolverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Commerce locale resolver.
 *
 * Get the locale based on the domain settings.
 */
class DomainCommerceLocaleResolver implements LocaleResolverInterface {

  /**
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  private $domainNegotiator;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var \Drupal\commerce\CurrentCountryInterface
   */
  private $currentCountry;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  /**
   * DomainCommerceLocaleResolver constructor.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $domainNegotiator
   *   Domain negotiator.
   */
  public function __construct(DomainNegotiatorInterface $domainNegotiator, ConfigFactoryInterface $configFactory, CurrentCountryInterface $currentCountry, LanguageManagerInterface $languageManager) {
    $this->domainNegotiator = $domainNegotiator;
    $this->configFactory = $configFactory;
    $this->currentCountry = $currentCountry;
    $this->languageManager = $languageManager;
  }

  /**
   * @inheritDoc
   */
  public function resolve() {
    $activeDomain = $this->domainNegotiator->getActiveDomain();
    $settings = $this->configFactory->get('domain_commerce_locale.settings');

    if ($domainConfig = $settings->get($activeDomain->id())) {
      if (!isset($domainConfig['language'])) {
        $domainConfig['language'] = $this->languageManager->getCurrentLanguage()->getId();
      }
      if (!isset($domainConfig['country'])) {
        $domainConfig['country'] = $this->currentCountry->getCountry()->getCountryCode();
      }

      $languageParts = explode('-', $domainConfig['language']);

      if (count($languageParts) > 1) {
        $domainConfig['language'] = $languageParts[0];
      }

      $localeId = $domainConfig['language'] . '-' . $domainConfig['country'];

      return new Locale($localeId);
    }

    return NULL;
  }

}
