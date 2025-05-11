<?php 

namespace Drupal\file_path_checker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class IndexingLogController extends ControllerBase {

    protected $database;
    protected $requestStack;

    public function __construct(Connection $database, RequestStack $requestStack) {
        $this->database = $database;
        $this->requestStack = $requestStack;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('database'),
            $container->get('request_stack')
        );
    }

    public function displayIndexingLog() {
        $request = $this->requestStack->getCurrentRequest();
        $selectedStatus = $request->query->get('status');

        $query = $this->database->select('saa_indexing_log', 'l')
            ->fields('l');

        if ($selectedStatus) {
            $query->condition('l.status', $selectedStatus);
        }

        $totalRows = $query->countQuery()->execute()->fetchField();
        $results = $query->execute()->fetchAll();

        $statusOptions = [
            '' => '- All -',
            'node_not_found' => 'Node Not Found',
            'no_node_reference' => 'No Node Reference',
            'no_file_reference' => 'No File Reference',
            'file_not_found_after_url_update' => 'File Not Found (After URL Update)',
            'file_not_found' => 'File Not Found',
        ];

        return [
            '#theme' => 'file-path-checker-indexing-log',
            '#results' => $results,
            '#count' => $totalRows,
            '#status_options' => $statusOptions,
            '#selected_status' => $selectedStatus,
        ];
    }
}