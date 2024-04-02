<?php

namespace Drupal\nylotto_custom_json\Controller;

use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Menu\MenuLinkTree;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Mail\MailManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;

/**
 * Class CustomEndpoints.
 */
class CustomEndpoints extends ControllerBase {

  /**
   * Stores the request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Stores the configuration factory for use in our callback.
   *
   * @var \Drupal\Core\Config\ConfigFactorystorestheconfigurationfactory
   */
  protected $configFactory;

  /**
   * Stores the basice page settings for our callback.
   *
   * @var objectstoresconfigurationsforbasicpagesettings
   */
  protected $basicPageSettings;

  /**
   * Stores the service for the menu tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTree
   */
  protected $menuLinkTree;

  /**
   * Stores the service for mail manager.
   *
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * Constructs a CustomEndpoints object.
   */
  public function __construct(RequestStack $request_stack, ConfigFactory $configFactory, MenuLinkTree $menuLinkTree, MailManager $mailManager) {
    $this->requestStack = $request_stack;
    $this->configFactory = $configFactory;
    $this->basicPageSettings = $this->configFactory->get('nylotto_custom_json.page.settings');
    $this->menuLinkTree = $menuLinkTree;
    $this->mailManager = $mailManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('request_stack'),
          $container->get('config.factory'),
          $container->get('menu.link_tree'),
          $container->get('plugin.manager.mail')
      );
  }

  /**
   * Returns the list of basic page ids for the front end.
   */
  public function getBasicPageIds() {
    $data = [];
    foreach ($this->basicPageSettings->get('static_pages') as $page_id) {
      $nid = $this->basicPageSettings->get($page_id);
      if ($nid) {
        $alias = Url::fromRoute('entity.node.canonical', ['node' => $nid])->toString();
        $data[$page_id] = [
          'nid' => $this->basicPageSettings->get($page_id),
          'alias' => $alias,
        ];
      }
    }

    return new JsonResponse($data);
  }

  /**
   * Returns the list of menu items for a given menu.
   */
  public function getMenuTree($menu_name) {
    $menu_parameters = new MenuTreeParameters();
    $items = $this->menuLinkTree->load($menu_name, $menu_parameters);
    $data = [];
    foreach ($items as $item) {
      $name = $item->link->getTitle();
      $url = $item->link->getUrlObject();
      $url_string = $url->toString();
      $fid = isset($item->link->getOptions()['menu_icon']['fid']) ? $item->link->getOptions()['menu_icon']['fid'] : FALSE;
      $file = entity_load('file', $fid);
      $data[] = [
        'name' => $name,
        'url' => $url_string,
        'icon' => ($file) ? file_create_url($file->getFileUri()) : '',
        'weight' => $item->link->getWeight(),
      ];
    }

    usort($data, function ($a, $b) {
        return $a['weight'] - $b['weight'];
    });

    return new JsonResponse($data);
  }

  /**
   * Send mail that we get from users.
   */
  public function sendMail() {
    $module = 'nylotto_custom_json';
    $key = 'nylotto_contact_form';
    $system_site_config = \Drupal::config('system.site');
    $site_email = $system_site_config->get('mail');
    $to = $site_email;
    $request = $this->requestStack->getCurrentRequest();
    $params['message'] = $request->get('message');
    $params['subject'] = $request->get('subject');
    $params['reply'] = $request->get('email');

    if (empty($params['message']) || empty($params['subject']) || empty($params['reply'])) {
      $response = new JsonResponse(['status' => 0, 'message' => 'There was a problem sending your message and it was not sent.']);
      $response->setStatusCode(500);
      return $response;
    }

    $langcode = 'EN';
    $send = TRUE;
    $result = $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    if (!($result['result'])) {
      $response = new JsonResponse(['status' => 0, 'message' => 'There was a problem sending your message and it was not sent.']);
      $response->setStatusCode(500);
      return $response;
    }
    else {
      return new JsonResponse(['status' => 1, 'message' => 'success']);
    }
  }

  /**
   * Send mail that we get from users.
   */
  public function getScratchoffPdf() {
    $data = [];
    $config = \Drupal::service('config.factory')->getEditable('scratch_off_pdf.custom_settings');
    $file_id = $config->get('scratch_off_pdf_file')[0];
    if ($file_id) {
      $file = File::load($file_id);
      $uri = $file->getFileUri();
      $url = Url::fromUri(file_create_url($uri))->toString();
      $data[] = [
        'uri' => $uri,
        'url' => $url,
      ];
    }

    return new JsonResponse($data);
  }

}
