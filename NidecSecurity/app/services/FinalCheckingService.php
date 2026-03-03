<?php

require_once __DIR__ . '/../models/FinalCheckingModel.php';

class FinalCheckingService
{
    private FinalCheckingModel $model;

    public function __construct(?FinalCheckingModel $model = null)
    {
        $this->model = $model ?: new FinalCheckingModel();
    }

    public function handlePost(array $post, int $userId): array
    {
        $token = (string)($post['csrf_token'] ?? '');
        $action = trim((string)($post['action'] ?? ''));
        $reportNo = trim((string)($post['report_no'] ?? ''));
        $remarks = trim((string)($post['final_remarks'] ?? ''));

        if (!csrf_validate($token)) {
            return ['flash' => 'Security check failed. Please refresh and try again.', 'flashType' => 'error'];
        }

        if (!in_array($action, ['confirm_resolved', 'not_resolved'], true) || $reportNo === '') {
            return ['flash' => 'Invalid request.', 'flashType' => 'error'];
        }

        if ($remarks === '') {
            return ['flash' => 'Remarks are required.', 'flashType' => 'error'];
        }

        $reportRow = $this->model->findReportForFinalChecking($reportNo);
        if (!$reportRow) {
            return ['flash' => 'Report not found.', 'flashType' => 'error'];
        }

        if ((int)($reportRow['submitted_by'] ?? 0) !== $userId) {
            http_response_code(403);
            die('Access denied.');
        }

        if (($reportRow['status'] ?? '') !== 'for_security_final_check') {
            return ['flash' => 'This report is not available for final checking.', 'flashType' => 'error'];
        }

        $reportId = (int)($reportRow['id'] ?? 0);
        $deptId = (int)($reportRow['responsible_department_id'] ?? 0);

        $conn = db();
        $conn->beginTransaction();

        try {
            if ($action === 'confirm_resolved') {
                $this->model->confirmResolved($reportId, $userId, $remarks);

                if ($deptId > 0) {
                    notify_role('department', $reportId, 'Report Fully Resolved', $deptId);
                }
                notify_role('ga_staff', $reportId, 'Report Fully Resolved');
                notify_role('ga_president', $reportId, 'Report Fully Resolved');

                $conn->commit();
                return ['flash' => 'Report marked as resolved.', 'flashType' => 'success'];
            }

            $this->model->markNotResolved($reportId, $userId, $remarks);

            if ($deptId > 0) {
                notify_role('department', $reportId, 'Report Returned. Issue Not Resolved', $deptId);
            }

            notify_role('ga_staff', $reportId, 'Report Not Resolved (Returned to Department)');
            notify_role('ga_president', $reportId, 'Report Not Resolved (Returned to Department)');

            $conn->commit();
            return ['flash' => 'Report returned to Department for further action.', 'flashType' => 'success'];
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return ['flash' => 'Failed to update report. Please try again.', 'flashType' => 'error'];
        }
    }

    public function getReportsForUser(int $userId): array
    {
        return $this->model->getReportsAwaitingFinalCheckingForUser($userId);
    }
}
