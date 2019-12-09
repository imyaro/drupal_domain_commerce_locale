<?php

namespace Drupal\domain_commerce_locale\Form;

use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DomainCommerceLocaleSettings extends ConfigFormBase {

  /**
   * Entity type manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface
   */
  private $countryRepository;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, CountryRepositoryInterface $countryRepository, LanguageManagerInterface $languageManager) {
    parent::__construct($configFactory);
    $this->entityTypeManager = $entityTypeManager;
    $this->countryRepository = $countryRepository;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('address.country_repository'),
      $container->get('language_manager')
    );
  }

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames() {
    return ['domain_commerce_locale.settings'];
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'domain_commerce_locale_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  function buildForm(array $form, FormStateInterface $form_state) {
    $domainStorage = $this->entityTypeManager->getStorage('domain');
    /** @var \Drupal\domain\DomainInterface[] $domains */
    $domains = $domainStorage->loadMultiple();

    $config = $this->configFactory()->get('domain_commerce_locale.settings');

    // Single settings form for all domains.
    foreach ($domains as $domain) {
      $domainId = $domain->id();
      $domainLabel = $domain->label();

      $domainConfig = $config->get($domainId) ?? [];

      $form[$domainId] = [
        '#type' => 'fieldset',
        '#title' => $domainLabel,
        '#tree' => TRUE,
      ];

      $form[$domainId]['country'] = [
        '#type' => 'select',
        '#title' => $this->t('Country for %domain', ['%domain' => $domainLabel]),
        '#options' => $this->getCountryList(),
        '#empty_value' => '_none',
        '#empty_option' => $this->t('Default'),
        '#default_value' => $domainConfig['country'] ?? '_none',
      ];

      $form[$domainId]['language'] = [
        '#type' => 'select',
        '#title' => $this->t('Language for %domain', ['%domain' => $domainLabel]),
        '#options' => $this->getLanguageList(),
        '#empty_value' => '_none',
        '#empty_option' => $this->t('Default'),
        '#default_value' => $domainConfig['country'] ?? '_none',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Provides list of the available locales formatted to be used in select.
   *
   * @return array
   *  Locale list.
   */
  protected function getCountryList(): array {
    return $this->countryRepository->getList();
  }

  /**
   * Languages list.
   *
   * @return array
   *   Provides languages list.
   */
  protected function getLanguageList(): array {
    $languages = $this->languageManager->getLanguages();
    $languageList = [];
    foreach ($languages as $language) {
      $languageList[$language->getId()] = $language->getName();
    }

    return $languageList;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('domain_commerce_locale.settings');

    $domainStorage = $this->entityTypeManager->getStorage('domain');
    /** @var \Drupal\domain\DomainInterface[] $domains */
    $domains = $domainStorage->loadMultiple();

    foreach ($domains as $domain) {
      $domainId = $domain->id();

      $domainValue = $form_state->getValue($domainId);
      foreach ($domainValue as $domainValueKey => $domainValueData) {
        if ($domainValueData === '_none') {
          unset($domainValue[$domainValueKey]);
        }
      }

      if ($domainValue) {
        $config->set($domainId, $domainValue);
      }
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
