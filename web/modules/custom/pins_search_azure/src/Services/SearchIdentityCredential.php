<?php

namespace Drupal\pins_search_azure\Services;

use GuzzleHttp\ClientInterface;

class SearchIdentityCredential {

  private const RESOURCE = 'https://search.azure.com';
  protected ?string $accessToken = NULL;
  protected int $expiresAt = 0;

  public function __construct(protected ClientInterface $httpClient) {
  }

  protected function environment(string $name): string {
    return (string) ($_ENV[$name] ?? (getenv($name) ?: ''));
  }

  public function getToken(): string {

    if (
      $this->accessToken !== NULL &&
      $this->expiresAt > (time() + 300)
    ) {
      return $this->accessToken;
    }

    try {
      $response_body = $this->getManagedIdentityResponse();

      $token = json_decode($response_body, TRUE, 512, JSON_THROW_ON_ERROR);
      if (!is_array($token) || !is_string($token['access_token'] ?? NULL) || $token['access_token'] === '') {
        throw new \RuntimeException('No valid access token returned.');
      }
      $expires_on = $token['expires_on'] ?? NULL;
      $expires_at = is_numeric($expires_on) ? (int) $expires_on : (is_string($expires_on) ? strtotime($expires_on) : FALSE);
      if ($expires_at === FALSE || $expires_at <= time() + 300) {
        throw new \RuntimeException('Identity returned an invalid or expired access token.');
      }

      $this->accessToken = $token['access_token'];
      $this->expiresAt = $expires_at;
      return $this->accessToken;
    }
    catch (\Exception) {
      throw new \RuntimeException(
        'Managed identity authentication failed or returned an invalid or expired token. Enable an identity on the Azure host and check endpoint connectivity and AZURE_CLIENT_ID. API-key fallback is disabled outside DDEV.'
      );
    }
  }

  protected function getManagedIdentityResponse(): string {
    $query = [
      'api-version' => '2018-02-01',
      'resource' => self::RESOURCE,
    ];
    $client_id = $this->environment('AZURE_CLIENT_ID');
    if ($client_id !== '') {
      $query['client_id'] = $client_id;
    }
    $response = $this->httpClient->request(
      'GET',
      'http://169.254.169.254/metadata/identity/oauth2/token',
      [
        'headers' => ['Metadata' => 'true'],
        'query' => $query,
        'allow_redirects' => FALSE,
        'proxy' => '',
        'timeout' => 10,
        'connect_timeout' => 2,
      ]
    );

    return (string) $response->getBody();
  }

}