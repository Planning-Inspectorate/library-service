<?php

namespace Drupal\pins_search_azure\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays Azure OpenAI token usage reports.
 */
class TokenUsageReportController extends ControllerBase {

  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Builds the page listing token usage recorded during indexing.
   */
  public function buildIndexUsagePage() {
    $totals_query = $this->database->select('pins_search_azure_token_usage', 't');
    $totals_query->addExpression('SUM(t.prompt_tokens)', 'prompt_sum');
    $totals_query->addExpression('SUM(t.total_tokens)', 'total_sum');
    $totals_query->addExpression('COUNT(*)', 'row_count');
    $totals = $totals_query->execute()->fetchAssoc();

    $header = [
      $this->t('Index'),
      $this->t('Item'),
      $this->t('Field count'),
      $this->t('Prompt tokens'),
      $this->t('Total tokens'),
      $this->t('Recorded'),
    ];

    $query = $this->database->select('pins_search_azure_token_usage', 't')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->fields('t', [
      'index_id',
      'item_id',
      'field_count',
      'prompt_tokens',
      'total_tokens',
      'created',
    ]);
    $query->orderBy('created', 'DESC');
    $query->limit(50);
    $result = $query->execute();

    $rows = [];
    foreach ($result as $record) {
      $rows[] = [
        $record->index_id,
        $record->item_id,
        $record->field_count,
        $record->prompt_tokens,
        $record->total_tokens,
        $this->dateFormatter()->format($record->created, 'short'),
      ];
    }

    $prompt_sum = (int) ($totals['prompt_sum'] ?? 0);
    $total_sum = (int) ($totals['total_sum'] ?? 0);
    $row_count = (int) ($totals['row_count'] ?? 0);

    $build['summary'] = [
      '#markup' => $this->t('Total prompt tokens: @prompt / Total tokens: @total across @count items.', [
        '@prompt' => $prompt_sum,
        '@total' => $total_sum,
        '@count' => $row_count,
      ]),
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No indexing token usage has been recorded yet.'),
    ];

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * Builds the page listing token usage recorded during live search queries.
   */
  public function buildQueryUsagePage() {
    $totals_query = $this->database->select('pins_search_azure_query_usage', 'q');
    $totals_query->addExpression('SUM(q.prompt_tokens)', 'prompt_sum');
    $totals_query->addExpression('SUM(q.total_tokens)', 'total_sum');
    $totals_query->addExpression('COUNT(*)', 'row_count');
    $totals = $totals_query->execute()->fetchAssoc();

    $header = [
      $this->t('Index'),
      $this->t('Query'),
      $this->t('Prompt tokens'),
      $this->t('Total tokens'),
      $this->t('Recorded'),
    ];

    $query = $this->database->select('pins_search_azure_query_usage', 'q')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->fields('q', [
      'index_id',
      'query_text',
      'prompt_tokens',
      'total_tokens',
      'created',
    ]);
    $query->orderBy('created', 'DESC');
    $query->limit(50);
    $result = $query->execute();

    $rows = [];
    foreach ($result as $record) {
      $rows[] = [
        $record->index_id,
        $record->query_text,
        $record->prompt_tokens,
        $record->total_tokens,
        $this->dateFormatter()->format($record->created, 'short'),
      ];
    }

    $prompt_sum = (int) ($totals['prompt_sum'] ?? 0);
    $total_sum = (int) ($totals['total_sum'] ?? 0);
    $row_count = (int) ($totals['row_count'] ?? 0);

    $build['summary'] = [
      '#markup' => $this->t('Total prompt tokens: @prompt / Total tokens: @total across @count queries.', [
        '@prompt' => $prompt_sum,
        '@total' => $total_sum,
        '@count' => $row_count,
      ]),
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No live search token usage has been recorded yet.'),
    ];

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * Gets the date formatter service.
   */
  protected function dateFormatter() {
    return \Drupal::service('date.formatter');
  }
}
