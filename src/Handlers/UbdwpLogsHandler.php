<?php

namespace UsersBulkDeleteWithPreview\Handlers;

use UsersBulkDeleteWithPreview\Repositories\UbdwpLogsRepository;
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class UbdwpLogsHandler {
	public $repository;

	public function __construct($current_user_id) {
		$this->repository = new UbdwpLogsRepository($current_user_id);
	}

	public function insert_log(array $user_data): void {
		$user_id = get_current_user_id();
		$user_data_json = wp_json_encode($user_data);
		$deletion_time = current_time('mysql');

		$this->repository->insert_log($user_id, $user_data_json, $deletion_time);
	}

	public function prepare_logs_data(array $request): array {
		$limit = intval(sanitize_text_field($request['length']));
		$offset = intval(sanitize_text_field($request['start']));
		$search_value = sanitize_text_field($request['search']['value'] ?? '');

		$where = $this->repository->build_where_clause($search_value);


		$logs = $this->repository->get_logs($where, $limit, $offset);
		$total_records = $this->repository->get_total_record_count();
		$filtered_records = $this->repository->get_filtered_record_count($where);

		return [
			'draw'            => intval(sanitize_text_field($request['draw'])),
			'recordsTotal'    => intval($total_records),
			'recordsFiltered' => intval($filtered_records),
			'data'            => $this->format_logs_data($logs),
		];
	}

	private function format_logs_data(array $logs): array {
		$data = [];

		foreach ($logs as $log) {
			$deleted_user_data = json_decode($log->user_deleted_data, true);

			$data[] = [
				intval($log->ID),
				sanitize_text_field($log->display_name),
				intval($deleted_user_data['user_delete_count'] ?? 0),
				implode(
					', ',
					array_map(
						fn($entry) => sanitize_text_field($entry['email']),
						$deleted_user_data['user_delete_data'] ?? []
					)
				),
				sanitize_text_field($log->deletion_time),
			];
		}

		return $data;
	}
}
