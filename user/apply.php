<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

// 未登录
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

// 只接受 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$jobId  = (int)($_POST['job_id'] ?? 0);

if ($jobId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid job.']);
    exit;
}

// 检查 job 是否存在，同时取 admin_id
$stmt = $conn->prepare('SELECT admin_id FROM jobs WHERE job_id = ? LIMIT 1');
$stmt->bind_param('i', $jobId);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$job) {
    echo json_encode(['success' => false, 'message' => 'Job not found.']);
    exit;
}

// 检查是否已申请过
$stmt = $conn->prepare('SELECT application_id FROM applications WHERE user_id = ? AND job_id = ? LIMIT 1');
$stmt->bind_param('ii', $userId, $jobId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    echo json_encode(['success' => false, 'message' => 'Already applied.', 'already' => true]);
    exit;
}

// 插入申请记录
$adminId = (int)$job['admin_id'];
$status  = 'pending';

$stmt = $conn->prepare(
    'INSERT INTO applications (user_id, job_id, admin_id, status, applied_at)
     VALUES (?, ?, ?, ?, NOW())'
);
$stmt->bind_param('iiis', $userId, $jobId, $adminId, $status);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}

$stmt->close();
exit;