<?php

namespace Drupal\pins_search_azure\Services;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service to handle vectorization logic for Azure/OpenAI.
 */
class AzureVectorizer {

  protected $httpClient;
  protected $config;
  protected $logger;

  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->httpClient = $http_client;
    $this->config = $config_factory->get('pins_search_azure.settings');
    $this->logger = $logger_factory->get('pins_vectorizer');
  }

  /**
   * Main entry point to get a single vector for a string or array of strings.
   */
  public function getVector($text, $title = ''): array {
    // If Search API provides them as an array (common in Views), flatten them.#conjunction is a special key used by Search API to indicate how multiple keys are combined (AND/OR). We should ignore it in our flattening logic.
    if (is_array($text)) {
      $text = $this->flattenKeys($text);
    }
    // Final validation to ensure we have a usable string.
    if (empty($text) || !is_string($text)) {
      return [];
    }

    $context_prefix = !empty($title) ? "Title: " . $title . ". Content: " : "";
    // Configuration
    $maxChunkChars = 1000;
    $overlapChars = 200;
    $effectiveSize = $maxChunkChars - strlen($context_prefix);
    
    $chunks = $this->chunkText($text, $effectiveSize, $overlapChars, $context_prefix);

    if (empty($chunks)) return [];

    try {
      // Batch process chunks (OpenAI supports arrays of strings)
      $all_vectors = $this->sendBatchRequest($chunks);
      
      // Perform Mean Pooling to return a single 1536-dim vector
      return $this->averageVectors($all_vectors);
    }
    catch (\Exception $e) {
      $this->logger->error('Vectorization failed: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Helper: Sliding window chunking with word-safety.
   */
  protected function chunkText($text, $size, $overlap, $prefix): array {
    $chunks = [];
    $cursor = 0;
    $len = strlen($text);

    while ($cursor < $len) {
      $chunkText = substr($text, $cursor, $size);
      
      // Word safety: don't cut words in half
      if ($cursor + $size < $len) {
        $lastSpace = strrpos($chunkText, ' ');
        if ($lastSpace !== FALSE) {
          $chunkText = substr($chunkText, 0, $lastSpace);
        }
      }

      $chunks[] = $prefix . trim($chunkText);
      $cursor += (strlen($chunkText) - $overlap);
      
      if ($cursor >= $len || strlen($chunkText) <= $overlap) break;
    }
    return $chunks;
  }

  /**
   * Helper: Batched API Request.
   */
  protected function sendBatchRequest(array $chunks): array {
    $vectors = [];
    // Azure/OpenAI usually limit batches to 16 or 96. We'll stay safe at 16.
    foreach (array_chunk($chunks, 16) as $batch) {
      $response = $this->httpClient->post($this->config->get('api_url'), [
        'json' => [
          'input' => $batch,
          'model' => $this->config->get('api_model') ?: 'text-embedding-ada-002',
        ],
        'headers' => [
          'Authorization' => 'Bearer ' . $this->config->get('api_key'),
        ],
      ]);
      $data = json_decode($response->getBody()->getContents(), TRUE);
      foreach ($data['data'] as $entry) {
        $vectors[] = $entry['embedding'];
      }
    }
    return $vectors;
  }

  /**
   * Helper: Mathematical Mean Pooling (Averaging).
   */
  public function averageVectors(array $vectors): array {
    $count = count($vectors);
    if ($count === 0) return [];
    if ($count === 1) return $vectors[0];

    $dimensions = count($vectors[0]);
    $mean = array_fill(0, $dimensions, 0.0);

    foreach ($vectors as $v) {
      foreach ($v as $dim => $value) {
        $mean[$dim] += $value;
      }
    }

    foreach ($mean as $dim => $value) {
      $mean[$dim] = $value / $count;
    }

    return $mean;
  }
  /**
   * Helper to flatten Search API keys into a string.
   */
  protected function flattenKeys($keys): string {
    if (is_string($keys)) {
      return $keys;
    }
    $flat = '';
    // This logic correctly skips '#conjunction' and handles nested arrays.
    foreach ($keys as $key => $value) {
      if (is_array($value)) {
        $flat .= ' ' . $this->flattenKeys($value);
      }
      // Skip internal Search API metadata keys starting with #
      elseif (is_string($value) && strpos($key, '#') !== 0) {
        $flat .= ' ' . $value;
      }
      // Handle cases where the value itself is the search term and key is numeric
      elseif (is_string($value) && is_numeric($key)) {
        $flat .= ' ' . $value;
      }
    }
    return trim($flat);
  }
}