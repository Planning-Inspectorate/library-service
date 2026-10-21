<?php

namespace Drupal\pins_search_azure\Services;

use Drupal\search_api_aais\BackendClient\BackendClientFactory;
use Drupal\search_api_aais\BackendClient\BackendClientInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;

class IdentityBackendClientFactory extends BackendClientFactory {

  protected SearchIdentityCredential $credential;

  public function setCredential(
    SearchIdentityCredential $credential
  ): void {
    $this->credential = $credential;
  }

  public function create(array $configuration): BackendClientInterface {

    $endpoint = parse_url($configuration['url'] ?? '');
    if (($endpoint['scheme'] ?? '') !== 'https' || empty($endpoint['host']) || isset($endpoint['user']) || isset($endpoint['pass'])) {
      throw new \InvalidArgumentException('Azure Search identity authentication requires an HTTPS endpoint without credentials.');
    }

    $factory = clone $this;
    $credential = $this->credential;
    $transport = $this->httpClient;

    $factory->httpClient = new Client([
      'handler' => static function (
        RequestInterface $request,
        array $options
      ) use (
        $credential,
        $transport,
        $endpoint
      ) {
        $uri = $request->getUri();
        if ($uri->getScheme() !== 'https' || strcasecmp($uri->getHost(), $endpoint['host']) !== 0 || ($uri->getPort() ?? 443) !== ($endpoint['port'] ?? 443)) {
          throw new \RuntimeException('Refusing to send Azure Search credentials to another endpoint.');
        }
        $request = $request
          ->withoutHeader('api-key')
          ->withoutHeader('Authorization')
          ->withHeader(
            'Authorization',
            'Bearer ' . $credential->getToken()
          );

        unset($options['handler']);
        $options['allow_redirects'] = FALSE;

        return $transport->sendAsync(
          $request,
          $options
        );
      },
    ]);

    return $factory->createIdentityClient($configuration);
  }

  protected function createIdentityClient(
    array $configuration
  ): BackendClientInterface {

    $client = parent::create($configuration);

    $client->setApiKey('');

    return $client;
  }

}